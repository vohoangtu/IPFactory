<?php

namespace App\Modules\Narrative\Services;

use App\Modules\Narrative\Models\Chronicle;
use App\Modules\Narrative\Models\HistoricalFact;
use App\Modules\Narrative\Models\Myth;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class MythologyEngine
{
    public function __construct(
        protected NarrativeQueueManager $narrativeQueueManager,
        protected ReligionSeedDetector $religionSeedDetector,
        protected \App\Modules\Simulation\Services\Civilization\MaterialIdentityProjector $materialProjector,
        protected \App\Modules\Simulation\Services\Civilization\CultureIdentityProjector $cultureProjector,
    ) {}

    public function generateFromPayload(array $payload): void
    {
        $universeId = (int) ($payload['universe_id'] ?? 0);
        if ($universeId <= 0) {
            return;
        }

        $chronicles = $this->resolveChronicles($universeId, $payload);
        $facts = $this->resolveFacts($universeId, $payload);

        if ($chronicles->isEmpty() && $facts->isEmpty()) {
            Log::debug('MythologyEngine: skipped, no chronicles/facts for payload', ['universe_id' => $universeId, 'payload' => $payload]);
            return;
        }

        $anchorChronicle = $this->selectAnchorChronicle($chronicles);
        $mythType = $this->determineMythType($payload, $chronicles, $facts);
        $impact = $this->calculateImpact($chronicles, $facts);
        $story = $this->buildStory($mythType, $chronicles, $facts, $payload);
        $sourceEvents = $this->buildSourceEvents($chronicles, $facts);

        $myth = Myth::query()->firstOrNew([
            'universe_id' => $universeId,
            'chronicle_id' => $anchorChronicle?->id,
            'myth_type' => $mythType,
        ]);

        $myth->story = $story;
        $myth->source_events = $sourceEvents;
        $myth->impact = max((float) ($myth->impact ?? 0), $impact);
        $myth->save();

        Log::info('NarrativeLoom: mythology synthesized', [
            'universe_id' => $universeId,
            'myth_id' => $myth->id,
            'myth_type' => $myth->myth_type,
            'impact' => round($myth->impact, 3),
        ]);

        if ($this->religionSeedDetector->isReligionSeed($myth)) {
            $this->narrativeQueueManager->scheduleReligion($universeId, $myth->id);
        }
    }

    protected function resolveChronicles(int $universeId, array $payload): Collection
    {
        $query = Chronicle::query()
            ->where('universe_id', $universeId)
            ->orderByDesc('importance')
            ->orderByDesc('to_tick');

        $chronicleIds = array_values(array_filter(array_map('intval', (array) ($payload['chronicle_ids'] ?? []))));
        if ($chronicleIds !== []) {
            $query->whereIn('id', $chronicleIds);
        } else {
            $startTick = isset($payload['start_tick']) ? (int) $payload['start_tick'] : null;
            $endTick = isset($payload['end_tick']) ? (int) $payload['end_tick'] : null;

            if ($startTick !== null && $endTick !== null) {
                $query->where(function ($builder) use ($startTick, $endTick) {
                    $builder->whereBetween('from_tick', [$startTick, $endTick])
                        ->orWhereBetween('to_tick', [$startTick, $endTick]);
                });
            }

            $query->whereIn('type', ['narrative', 'material_transition', 'myth', 'lore', 'collapse', 'crisis', 'war', 'hero_myth']);
        }

        return $query->limit(12)->get();
    }

    protected function resolveFacts(int $universeId, array $payload): Collection
    {
        $query = HistoricalFact::query()
            ->where('universe_id', $universeId)
            ->orderByDesc('tick');

        $startTick = isset($payload['start_tick']) ? (int) $payload['start_tick'] : null;
        $endTick = isset($payload['end_tick']) ? (int) $payload['end_tick'] : null;
        if ($startTick !== null && $endTick !== null) {
            $query->whereBetween('tick', [$startTick, $endTick]);
        }

        return $query->limit(8)->get();
    }

    protected function selectAnchorChronicle(Collection $chronicles): ?Chronicle
    {
        return $chronicles
            ->sortByDesc(fn (Chronicle $chronicle) => (((float) ($chronicle->importance ?? 0)) * 100000) + (int) ($chronicle->to_tick ?? 0))
            ->first();
    }

    protected function determineMythType(array $payload, Collection $chronicles, Collection $facts): string
    {
        if (!empty($payload['myth_type'])) {
            return (string) $payload['myth_type'];
        }

        $text = mb_strtolower(
            $chronicles->map(fn (Chronicle $chronicle) => (string) ($chronicle->content ?: data_get($chronicle->raw_payload, 'description', '')))
                ->merge($facts->map(fn (HistoricalFact $fact) => (string) implode(' ', (array) ($fact->facts ?? []))))
                ->implode(' ')
        );

        return match (true) {
            str_contains($text, 'collapse') || str_contains($text, 'crisis') || str_contains($text, 'despair') || str_contains($text, 'starvation') => 'martyr',
            str_contains($text, 'river') || str_contains($text, 'stone') || str_contains($text, 'iron') || str_contains($text, 'gold') || str_contains($text, 'water') || str_contains($text, 'material_transition') => 'oikos',
            str_contains($text, 'found') || str_contains($text, 'birth') || str_contains($text, 'origin') || str_contains($text, 'settlement') || str_contains($text, 'migration') => 'origin',
            str_contains($text, 'omen') || str_contains($text, 'prophecy') || str_contains($text, 'divine') || str_contains($text, 'sacred') || str_contains($text, 'revelation') => 'covenant',
            default => 'legend',
        };
    }

    protected function calculateImpact(Collection $chronicles, Collection $facts): float
    {
        $importancePeak = (float) ($chronicles->max('importance') ?? 0.15);
        $factWeight = min(0.25, $facts->count() * 0.04);
        $chronicleWeight = min(0.2, $chronicles->count() * 0.02);

        return max(0.2, min(1.0, $importancePeak + $factWeight + $chronicleWeight));
    }

    protected function buildStory(string $mythType, Collection $chronicles, Collection $facts, array $payload): string
    {
        $fromTick = (int) ($payload['start_tick'] ?? ($chronicles->min('from_tick') ?? $facts->min('tick') ?? 0));
        $toTick = (int) ($payload['end_tick'] ?? ($chronicles->max('to_tick') ?? $facts->max('tick') ?? $fromTick));

        $eventFragments = $chronicles
            ->take(3)
            ->map(function (Chronicle $chronicle): string {
                return trim((string) ($chronicle->content ?: data_get($chronicle->raw_payload, 'description', '')));
            })
            ->filter()
            ->values()
            ->all();

        $factFragments = $facts
            ->take(3)
            ->map(fn (HistoricalFact $fact): string => implode('; ', array_filter((array) ($fact->facts ?? []))))
            ->filter()
            ->values()
            ->all();

        $fragments = array_slice(array_values(array_filter(array_merge($eventFragments, $factFragments))), 0, 3);
        $core = $fragments !== [] ? implode(' | ', $fragments) : "A cycle of transformation unfolded between ticks {$fromTick} and {$toTick}.";

        $universeId = (int) ($payload['universe_id'] ?? 0);
        $material = $this->materialProjector->project($universeId);
        $culture = $this->cultureProjector->project($universeId);

        $matName = mb_strtolower($material['primary_material'] ?? 'vật chất');
        $livelihood = mb_strtolower($material['primary_livelihood'] ?? 'sinh tồn');
        $traits = implode(', ', array_slice((array) ($culture['dominant_traits'] ?? []), 0, 2));

        return match ($mythType) {
            'origin' => "Trong kỷ {$fromTick}-{$toTick}, ký ức khai nguyên kết tinh quanh sự khám phá ra quyền năng của {$matName}. Dân tộc vốn mang bản sắc {$traits}, đã chọn lối sống {$livelihood} làm khởi đầu cho đại nghiệp. {$core}",
            'martyr' => "Trong kỷ {$fromTick}-{$toTick}, sự khan hiếm {$matName} và những thử thách của nghề {$livelihood} đã tạo nên một vết thương tập thể. Một anh hùng đã hy sinh để bảo tồn ngọn lửa cộng đồng. {$core}",
            'oikos' => "Trong kỷ {$fromTick}-{$toTick}, linh hồn của {$matName} hòa quyện vào tâm thức {$traits}. Người dân tin rằng nghề {$livelihood} không chỉ là mưu sinh, mà là một hành trình linh thiêng để thấu hiểu thế giới. {$core}",
            'covenant' => "Trong kỷ {$fromTick}-{$toTick}, một giao ước được thiết lập giữa cộng đồng {$traits} và các lực lượng tự nhiên cai quản {$matName}. Nghề {$livelihood} từ đó được xem là thiên mệnh. {$core}",
            default => "Trong kỷ {$fromTick}-{$toTick}, truyền tích về {$matName} và hành trình {$livelihood} của người dân {$traits} đã trở thành nền tảng cho bản sắc mới. {$core}",
        };
    }

    protected function buildSourceEvents(Collection $chronicles, Collection $facts): array
    {
        return array_values(array_merge(
            $chronicles->map(fn (Chronicle $chronicle) => [
                'kind' => 'chronicle',
                'id' => $chronicle->id,
                'type' => $chronicle->type,
                'tick' => (int) ($chronicle->to_tick ?? $chronicle->from_tick ?? 0),
            ])->all(),
            $facts->map(fn (HistoricalFact $fact) => [
                'kind' => 'historical_fact',
                'id' => $fact->id,
                'category' => $fact->category,
                'tick' => (int) $fact->tick,
            ])->all()
        ));
    }
}
