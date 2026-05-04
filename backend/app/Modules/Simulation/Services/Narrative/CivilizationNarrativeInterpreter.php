<?php

namespace App\Modules\Simulation\Services\Narrative;

use App\Modules\World\Models\Universe;
use App\Modules\Simulation\Models\UniverseSnapshot;
use App\Modules\Narrative\Services\NarrativeAiService;
use Illuminate\Support\Facades\Config;

/**
 * AI Civilization Interpreter / History Narrative Engine (Doc §4.6, §18; RÀ_SOÁT_TMP mục 4).
 *
 * Reads snapshot (entropy, wars, religion, population) and produces a short narrative
 * ("Year 340: Empire of Talor collapsed…"). Stub: template-based or delegates to LLM via NarrativeAiService.
 */
class CivilizationNarrativeInterpreter
{
    public function __construct(
        protected ?NarrativeAiService $narrativeAi = null
    ) {
        if ($this->narrativeAi === null && class_exists(NarrativeAiService::class)) {
            $this->narrativeAi = app()->make(NarrativeAiService::class);
        }
    }

    /**
     * Interpret universe state at snapshot and return a short narrative string.
     *
     * @param  array<string, mixed>  $snapshotData  state_vector + optional metrics (entropy, civilization.war, demographic, etc.)
     */
    public function interpretSnapshot(Universe $universe, array $snapshotData): string
    {
        $tick = (int) ($snapshotData['tick'] ?? 0);
        $entropy = (float) ($snapshotData['entropy'] ?? $snapshotData['global_entropy'] ?? $universe->entropy ?? 0.5);
        $civ = $snapshotData['civilization'] ?? [];
        $war = is_array($civ['war'] ?? null) ? $civ['war'] : [];
        $warStage = $war['war_stage'] ?? null;
        $demographic = $snapshotData['civilization']['demographic'] ?? $snapshotData['demographic'] ?? [];
        $population = $demographic['population_proxy'] ?? $demographic['total_population'] ?? null;
        $religion = $civ['religion'] ?? $snapshotData['ideology_conversion'] ?? [];

        $template = Config::get('worldos.narrative_interpreter.template');
        if ($template && is_string($template)) {
            return $this->renderTemplate($template, [
                'tick' => $tick,
                'entropy' => $entropy,
                'war_stage' => $warStage,
                'population' => $population,
                'religion' => $religion,
            ]);
        }

        $prompt = $this->buildPrompt($tick, $entropy, $warStage, $population, $religion);
        if ($this->narrativeAi && method_exists($this->narrativeAi, 'generateSnippet')) {
            $out = $this->narrativeAi->generateSnippet($prompt);
            if ($out !== null && $out !== '') {
                return $out;
            }
        }

        return $this->stubNarrative($tick, $entropy, $warStage);
    }

    /**
     * Convenience: interpret from UniverseSnapshot model.
     */
    public function interpretFromSnapshot(Universe $universe, UniverseSnapshot $snapshot): string
    {
        $data = array_merge(
            is_array($snapshot->state_vector) ? $snapshot->state_vector : [],
            is_array($snapshot->metrics ?? null) ? $snapshot->metrics : []
        );
        $data['tick'] = $snapshot->tick;
        $data['entropy'] = $snapshot->entropy ?? $universe->entropy;

        return $this->interpretSnapshot($universe, $data);
    }

    private function buildPrompt(int $tick, float $entropy, ?string $warStage, $population, $religion): string
    {
        $parts = ["Tick {$tick}. Entropy: " . round($entropy, 2) . '.'];
        if ($warStage) {
            $parts[] = "War stage: {$warStage}.";
        }
        if ($population !== null) {
            $parts[] = "Population proxy: {$population}.";
        }
        if (is_array($religion) && ! empty($religion)) {
            $parts[] = 'Religion/ideology data: ' . json_encode($religion);
        }
        return 'Write one short paragraph (2-4 sentences) of historical narrative for this civilization state. '
            . implode(' ', $parts)
            . ' Style: chronicle, past tense.';
    }

    private function stubNarrative(int $tick, float $entropy, ?string $warStage): string
    {
        $year = $tick;
        if ($entropy > 0.8) {
            return "Year {$year}: The realm descended into chaos; entropy reached " . round($entropy, 2) . '. Structures crumbled and order gave way to collapse.';
        }
        if ($warStage) {
            return "Year {$year}: The age was marked by conflict (stage: {$warStage}). Entropy stood at " . round($entropy, 2) . '.';
        }
        return "Year {$year}: Civilization persisted with entropy " . round($entropy, 2) . '.';
    }

    /**
     * @param  array<string, mixed>  $vars
     */
    private function renderTemplate(string $template, array $vars): string
    {
        foreach ($vars as $k => $v) {
            $template = str_replace('{{' . $k . '}}', (string) $v, $template);
        }
        return $template;
    }
}

