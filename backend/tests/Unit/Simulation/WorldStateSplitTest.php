<?php

declare(strict_types=1);

namespace Tests\Unit\Simulation;

use App\Modules\Simulation\Core\Runtime\State\WorldState;
use PHPUnit\Framework\TestCase;

/**
 * WorldStateSplitTest – Verifies the WorldState refactoring:
 *   - WorldStateAccessors trait (layer accessors)
 *   - WorldStateSnapshot delegation (fromArray, toArray, snapshot)
 */
class WorldStateSplitTest extends TestCase
{
    /**
     * Test 1: fromArray creates a WorldState with accessible data.
     */
    public function test_from_array_creates_world_state(): void
    {
        $data = [
            'universe_id' => 42,
            'tick' => 7,
            'entropy' => 0.65,
            'stability_index' => 0.8,
            'cosmic' => ['dark_energy' => 0.9],
            'planetary' => ['temperature' => 15.0],
            'actors' => ['count' => 5],
        ];

        $state = WorldState::fromArray($data);

        $this->assertInstanceOf(WorldState::class, $state);
        $this->assertSame(42, $state->getUniverseId());
        $this->assertSame(7, $state->getTick());
        $this->assertSame(0.65, $state->getEntropy());
        $this->assertSame(0.8, $state->getStabilityIndex());
        $this->assertSame(['dark_energy' => 0.9], $state->getCosmic());
        $this->assertSame(['temperature' => 15.0], $state->getPlanetary());
    }

    /**
     * Test 2: toArray returns all data, including actor entities when present.
     */
    public function test_to_array_returns_all_data(): void
    {
        $data = [
            'universe_id' => 1,
            'tick' => 10,
            'entropy' => 0.5,
            'cosmic' => ['age' => 1000],
            'fields' => ['survival' => 0.3],
            'zones' => [['id' => 1, 'name' => 'plains']],
        ];

        $state = WorldState::fromArray($data);
        $result = $state->toArray();

        $this->assertSame(1, $result['universe_id']);
        $this->assertSame(10, $result['tick']);
        $this->assertSame(0.5, $result['entropy']);
        $this->assertSame(['age' => 1000], $result['cosmic']);
        $this->assertSame(['survival' => 0.3], $result['fields']);
        $this->assertSame([['id' => 1, 'name' => 'plains']], $result['zones']);
    }

    /**
     * Test 3: Snapshot creates an independent copy that is not affected by later mutations.
     */
    public function test_snapshot_creates_immutable_copy(): void
    {
        $state = new WorldState([
            'entropy' => 0.3,
            'stability_index' => 0.9,
            'cosmic' => ['dark_energy' => 0.5],
        ]);
        $state->setIsObserved(true);

        $snapshot = $state->snapshot();

        // Mutate the original after taking the snapshot
        $state->setEntropy(0.99);
        $state->setStabilityIndex(0.1);
        $state->setCosmic(['dark_energy' => 1.0]);
        $state->setIsObserved(false);

        // Snapshot should retain the original values
        $this->assertSame(0.3, $snapshot->getEntropy());
        $this->assertSame(0.9, $snapshot->getStabilityIndex());
        $this->assertSame(['dark_energy' => 0.5], $snapshot->getCosmic());
        $this->assertTrue($snapshot->isObserved());

        // Original should have the mutated values
        $this->assertSame(0.99, $state->getEntropy());
        $this->assertSame(0.1, $state->getStabilityIndex());
        $this->assertFalse($state->isObserved());
    }

    /**
     * Test 4: Basic get/set for scalar values via dot-notation helpers.
     */
    public function test_get_and_set_work_correctly(): void
    {
        $state = new WorldState();

        // set → get round-trip for simple keys
        $state->set('entropy', 0.42);
        $this->assertSame(0.42, $state->get('entropy'));

        // set → get round-trip for nested keys
        $state->set('meta.active_myths', ['creation', 'flood']);
        $this->assertSame(['creation', 'flood'], $state->get('meta.active_myths'));

        // get with default when key missing
        $this->assertSame('fallback', $state->get('nonexistent.key', 'fallback'));
        $this->assertNull($state->get('nonexistent.key'));

        // Typed convenience accessors
        $state->setEntropy(0.77);
        $this->assertSame(0.77, $state->getEntropy());

        $state->setStabilityIndex(0.55);
        $this->assertSame(0.55, $state->getStabilityIndex());

        $state->setActiveAttractor('chaos');
        $this->assertSame('chaos', $state->getActiveAttractor());
    }

