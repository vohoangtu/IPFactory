<?php

declare(strict_types=1);

namespace App\Modules\Narrative\Services;

use App\Modules\Intelligence\Models\LegendaryAgent;
use App\Modules\Intelligence\Services\AI\VisualDnaEngine;
use App\Services\AiGateway;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * HeroImageService: Generates visual representations of Legendary Agents (§V12).
 *
 * Uses AI imagery to bring the right-brain 'Mythos' to life via the VisualDnaEngine
 * and the project's AiGateway for image generation.
 *
 * ## Integration Points
 *
 * 1. **VisualDnaEngine** — extracts mythic affinity, form signature, and color dominance
 *    from the LegendaryAgent's genome to build the image prompt.
 *
 * 2. **AiGateway::generateImage()** — routes the prompt through the configured AI provider
 *    (OpenAI DALL-E, Stable Diffusion, etc.) and returns a hosted image URL.
 *
 * 3. **Fallback** — when AI generation is unavailable or the agent is in simulation mode,
 *    a deterministic placeholder URL is returned based on agent identity.
 *
 * 4. **Caching** — generated URLs are cached per agent+tick to avoid redundant API calls.
 */
class HeroImageService
{
    /**
     * AI-generated URL TTL: short on purpose. AI image providers occasionally
     * return HTTP 200 with a broken image body, and we have no streaming way
     * to detect that at cache-write time. A short TTL means a broken URL
     * poisons the cache for at most 5 minutes before the next request
     * re-tries generation. A broken fallback URL is harmless because the
     * fallback is deterministic and regenerated on every miss.
     */
    private const AI_CACHE_TTL_SECONDS = 300; // 5 minutes

    public function __construct(
        protected VisualDnaEngine $dnaEngine,
        protected ?AiGateway $aiGateway = null,
    ) {}

    /**
     * Generate portrait for a legendary agent using Mythic Genome (§V13).
     *
     * Pipeline:
     * 1. Extract Visual DNA from LegendaryAgent.
     * 2. Merge active branch mutations if present.
     * 3. Build a cinematic prompt from DNA + roles + tags.
     * 4. Attempt AI image generation via AiGateway.
     * 5. Healthcheck the AI URL via HEAD; if it fails, do not cache and
     *    fall back to the deterministic placeholder.
     * 6. Fall back to deterministic placeholder if AI is unavailable.
     */
    public function generatePortrait(LegendaryAgent $legend): string
    {
        $cacheKey = $this->cacheKey($legend);

        // Return cached AI URL if it passed healthcheck on a previous request.
        $cached = Cache::get($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        $dna = $this->resolveVisualDna($legend);
        $prompt = $this->buildPrompt($legend, $dna);

        Log::info("HeroImage: generating portrait for LegendaryAgent #{$legend->id}", [
            'name' => $legend->name,
            'affinity' => $dna['mythic_affinity'] ?? 'unknown',
        ]);

        // Attempt AI image generation, healthcheck the URL, then cache.
        $aiUrl = $this->generateViaAi($prompt);
        if ($aiUrl !== null && $this->isImageUrlReachable($aiUrl)) {
            Cache::put($cacheKey, $aiUrl, self::AI_CACHE_TTL_SECONDS);
            return $aiUrl;
        }

        // Fallback URL is deterministic and cheap to recompute — do not cache.
        return $this->generateFallbackUrl($legend);
    }

    private function cacheKey(LegendaryAgent $legend): string
    {
        return "hero_image.{$legend->id}.{$legend->tick_discovered}";
    }

    /**
     * Cheap HTTP HEAD probe to confirm the AI-provided image URL is actually
     * reachable. Returns false on any non-2xx, timeout, or curl error so the
     * caller can fall back to the deterministic placeholder instead of
     * poisoning the cache for the next 5 minutes.
     */
    private function isImageUrlReachable(string $url): bool
    {
        try {
            $response = \Illuminate\Support\Facades\Http::timeout(5)->head($url);
            if ($response->successful()) {
                return true;
            }

            Log::warning('HeroImage: AI URL failed HEAD healthcheck, using fallback', [
                'url' => $url,
                'status' => $response->status(),
            ]);
            return false;
        } catch (\Throwable $e) {
            Log::warning('HeroImage: AI URL HEAD probe threw, using fallback', [
                'url' => $url,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Resolve the visual DNA for an agent, including active branch mutations.
     *
     * @return array{mythic_affinity: string, form_signature: string, color_dominance: string}
     */
    private function resolveVisualDna(LegendaryAgent $legend): array
    {
        $dna = $this->dnaEngine->getOrCreateRootDna($legend);

        $branch = $legend->universe->visualBranches()
            ->where('legendary_agent_id', $legend->id)
            ->latest()
            ->first();

        $dna = $branch ? $branch->visual_dna : $dna;
        $mutations = $branch ? $branch->mutations()->orderBy('tick')->get() : collect();
        $mutationText = $mutations->map(fn($m) => "mutation[{$m->type}, severity:{$m->severity}]")->implode(', ');

        $dna['mutation_text'] = $mutationText;

        return $dna;
    }

    /**
     * Build the cinematic image-generation prompt from DNA and agent metadata.
     */
    private function buildPrompt(LegendaryAgent $legend, array $dna): string
    {
        $tags = is_array($legend->fate_tags) ? implode(', ', $legend->fate_tags) : (string) $legend->fate_tags;

        return "A cinematic, epic portrait of {$legend->name}. " .
               "DNA[Affinity:{$dna['mythic_affinity']}, Form:{$dna['form_signature']}, Colors:{$dna['color_dominance']}]. " .
               "Status: {$dna['mutation_text']}. Roles: {$tags}. " .
               "Style: Mythic, hyper-realistic, volumetric lighting, digital art, WorldOS aesthetic.";
    }

    /**
     * Attempt AI image generation via the configured provider.
     *
     * Returns a hosted image URL on success, or null if generation is unavailable.
     */
    private function generateViaAi(string $prompt): ?string
    {
        if ($this->aiGateway === null) {
            return null;
        }

        try {
            $result = $this->aiGateway->generateImage([
                'prompt' => $prompt,
                'size' => '1024x1024',
                'style' => 'vivid',
            ]);

            return $result['url'] ?? $result['image_url'] ?? null;
        } catch (\Throwable $e) {
            Log::warning('HeroImage: AI image generation failed, using fallback', [
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Generate a deterministic fallback URL when AI generation is unavailable.
     *
     * The MD5 hash ensures the same agent always gets the same placeholder,
     * making it cacheable and consistent across requests.
     */
    private function generateFallbackUrl(LegendaryAgent $legend): string
    {
        return 'https://worldos.simulation/assets/legends/' .
            md5($legend->name . $legend->tick_discovered) . '.webp';
    }
}

