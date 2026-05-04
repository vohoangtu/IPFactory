<?php

namespace App\Modules\Simulation\Core;

use App\Modules\Simulation\Core\Contracts\SimulationEngine;

/**
 * Builds product → engines map for API and CLI. Merges config (worldos_engine_products.product_to_engines)
 * with engines from registry that declare productTypes().
 */
final class EngineProductMapping
{
    /**
     * @return array<string, list<string>>
     */
    public function getProductToEngines(EngineRegistry $registry): array
    {
        $base = config('worldos_engine_products.product_to_engines', []);
        $result = [];
        foreach ($base as $product => $names) {
            $result[$product] = array_values(array_unique($names));
        }

        foreach ($registry->getOrdered() as $engine) {
            if (! method_exists($engine, 'productTypes')) {
                continue;
            }
            /** @var object $engine */
            $types = $engine->productTypes();
            $engineName = $this->engineDisplayName($engine);
            foreach ($types as $type) {
                if (! isset($result[$type])) {
                    $result[$type] = [];
                }
                if (! in_array($engineName, $result[$type], true)) {
                    $result[$type][] = $engineName;
                }
            }
        }

        return $result;
    }

    /**
     * @return array<int, array{name: string, phase: string, priority: int, tick_rate: int, product_types: list<string>}>
     */
    public function getEnginesWithProducts(EngineRegistry $registry): array
    {
        $out = [];
        foreach ($registry->getOrdered() as $engine) {
            /** @var object $engine */
            $productTypes = method_exists($engine, 'productTypes') ? $engine->productTypes() : [];
            $out[] = [
                'name' => $engine->name(),
                'phase' => $engine->phase(),
                'priority' => $engine->priority(),
                'tick_rate' => $engine->tickRate(),
                'product_types' => array_values($productTypes),
            ];
        }
        return $out;
    }

    private function engineDisplayName(SimulationEngine $engine): string
    {
        $name = $engine->name();
        return str_replace('_', ' ', ucfirst($name));
    }
}
