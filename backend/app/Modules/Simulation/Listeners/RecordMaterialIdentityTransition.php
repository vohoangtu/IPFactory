<?php

namespace App\Modules\Simulation\Listeners;

use App\Modules\Narrative\Models\Chronicle;
use App\Modules\Simulation\Models\UniverseSnapshot;
use App\Modules\Simulation\Events\UniverseSimulationPulsed;

class RecordMaterialIdentityTransition
{
    public function handle(UniverseSimulationPulsed $event): void
    {
        $universe = $event->universe;
        $snapshot = $event->snapshot->fresh();

        if (!$snapshot || !$snapshot->exists) {
            return;
        }

        $current = (array) (($snapshot->metrics ?? [])['material_identity'] ?? []);
        if ($current === []) {
            return;
        }

        $previousSnapshot = UniverseSnapshot::query()
            ->where('universe_id', $universe->id)
            ->where('tick', '<', $snapshot->tick)
            ->orderByDesc('tick')
            ->first();

        if (!$previousSnapshot) {
            return;
        }

        $previous = (array) (($previousSnapshot->metrics ?? [])['material_identity'] ?? []);
        if ($previous === []) {
            return;
        }

        $changes = $this->detectChanges($previous, $current);
        if ($changes === []) {
            return;
        }

        Chronicle::create([
            'universe_id' => $universe->id,
            'from_tick' => (int) $previousSnapshot->tick,
            'to_tick' => (int) $snapshot->tick,
            'type' => 'material_transition',
            'content' => $this->buildSummary($changes, $current),
            'importance' => 0.45,
            'raw_payload' => [
                'previous' => $previous,
                'current' => $current,
                'changes' => $changes,
            ],
        ]);
    }

    /**
     * @param array<string, mixed> $previous
     * @param array<string, mixed> $current
     * @return array<string, array<string, string>>
     */
    private function detectChanges(array $previous, array $current): array
    {
        $tracked = [
            'primary_material',
            'primary_livelihood',
            'primary_settlement_style',
        ];

        $changes = [];
        foreach ($tracked as $field) {
            $from = (string) ($previous[$field] ?? 'unknown');
            $to = (string) ($current[$field] ?? 'unknown');

            if ($from !== $to) {
                $changes[$field] = ['from' => $from, 'to' => $to];
            }
        }

        return $changes;
    }

    /**
     * @param array<string, array<string, string>> $changes
     * @param array<string, mixed> $current
     */
    private function buildSummary(array $changes, array $current): string
    {
        $parts = [];

        if (isset($changes['primary_material'])) {
            $parts[] = sprintf(
                'Vat lieu chu dao chuyen tu %s sang %s',
                $changes['primary_material']['from'],
                $changes['primary_material']['to']
            );
        }

        if (isset($changes['primary_livelihood'])) {
            $parts[] = sprintf(
                'sinh ke chinh chuyen tu %s sang %s',
                $changes['primary_livelihood']['from'],
                $changes['primary_livelihood']['to']
            );
        }

        if (isset($changes['primary_settlement_style'])) {
            $parts[] = sprintf(
                'kieu cu tru chuyen tu %s sang %s',
                $changes['primary_settlement_style']['from'],
                $changes['primary_settlement_style']['to']
            );
        }

        $summary = ucfirst(implode('; ', $parts));
        $climateSignatures = (array) ($current['climate_signatures'] ?? []);
        $climate = (string) (array_key_first($climateSignatures) ?? 'unknown');

        return $summary . ". Boi canh khi hau hien tai: {$climate}.";
    }
}
