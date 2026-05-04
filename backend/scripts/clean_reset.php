<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';

use Illuminate\Support\Facades\DB;
use Illuminate\Contracts\Console\Kernel;

$app->make(Kernel::class)->bootstrap();

echo "--- WorldOS Surgical Clean Reset ---\n";

$tables = [
    'universe_snapshots',
    'actor_events',
    'actors',
    'branch_events',
    'chronicles',
    'material_instances',
    'institutions',
    'civilizations',
    'factions',
    'universes'
];

echo "Cleaning simulation tables...\n";

DB::statement('SET CONSTRAINTS ALL DEFERRED'); // Specific for Postgres if needed or just disable triggers

foreach ($tables as $table) {
    if (\Illuminate\Support\Facades\Schema::hasTable($table)) {
        echo "Truncating {$table}...\n";
        DB::table($table)->truncate();
    }
}

echo "Seeding initial cosmology...\n";
\Illuminate\Support\Facades\Artisan::call('db:seed', ['--class' => 'CosmologySeeder']);

echo "Reset Complete! You can now start a new simulation from the UI or via pulse command.\n";
