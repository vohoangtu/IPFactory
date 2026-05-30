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
    private const CACHE_TTL_SECONDS = 3600; // 1 hour

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
     * 5. Fall back to deterministic placeholder if AI is unavailable.
     */
    public function generatePortrait(LegendaryAgent $legend): string
    {
        $cacheKey = "hero_image.{$legend->id}.{$legend->tick_discovered}";

        // Return cached URL if already generated this tick.
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

        // Attempt AI image generation.
        $url = $this->generateViaAi($prompt)
            ?? $this->generateFallbackUrl($legend);

        Cache::put($cacheKey, $url, self::CACHE_TTL_SECONDS);

        return $url;
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