    /**
     * Test 5: Layer accessors return structured arrays with correct keys.
     */
    public function test_layer_accessors_return_filtered_data(): void
    {
        $state = new WorldState([
            'cosmic' => ['dark_energy' => 0.8],
            'planetary' => ['temperature' => 20.0],
            'ecosystem' => ['biodiversity' => 0.6],
            'actors' => ['count' => 10],
            'civilization' => ['knowledge_graph' => ['nodes' => 5]],
            'fields' => [
                'survival' => 0.4,
                'wealth' => 0.7,
                'entropy' => 0.2,
                'fear' => 0.1,
                'power' => 0.5,
                'authority' => 0.3,
                'order' => 0.9,
                'meaning' => 0.6,
                'knowledge' => 0.8,
                'resonance' => 0.15,
            ],
            'stability_index' => 0.85,
        ]);

        // Physical layer should contain cosmic, planetary, ecosystem data
        $physical = $state->getPhysicalLayer();
        $this->assertArrayHasKey('state', $physical);
        $this->assertArrayHasKey('pressures', $physical);
        $this->assertSame(['dark_energy' => 0.8], $physical['state']['cosmic']);
        $this->assertSame(['temperature' => 20.0], $physical['state']['planetary']);
        $this->assertSame(['biodiversity' => 0.6], $physical['state']['ecosystem']);
        $this->assertSame(0.4, $physical['pressures']['survival_pressure']);

        // Life layer
        $life = $state->getLifeLayer();
        $this->assertArrayHasKey('state', $life);
        $this->assertArrayHasKey('pressures', $life);
        $this->assertSame(0.4, $life['pressures']['metabolic_stress']);

        // Social layer
        $social = $state->getSocialLayer();
        $this->assertArrayHasKey('state', $social);
        $this->assertArrayHasKey('pressures', $social);
        $this->assertSame(0.5, $social['pressures']['war_pressure']);
        $this->assertSame(0.3, $social['pressures']['authority_intensity']);
        $this->assertSame(0.9, $social['pressures']['social_order']);

        // Narrative layer
        $narrative = $state->getNarrativeLayer();
        $this->assertArrayHasKey('state', $narrative);
        $this->assertArrayHasKey('pressures', $narrative);
        $this->assertSame(0.6, $narrative['pressures']['collective_meaning']);
        $this->assertSame(0.8, $narrative['pressures']['knowledge_diffusion']);

        // Mythic layer
        $mythic = $state->getMythicLayer();
        $this->assertArrayHasKey('state', $mythic);
        $this->assertArrayHasKey('pressures', $mythic);
        $this->assertSame(0.15, $mythic['pressures']['field_resonance']);
    }

    /**
     * Test 6: Actor entity accessors set and retrieve correctly.
     */
    public function test_get_actor_entities_returns_actors(): void
    {
        $state = new WorldState(['universe_id' => 1, 'tick' => 1]);

        // Initially empty
        $this->assertSame([], $state->getActorEntities());

        // Create mock actor-like objects
        $actor1 = new \stdClass();
        $actor1->id = 1;
        $actor1->name = 'Hero';
        $actor1->isAlive = true;
        $actor1->zone_id = 10;

        $actor2 = new \stdClass();
        $actor2->id = 2;
        $actor2->name = 'Villain';
        $actor2->isAlive = true;
        $actor2->zone_id = 20;

        $state->setActorEntities([$actor1, $actor2]);
        $entities = $state->getActorEntities();

        $this->assertCount(2, $entities);
        $this->assertSame(1, $entities[0]->id);
        $this->assertSame('Hero', $entities[0]->name);
        $this->assertSame(2, $entities[1]->id);
        $this->assertSame('Villain', $entities[1]->name);

        // forgetActor removes by ID
        $state->forgetActor(1);
        $remaining = $state->getActorEntities();
        $this->assertCount(1, $remaining);
        $this->assertSame(2, array_values($remaining)[0]->id);
    }
}
