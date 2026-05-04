<?php

namespace App\Modules\Simulation\Core\Graph;

use App\Modules\Simulation\Core\Contracts\WorldOsGraphServiceInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Sync WorldEvent to Neo4j (doc §15). Uses Neo4j HTTP API (transaction endpoint).
 * When enabled, creates/merges Event node and optional relationships.
 */
final class Neo4jWorldOsGraphService implements WorldOsGraphServiceInterface
{
    public function __construct(
        private readonly string $uri,
        private readonly ?string $username = null,
        private readonly ?string $password = null,
    ) {
    }

    public function syncEvent(array $eventData): void
    {
        $id = $eventData['id'] ?? null;
        $type = $eventData['type'] ?? 'unknown';
        $universeId = (int) ($eventData['universe_id'] ?? 0);
        $tick = (int) ($eventData['tick'] ?? 0);
        $payload = $eventData['payload'] ?? [];
        $actors = $eventData['actors'] ?? [];
        $location = $eventData['location'] ?? null;

        if ($id === null) {
            return;
        }

        try {
            $cypher = 'MERGE (e:Event {id: $id}) SET e.type = $type, e.universe_id = $universeId, e.tick = $tick, e.location = $location, e.updated_at = datetime()';
            $params = [
                'id' => $id,
                'type' => $type,
                'universe_id' => $universeId,
                'tick' => $tick,
                'location' => $location ?? '',
            ];
            $this->runQuery($cypher, $params);

            if (! empty($actors)) {
                foreach (array_slice($actors, 0, 5) as $i => $actorId) {
                    $actorId = is_scalar($actorId) ? (string) $actorId : json_encode($actorId);
                    $this->runQuery(
                        'MATCH (e:Event {id: $eventId}) MERGE (a:Actor {id: $actorId}) MERGE (e)-[:INVOLVES]->(a)',
                        ['eventId' => $id, 'actorId' => $actorId . '_' . $i]
                    );
                }
            }
        } catch (\Throwable $e) {
            Log::warning('Neo4j syncEvent failed: ' . $e->getMessage(), ['event_id' => $id]);
        }
    }

    private function runQuery(string $cypher, array $params): void
    {
        $body = ['statements' => [['statement' => $cypher, 'parameters' => $params]]];
        $request = Http::withHeaders(['Content-Type' => 'application/json'])->withBody(json_encode($body), 'application/json');
        if ($this->username !== null && $this->password !== null) {
            $request = $request->withBasicAuth($this->username, $this->password);
        }
        $response = $request->post(rtrim($this->uri, '/') . '/db/neo4j/tx/commit');
        if (! $response->successful()) {
            throw new \RuntimeException('Neo4j request failed: ' . $response->body());
        }
    }
}
