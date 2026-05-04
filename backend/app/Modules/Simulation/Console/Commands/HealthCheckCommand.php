<?php

declare(strict_types=1);

namespace App\Modules\Simulation\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Health check command — verifies connectivity to DB, Redis, and Neo4j.
 *
 * Usage:
 *   php artisan worldos:health-check
 */
class HealthCheckCommand extends Command
{
    protected $signature = 'worldos:health-check';

    protected $description = 'Verify connectivity to PostgreSQL, Redis, and Neo4j services';

    public function handle(): int
    {
        $this->info('WorldOS Health Check');
        $this->newLine();

        $allHealthy = true;

        // 1. PostgreSQL
        $dbOk = $this->checkDatabase();
        $this->reportStatus('PostgreSQL', $dbOk);
        $allHealthy = $allHealthy && $dbOk;

        // 2. Redis
        $redisOk = $this->checkRedis();
        $this->reportStatus('Redis', $redisOk);
        $allHealthy = $allHealthy && $redisOk;

        // 3. Neo4j
        $neo4jOk = $this->checkNeo4j();
        $this->reportStatus('Neo4j', $neo4jOk);
        $allHealthy = $allHealthy && $neo4jOk;

        $this->newLine();

        if ($allHealthy) {
            $this->info('All services healthy.');

            return 0;
        }

        $this->error('One or more services are unhealthy.');

        return 1;
    }

    private function checkDatabase(): bool
    {
        try {
            DB::select('SELECT 1');

            return true;
        } catch (\Throwable $e) {
            $this->line("  DB error: {$e->getMessage()}");

            return false;
        }
    }

    private function checkRedis(): bool
    {
        try {
            $key = 'worldos:health-check:' . time();
            Cache::put($key, 'ok', 10);
            $value = Cache::get($key);
            Cache::forget($key);

            return $value === 'ok';
        } catch (\Throwable $e) {
            $this->line("  Redis error: {$e->getMessage()}");

            return false;
        }
    }

    private function checkNeo4j(): bool
    {
        try {
            $uri = config('worldos_knowledge.neo4j.uri', '');
            if (empty($uri)) {
                $this->line('  Neo4j: not configured (uri is empty)');

                return false;
            }

            // Parse host and port from bolt:// or neo4j:// URI
            $parsed = parse_url(str_replace(['bolt://', 'neo4j://'], 'http://', $uri));
            $host = $parsed['host'] ?? 'localhost';
            $port = 7474; // Neo4j HTTP browser port

            $username = config('worldos_knowledge.neo4j.username', 'neo4j');
            $password = config('worldos_knowledge.neo4j.password', '');

            $ch = curl_init("http://{$host}:{$port}");
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 5);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);
            if ($username && $password) {
                curl_setopt($ch, CURLOPT_USERPWD, "{$username}:{$password}");
            }
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            return $httpCode === 200;
        } catch (\Throwable $e) {
            $this->line("  Neo4j error: {$e->getMessage()}");

            return false;
        }
    }

    private function reportStatus(string $service, bool $ok): void
    {
        if ($ok) {
            $this->line("  <info>[OK]</info>   {$service}");
        } else {
            $this->line("  <error>[FAIL]</error> {$service}");
        }
    }
}
