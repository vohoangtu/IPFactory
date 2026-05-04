<?php

namespace Database\Seeders;

use App\Modules\WorldOS\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        if (! User::where('email', 'test@example.com')->exists()) {
            User::create([
                'name' => 'Test User',
                'email' => 'test@example.com',
                'password' => \Illuminate\Support\Facades\Hash::make('password'),
                'email_verified_at' => now(),
            ]);
        }

        $this->call([
            RuleSetTierSeeder::class,
            RuleSetDefinitionSeeder::class,
            RuleSetCombineRulesSeeder::class,
            VocationRegistrySeeder::class,
            CosmologySeeder::class,
            MaterialSeeder::class,
            SymbolicMaterialSeeder::class,
            MaterialExpansionSeeder::class,
            FlavorTextSeeder::class,
            EventTriggerSeeder::class,
            CivilizationAttractorSeeder::class,
            AttractorSpawnRuleSeeder::class,
            WorldRulesetRuntimeSeeder::class,
        ]);
    }
}
