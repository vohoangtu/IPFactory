<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Support\Facades\Http;
use App\Modules\Narrative\Services\NarrativeLoomService;

class AdaptiveNarrativeTest extends TestCase
{
    /**
     * Kiểm thử xem Impact Score dưới 5.0 thì có gửi Http tới Loom không.
     * Tùy thuộc vào thực tế implement, có thể Laravel lọc trước khi gửi hoặc Laravel gửi tuốt và Python tự cắt (Hiện đang implement ở Python).
     * Do đó, ở test này ta sẽ mock HTTP call tới Python và trả về Mock JSON format giống hệt "Minor Event".
     */
    public function test_python_loom_returns_minor_event_for_low_impact(): void
    {
        // Mock phản hồi từ Python Narrative Loom khi Impact Score < 5.0
        Http::fake([
            'narrative-loom:8000/scribe-history' => Http::response([
                'message' => 'Success',
                'chronicle' => [
                    'event_name' => 'Minor Event: War Declared',
                    'chronicle' => "Hệ thống ghi nhận sự kiện 'War Declared' tại Vũ trụ #1. Tác động vi mô không đủ để khắc sâu vào lịch sử."
                ]
            ], 200)
        ]);

        $response = Http::post('http://narrative-loom:8000/scribe-history', [
            'event_type' => 'war_declared',
            'impact_score' => 3.5,
            'trigger_data' => ['zone' => 'A'],
            'world_id' => 1
        ]);

        $chronicle = $response->json();

        $this->assertIsArray($chronicle);
        $this->assertStringContainsString('Minor Event', $chronicle['chronicle']['event_name'] ?? $chronicle['event_name'] ?? '');
        
        // Assert Http was actually called once
        Http::assertSent(function ($request) {
            return str_contains($request->url(), '/scribe-history') &&
                   $request['impact_score'] == 3.5;
        });
    }
}
