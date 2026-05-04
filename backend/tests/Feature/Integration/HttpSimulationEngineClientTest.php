<?php

namespace Tests\Feature\Integration;

use App\Services\Simulation\HttpSimulationEngineClient;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class HttpSimulationEngineClientTest extends TestCase
{
    private HttpSimulationEngineClient $client;

    protected function setUp(): void
    {
        parent::setUp();
        $this->client = new HttpSimulationEngineClient('http://engine:50052');
    }

    // ─── Advance ────────────────────────────────────────────────

    public function test_advance_sends_correct_payload_and_parses_snapshot(): void
    {
        Http::fake([
            'engine:50052/advance' => Http::response([
                'ok' => true,
                'snapshot' => [
                    'universe_id' => 42,
                    'tick' => 5,
                    'state_vector' => ['zones' => []],
                    'entropy' => 0.35,
                    'stability_index' => 0.65,
                    'metrics' => ['order' => 0.65],
                    'sci' => 0.8,
                    'instability_gradient' => 0.1,
                ],
                'error_message' => '',
            ], 200),
        ]);

        $result = $this->client->advance(42, 5, ['tick' => 0], ['origin' => 'generic']);

        $this->assertTrue($result['ok']);
        $this->assertNotNull($result['snapshot']);
        $this->assertEquals(42, $result['snapshot']['universe_id']);
        $this->assertEquals(5, $result['snapshot']['tick']);
        $this->assertEquals(0.35, $result['snapshot']['entropy']);
        $this->assertEquals(0.65, $result['snapshot']['stability_index']);
        $this->assertEquals(0.8, $result['snapshot']['sci']);

        Http::assertSent(function ($request) {
            return $request->url() === 'http://engine:50052/advance'
                && $request['universe_id'] === 42
                && $request['ticks'] === 5
                && $request['state_input']['tick'] === 0
                && $request['world_config']['origin'] === 'generic';
        });
    }

    public function test_advance_handles_engine_error_gracefully(): void
    {
        Http::fake([
            'engine:50052/advance' => Http::response([
                'ok' => false,
                'snapshot' => null,
                'error_message' => 'Engine panic: stack overflow',
            ], 200),
        ]);

        $result = $this->client->advance(1, 1);

        $this->assertFalse($result['ok']);
        $this->assertNull($result['snapshot']);
        $this->assertEquals('Engine panic: stack overflow', $result['error_message']);
    }

    public function test_advance_handles_http_timeout(): void
    {
        Http::fake([
            'engine:50052/advance' => function () {
                throw new \Illuminate\Http\Client\ConnectionException('Connection timed out');
            },
        ]);

        $result = $this->client->advance(1, 1);

        $this->assertFalse($result['ok']);
        $this->assertNull($result['snapshot']);
        $this->assertStringContainsString('timed out', $result['error_message']);
    }

    public function test_advance_handles_http_500(): void
    {
        Http::fake([
            'engine:50052/advance' => Http::response('Internal Server Error', 500),
        ]);

        $result = $this->client->advance(1, 1);

        $this->assertFalse($result['ok']);
        $this->assertNull($result['snapshot']);
        $this->assertNotEmpty($result['error_message']);
    }

    /** Contract: advance snapshot contains all keys required by WorldOS_DSL_Spec / state contract (state_vector, entropy, stability_index, metrics, sci, instability_gradient, global_fields). */
    public function test_advance_snapshot_contains_contract_keys(): void
    {
        Http::fake([
            'engine:50052/advance' => Http::response([
                'ok' => true,
                'snapshot' => [
                    'universe_id' => 1,
                    'tick' => 1,
                    'state_vector' => ['zones' => []],
                    'entropy' => 0.4,
                    'stability_index' => 0.7,
                    'metrics' => ['sci' => 0.9],
                    'sci' => 0.9,
                    'instability_gradient' => 0.05,
                    'global_fields' => ['civ_stability' => 0.8],
                ],
                'error_message' => '',
            ], 200),
        ]);

        $result = $this->client->advance(1, 1, []);

        $this->assertTrue($result['ok']);
        $snap = $result['snapshot'];
        $this->assertIsArray($snap);
        $this->assertArrayHasKey('tick', $snap);
        $this->assertArrayHasKey('state_vector', $snap);
        $this->assertArrayHasKey('entropy', $snap);
        $this->assertArrayHasKey('stability_index', $snap);
        $this->assertArrayHasKey('metrics', $snap);
        $this->assertArrayHasKey('sci', $snap);
        $this->assertArrayHasKey('instability_gradient', $snap);
        $this->assertArrayHasKey('global_fields', $snap);
        $this->assertEquals(0.8, $snap['global_fields']['civ_stability'] ?? null);
    }

    /** Contract: evaluate-rules receives state (per RuleVmService::buildStateForVm) and returns outputs; client parses ok and outputs. */
    public function test_evaluate_rules_sends_state_and_parses_outputs(): void
    {
        Http::fake([
            'engine:50052/evaluate-rules' => Http::response([
                'ok' => true,
                'outputs' => [
                    ['type' => 'event', 'event_name' => 'high_entropy'],
                    ['type' => 'adjust_stability', 'adjust_stability_delta' => -0.1],
                ],
                'error_message' => null,
            ], 200),
        ]);

        $state = [
            'tick' => 5,
            'entropy' => 0.7,
            'global_entropy' => 0.7,
            'stability_index' => 0.6,
            'sci' => 0.8,
            'instability_gradient' => 0.1,
            'knowledge_core' => 0.5,
        ];
        $rulesDsl = 'rule entropy > 0.5 => emit_event high_entropy';

        $result = $this->client->evaluateRules($state, $rulesDsl);

        $this->assertTrue($result['ok']);
        $this->assertIsArray($result['outputs']);
        $this->assertCount(2, $result['outputs']);
        $this->assertEquals('event', $result['outputs'][0]['type']);
        $this->assertEquals('high_entropy', $result['outputs'][0]['event_name']);
        $this->assertEquals('adjust_stability', $result['outputs'][1]['type']);
        $this->assertEquals(-0.1, $result['outputs'][1]['adjust_stability_delta']);

        Http::assertSent(function ($request) use ($state, $rulesDsl) {
            if ($request->url() !== 'http://engine:50052/evaluate-rules') {
                return false;
            }
            $body = json_decode($request->body(), true);
            if (!is_array($body) || !isset($body['state'])) {
                return false;
            }
            foreach (['tick', 'entropy', 'stability_index', 'sci', 'instability_gradient', 'knowledge_core'] as $key) {
                if (!array_key_exists($key, $body['state'])) {
                    return false;
                }
            }
            return ($body['rules_dsl'] ?? null) === $rulesDsl;
        });
    }

    // ─── Merge ──────────────────────────────────────────────────

    public function test_merge_sends_correct_payload_and_parses_response(): void
    {
        Http::fake([
            'engine:50052/merge' => Http::response([
                'ok' => true,
                'snapshot' => [
                    'tick' => 10,
                    'state_vector' => '{"merged": true}',
                    'entropy' => 0.5,
                    'stability_index' => 0.5,
                    'metrics' => '{}',
                    'sci' => 0.7,
                    'instability_gradient' => 0.2,
                ],
                'error_message' => '',
            ], 200),
        ]);

        $result = $this->client->merge('{"state":"a"}', '{"state":"b"}');

        $this->assertTrue($result['ok']);
        $this->assertNotNull($result['snapshot']);
        $this->assertEquals(10, $result['snapshot']['tick']);
        $this->assertEquals(0.5, $result['snapshot']['entropy']);
        $this->assertEquals(0.7, $result['snapshot']['sci']);

        Http::assertSent(function ($request) {
            return $request->url() === 'http://engine:50052/merge'
                && $request['state_a'] === '{"state":"a"}'
                && $request['state_b'] === '{"state":"b"}';
        });
    }

    public function test_merge_handles_engine_failure(): void
    {
        Http::fake([
            'engine:50052/merge' => Http::response('Bad Gateway', 502),
        ]);

        $result = $this->client->merge('{}', '{}');

        $this->assertFalse($result['ok']);
        $this->assertNull($result['snapshot']);
    }
}
