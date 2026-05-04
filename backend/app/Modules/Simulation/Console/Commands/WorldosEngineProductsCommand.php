<?php

namespace App\Modules\Simulation\Console\Commands;

use App\Modules\Simulation\Core\EngineProductMapping;
use App\Modules\Simulation\Core\EngineRegistry;
use Illuminate\Console\Command;

/**
 * Output engine list with product types and product → engines map (for docs and debugging).
 * See config/worldos_engine_products.php and backend/docs/ENGINE_PRODUCTS.md.
 */
class WorldosEngineProductsCommand extends Command
{
    protected $signature = 'worldos:engine-products';

    protected $description = 'List simulation engines with product types and product → engines map';

    public function handle(EngineRegistry $registry, EngineProductMapping $mapping): int
    {
        $engines = $mapping->getEnginesWithProducts($registry);
        $productToEngines = $mapping->getProductToEngines($registry);

        $this->table(
            ['Engine', 'Phase', 'Priority', 'Tick rate', 'Product types'],
            array_map(fn ($e) => [
                $e['name'],
                $e['phase'],
                $e['priority'],
                $e['tick_rate'],
                implode(', ', $e['product_types']) ?: '—',
            ], $engines)
        );

        $this->newLine();
        $this->info('Product → engines (for UI "Engine liên quan"):');
        $rows = [];
        foreach ($productToEngines as $product => $names) {
            $rows[] = [$product, implode(', ', $names)];
        }
        $this->table(['Product', 'Engines / sources'], $rows);

        return 0;
    }
}

