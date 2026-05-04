<?php

namespace App\Modules\Simulation\Services\Core;

use App\Modules\Simulation\Events\AutopoiesisMutationApplied;
use App\Modules\Simulation\Core\Runtime\RuleVM\RuleVmService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Phase 71: Rule Mutation Service 🧬🧪
 * 
 * Quản lý việc lưu trữ và áp dụng các "đột biến" (mutations) vào logic DSL.
 * Hỗ trợ persistence cho quá trình Autopoiesis (Mã nguồn tự sinh).
 */
class RuleMutationService
{
    protected string $storageBase = 'simulation/mutated_rules';

    /**
     * Apply a mutation with versioning.
     */
    public function applyMutation(string $dslPath, string $newContent, array $metadata = []): bool
    {
        $hash = md5($dslPath);
        $timestamp = now()->timestamp;
        $versionDir = "{$this->storageBase}/{$hash}";
        $versionFile = "{$versionDir}/v{$timestamp}.dsl";
        $currentFile = "{$versionDir}/current.dsl";
        $previousContent = Storage::disk('local')->exists($currentFile)
            ? Storage::disk('local')->get($currentFile)
            : null;

        try {
            if (!Storage::disk('local')->exists($versionDir)) {
                Storage::disk('local')->makeDirectory($versionDir);
            }

            $metadata = array_merge($metadata, [
                'dsl_hash' => $hash,
                'dsl_path' => $dslPath,
                'timestamp' => $timestamp,
            ]);

            // Save new version
            Storage::disk('local')->put($versionFile, $newContent);
            // Update pointer
            Storage::disk('local')->put($currentFile, $newContent);
            Storage::disk('local')->put("{$versionDir}/v{$timestamp}.json", json_encode($metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
            Storage::disk('local')->put("{$versionDir}/latest.json", json_encode($metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
            
            Log::info("RuleMutationService: Applied mutation to $dslPath (v{$timestamp})", [
                'meta' => $metadata,
                'path' => $versionFile
            ]);

            RuleVmService::clearDslCache($dslPath);
            $this->clearCausalCache();
            $this->broadcastMutation($hash, $newContent, $previousContent, $metadata);
            return true;
        } catch (\Exception $e) {
            Log::error("RuleMutationService: Failed to apply mutation", ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Rollback to a specific version or the previous one.
     */
    public function rollbackMutation(string $dslPath, ?int $timestamp = null): bool
    {
        $hash = md5($dslPath);
        $versionDir = "{$this->storageBase}/{$hash}";

        if (!Storage::disk('local')->exists($versionDir)) {
            return false;
        }

        $files = collect(Storage::disk('local')->files($versionDir))
            ->filter(fn($f) => str_ends_with($f, '.dsl') && !str_contains($f, 'current.dsl'))
            ->sortDesc();

        $targetFile = $timestamp 
            ? "{$versionDir}/v{$timestamp}.dsl"
            : $files->skip(1)->first(); // Previous version

        if ($targetFile && Storage::disk('local')->exists($targetFile)) {
            $content = Storage::disk('local')->get($targetFile);
            Storage::disk('local')->put("{$versionDir}/current.dsl", $content);
            Log::warning("RuleMutationService: Rolled back $dslPath to $targetFile");
            RuleVmService::clearDslCache($dslPath);
            $this->clearCausalCache();
            return true;
        }

        return false;
    }

    public function getMutatedContent(string $originalPath): ?string
    {
        $hash = md5($originalPath);
        $currentFile = "{$this->storageBase}/{$hash}/current.dsl";

        if (Storage::disk('local')->exists($currentFile)) {
            return Storage::disk('local')->get($currentFile);
        }

        return null;
    }

    public function getMutationHistory(string $dslPath): array
    {
        $hash = md5($dslPath);
        return Storage::disk('local')->files("{$this->storageBase}/{$hash}");
    }

    public function getMutationChronicle(): array
    {
        $chronicle = [];

        foreach (Storage::disk('local')->directories($this->storageBase) as $dir) {
            $hash = basename($dir);
            $versions = collect(Storage::disk('local')->files($dir))
                ->filter(fn($file) => str_starts_with(basename($file), 'v') && str_ends_with($file, '.dsl'))
                ->sortDesc()
                ->values();

            $latestVersion = $versions->first();
            $latestTimestamp = $latestVersion ? $this->extractTimestampFromVersion($latestVersion) : null;
            $latestMetadata = $this->readMetadataFile("{$dir}/latest.json")
                ?? ($latestVersion ? $this->readMetadataFile($this->replaceDslExtensionWithJson($latestVersion)) : null)
                ?? [];

            $chronicle[] = [
                'dsl_hash' => $hash,
                'dsl_path' => $latestMetadata['dsl_path'] ?? null,
                'version_count' => $versions->count(),
                'has_current' => Storage::disk('local')->exists("{$dir}/current.dsl"),
                'latest_version' => $latestVersion ? basename($latestVersion) : null,
                'latest_timestamp' => $latestTimestamp?->toIso8601String(),
                'latest_tick' => isset($latestMetadata['tick']) ? (int) $latestMetadata['tick'] : null,
                'vector' => $latestMetadata['vector'] ?? null,
                'source' => $latestMetadata['source'] ?? 'autopoiesis',
                'universe_id' => isset($latestMetadata['universe_id']) ? (int) $latestMetadata['universe_id'] : null,
            ];
        }

        usort(
            $chronicle,
            fn(array $left, array $right) => strcmp((string) ($right['latest_timestamp'] ?? ''), (string) ($left['latest_timestamp'] ?? ''))
        );

        return $chronicle;
    }

    public function getMutationDetail(string $hash): ?array
    {
        $versionDir = "{$this->storageBase}/{$hash}";
        if (!Storage::disk('local')->exists($versionDir)) {
            return null;
        }

        $versions = collect(Storage::disk('local')->files($versionDir))
            ->filter(fn($file) => str_starts_with(basename($file), 'v') && str_ends_with($file, '.dsl'))
            ->sortDesc()
            ->values();

        $currentFile = "{$versionDir}/current.dsl";
        $currentContent = Storage::disk('local')->exists($currentFile) ? Storage::disk('local')->get($currentFile) : null;
        $latestVersion = $versions->first();
        $previousVersion = $versions->skip(1)->first();
        $latestMetadata = $this->readMetadataFile("{$versionDir}/latest.json")
            ?? ($latestVersion ? $this->readMetadataFile($this->replaceDslExtensionWithJson($latestVersion)) : null)
            ?? [];

        $dslPath = $latestMetadata['dsl_path'] ?? null;
        $originalContent = $this->resolveOriginalDslContent(is_string($dslPath) ? $dslPath : null);

        return [
            'dsl_hash' => $hash,
            'dsl_path' => $dslPath,
            'version_count' => $versions->count(),
            'current_content' => $currentContent,
            'previous_content' => $previousVersion ? Storage::disk('local')->get($previousVersion) : null,
            'original_content' => $originalContent,
            'latest_version' => $latestVersion ? basename($latestVersion) : null,
            'latest_timestamp' => $latestVersion ? $this->extractTimestampFromVersion($latestVersion)?->toIso8601String() : null,
            'metadata' => $latestMetadata,
            'versions' => $versions->map(function (string $file) {
                $metadata = $this->readMetadataFile($this->replaceDslExtensionWithJson($file)) ?? [];

                return [
                    'file' => basename($file),
                    'timestamp' => $this->extractTimestampFromVersion($file)?->toIso8601String(),
                    'tick' => isset($metadata['tick']) ? (int) $metadata['tick'] : null,
                    'vector' => $metadata['vector'] ?? null,
                    'source' => $metadata['source'] ?? 'autopoiesis',
                ];
            })->values()->all(),
        ];
    }

    private function clearCausalCache(): void
    {
        try {
            $cacheService = app(\App\Modules\Simulation\Services\Core\CausalCacheService::class);
            $cacheService->clear(); 
        } catch (\Exception $e) {
            // Cache service might not be available in all contexts
        }
    }

    private function broadcastMutation(string $hash, string $currentContent, ?string $previousContent, array $metadata): void
    {
        $universeId = isset($metadata['universe_id']) ? (int) $metadata['universe_id'] : 0;
        if ($universeId <= 0) {
            return;
        }

        event(new AutopoiesisMutationApplied($universeId, [
            'dsl_hash' => $hash,
            'dsl_path' => $metadata['dsl_path'] ?? null,
            'tick' => isset($metadata['tick']) ? (int) $metadata['tick'] : null,
            'vector' => $metadata['vector'] ?? null,
            'source' => $metadata['source'] ?? 'autopoiesis',
            'timestamp' => isset($metadata['timestamp']) ? now()->setTimestamp((int) $metadata['timestamp'])->toIso8601String() : now()->toIso8601String(),
            'summary' => $metadata['vector'] ?? 'Autopoiesis mutation applied',
            'version_count' => count($this->getMutationHistory((string) ($metadata['dsl_path'] ?? ''))),
            'current_length' => strlen($currentContent),
            'previous_length' => $previousContent !== null ? strlen($previousContent) : 0,
        ]));
    }

    private function readMetadataFile(string $path): ?array
    {
        if (!Storage::disk('local')->exists($path)) {
            return null;
        }

        $payload = json_decode(Storage::disk('local')->get($path), true);
        return is_array($payload) ? $payload : null;
    }

    private function replaceDslExtensionWithJson(string $path): string
    {
        return preg_replace('/\.dsl$/', '.json', $path) ?? $path;
    }

    private function extractTimestampFromVersion(string $path): ?\Illuminate\Support\Carbon
    {
        if (!preg_match('/v(\d+)\.dsl$/', basename($path), $matches)) {
            return null;
        }

        return now()->setTimestamp((int) $matches[1]);
    }

    private function resolveOriginalDslContent(?string $dslPath): ?string
    {
        if (!$dslPath || str_starts_with($dslPath, 'vocation://')) {
            return null;
        }

        $candidates = [
            resource_path("dsl/{$dslPath}.dsl"),
            resource_path("dsl/{$dslPath}"),
            $dslPath,
        ];

        foreach ($candidates as $candidate) {
            if (is_string($candidate) && is_file($candidate)) {
                return file_get_contents($candidate) ?: null;
            }
        }

        return null;
    }
}

