<?php

namespace App\Modules\SocialGraph\Services;

use Laudis\Neo4j\ClientBuilder;
use Laudis\Neo4j\Contracts\ClientInterface;
use App\Modules\Intelligence\Models\Actor;
use Illuminate\Support\Facades\Log;
use Laudis\Neo4j\Authentication\Authenticate;

class Neo4jSocialSyncer
{
    protected ?ClientInterface $client = null;

    public function __construct()
    {
    }

    protected function getClient(): ClientInterface
    {
        if ($this->client === null) {
            $uri = config('worldos.graph.uri', 'bolt://neo4j:worldos_secret@neo4j:7687');
            // Force bolt if we get an http address (likely from a misconfigured bridge)
            if (str_starts_with($uri, 'http')) {
                $uri = str_replace(['http://', 'https://'], 'bolt://', $uri);
                $uri = str_replace(':7474', ':7687', $uri);
            }

            // Parse auth from bolt URI (bolt://user:pass@host:port)
            // Use ClientBuilder's internal basic auth if Authentication class is missing
            $user = 'neo4j';
            $pass = 'worldos_secret';
            if (preg_match('/bolt:\/\/([^:]+):([^@]+)@/', $uri, $matches)) {
                $user = $matches[1];
                $pass = $matches[2];
            }

            $this->client = ClientBuilder::create()
                ->withDriver('bolt', $uri, Authenticate::basic($user, $pass))
                ->build();
        }
        return $this->client;
    }

    /**
     * Sync a batch of actors and their relationships to Neo4j.
     */
    public function syncActors(iterable $actors): void
    {
        foreach ($actors as $actor) {
            $this->syncActorNode($actor);
            $this->syncActorRelations($actor);
        }
    }

    /**
     * Create or update an Actor node.
     */
    protected function syncActorNode(Actor $actor): void
    {
        $this->getClient()->run(<<<'CYPHER'
            MERGE (a:Actor {id: $id})
            SET a.name = $name,
                a.archetype = $archetype,
                a.universe_id = $universe_id,
                a.is_alive = $is_alive
        CYPHER, [
            'id' => (int) $actor->id,
            'name' => $actor->name,
            'archetype' => $actor->archetype,
            'universe_id' => (int) $actor->universe_id,
            'is_alive' => (bool) $actor->is_alive,
        ]);
    }

    /**
     * Sync relationships from metrics.social_relations.
     */
    protected function syncActorRelations(Actor $actor): void
    {
        $relations = data_get($actor->metrics, 'social_relations', []);
        
        foreach ($relations as $targetId => $rel) {
            $this->getClient()->run(<<<'CYPHER'
                MATCH (a:Actor {id: $source_id})
                MATCH (b:Actor {id: $target_id})
                MERGE (a)-[r:RELATION]->(b)
                SET r.trust = $trust,
                    r.fear = $fear,
                    r.updated_at = timestamp()
            CYPHER, [
                'source_id' => (int) $actor->id,
                'target_id' => (int) $targetId,
                'trust' => (float) ($rel['trust'] ?? 0.0),
                'fear' => (float) ($rel['fear'] ?? 0.0),
            ]);
        }
    }

    /**
     * Create or update an Actor node by manual parameters.
     */
    public function createActorNode(string $id, string $name, string $archetype, int $universe_id): void
    {
        $this->getClient()->run(<<<'CYPHER'
            MERGE (a:Actor {id: $id})
            SET a.name = $name,
                a.archetype = $archetype,
                a.universe_id = $universe_id,
                a.is_alive = true
        CYPHER, [
            'id' => $id,
            'name' => $name,
            'archetype' => $archetype,
            'universe_id' => $universe_id,
        ]);
    }

    /**
     * Create Parent-Child relationship.
     */
    public function createParentChildRelation(string $parentId, string $childId): void
    {
        $this->getClient()->run(<<<'CYPHER'
            MATCH (p:Actor {id: $pId})
            MATCH (c:Actor {id: $cId})
            MERGE (p)-[r:PARENT_OF]->(c)
            SET r.created_at = timestamp()
        CYPHER, [
            'pId' => $parentId,
            'cId' => $childId,
        ]);
    }

    /**
     * Mark actor as deceased.
     */
    public function markActorDeceased(string $id): void
    {
        $this->getClient()->run(<<<'CYPHER'
            MATCH (a:Actor {id: $id})
            SET a.is_alive = false
        CYPHER, ['id' => $id]);
    }

    /**
     * Find "Interesting Cliques" for Narrative Loom.
     */
    public function findAnomalousCliques(int $universeId): array
    {
        // Example: Find dense clusters of Fear
        $result = $this->getClient()->run(<<<'CYPHER'
            MATCH (a:Actor)-[r:RELATION]->(b:Actor)
            WHERE a.universe_id = $universe_id AND r.fear > 0.7
            WITH a, count(r) as fear_connections
            WHERE fear_connections > 3
            RETURN a.id as actor_id, a.name as name, fear_connections
            LIMIT 5
        CYPHER, ['universe_id' => $universeId]);

        return $result->toArray();
    }
}

