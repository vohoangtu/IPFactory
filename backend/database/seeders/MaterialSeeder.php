<?php

namespace Database\Seeders;

use App\Modules\World\Models\Material;
use App\Modules\Simulation\Models\MaterialPressure;
use App\Modules\Simulation\Models\MaterialReaction;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class MaterialSeeder extends Seeder
{
    /**
     * Seed materials by origin (Vietnamese, European, Futuristic...).
     */
    public function run(?string $origin = 'generic'): void
    {
        $seeds = match ($origin) {
            'vietnamese' => $this->vietnamese(),
            'european', 'western' => $this->westernFeudal(),
            'eastern' => $this->easternFeudal(),
            'futuristic' => $this->futuristic(),
            'special' => $this->special(),
            default => array_merge(
                $this->vietnamese(), 
                $this->westernFeudal(), 
                $this->easternFeudal(),
                $this->futuristic(), 
                $this->special()
            ),
        };

        foreach ($seeds as $data) {
            $slug = Str::slug($data['name']);
            $material = Material::updateOrCreate(
                ['slug' => $slug],
                [
                    'name' => $data['name'],
                    'description' => $data['description'] ?? null,
                    'ontology' => $data['ontology'],
                    'lifecycle' => 'dormant',
                    'inputs' => $data['inputs'] ?? [],
                    'outputs' => $data['outputs'] ?? [],
                    'pressure_coefficients' => $data['pressure_coefficients'] ?? null,
                ]
            );

            if (! empty($data['pressures'])) {
                foreach ($data['pressures'] as $key => $coef) {
                    MaterialPressure::updateOrCreate(
                        ['material_id' => $material->id, 'vector_key' => $key],
                        ['coefficient' => $coef]
                    );
                }
            }
        }

        $this->seedReactions();
    }

    protected function seedReactions(): void
    {
        // Vietnamese Reaction Graph
        $luaNuoc = Material::where('slug', 'nong-nghiep-lua-nuoc')->first();
        $langXa = Material::where('slug', 'lang-xa-tu-tri')->first();
        $trongDong = Material::where('slug', 'van-hoa-trong-dong')->first();
        $thoCung = Material::where('slug', 'tho-cung-to-tien')->first();
        $leHoi = Material::where('slug', 'le-hoi-den-dai')->first();

        if ($luaNuoc && $langXa) {
            MaterialReaction::updateOrCreate(
                ['slug' => 'evolution-lang-xa'],
                [
                    'name' => 'Tiến hóa Làng xã',
                    'inputs' => ['nong-nghiep-lua-nuoc' => 1],
                    'outputs' => ['nong-nghiep-lua-nuoc' => 1, 'lang-xa-tu-tri' => 1],
                    'condition' => 'field_growth > 0.4',
                    'rate' => 0.1
                ]
            );
        }

        if ($trongDong && $thoCung) {
            MaterialReaction::updateOrCreate(
                ['slug' => 'evolution-tho-cung'],
                [
                    'name' => 'Tiến hóa Thờ cúng',
                    'inputs' => ['van-hoa-trong-dong' => 1],
                    'outputs' => ['van-hoa-trong-dong' => 1, 'tho-cung-to-tien' => 1],
                    'condition' => 'field_stability > 0.3',
                    'rate' => 0.08
                ]
            );
        }

        if ($langXa && $leHoi) {
            MaterialReaction::updateOrCreate(
                ['slug' => 'evolution-le-hoi'],
                [
                    'name' => 'Tiến hóa Lễ hội',
                    'inputs' => ['lang-xa-tu-tri' => 1],
                    'outputs' => ['lang-xa-tu-tri' => 1, 'le-hoi-den-dai' => 1],
                    'condition' => 'field_order > 0.5',
                    'rate' => 0.05
                ]
            );
        }
    }

    protected function vietnamese(): array
    {
        return [
            [
                'name' => 'Nông nghiệp Lúa nước',
                'description' => 'Wet rice civilisation based on hydraulic management and communal labor.',
                'ontology' => Material::ONTOLOGY_PHYSICAL,
                'inputs' => ['water' => 0.3],
                'outputs' => ['order' => 0.4, 'growth' => 0.5, 'population' => 0.6],
                'pressures' => ['order' => 0.2, 'growth' => 0.15, 'entropy' => 0.05],
            ],
            [
                'name' => 'Thờ cúng Tổ tiên',
                'description' => 'Ancestor worship reinforcing lineage and social cohesion.',
                'ontology' => Material::ONTOLOGY_SYMBOLIC,
                'inputs' => ['order' => 0.3, 'tradition' => 0.2],
                'outputs' => ['order' => 0.5, 'stability' => 0.4],
                'pressures' => ['order' => 0.3, 'innovation' => -0.15, 'trauma' => 0.05],
            ],
            [
                'name' => 'Làng xã Tự trị',
                'description' => 'Village autonomy: "The King\'s law bows to village custom".',
                'ontology' => Material::ONTOLOGY_INSTITUTIONAL,
                'inputs' => ['population' => 0.5, 'order' => 0.4],
                'outputs' => ['order' => 0.5, 'resilience' => 0.7],
                'pressures' => ['order' => 0.2, 'centralization' => -0.4],
            ],
            [
                'name' => 'Văn hóa Trống đồng',
                'description' => 'Dong Son culture symbolizing power and community.',
                'ontology' => Material::ONTOLOGY_SYMBOLIC,
                'inputs' => ['bronze' => 0.4],
                'outputs' => ['culture' => 0.8, 'authority' => 0.5],
                'pressures' => ['culture' => 0.3, 'authority' => 0.2],
            ],
            [
                'name' => 'Lễ hội Đền đài',
                'description' => 'Communal festivals honoring tutelary deities.',
                'ontology' => Material::ONTOLOGY_SYMBOLIC,
                'inputs' => ['culture' => 0.6, 'stability' => 0.5],
                'outputs' => ['solidarity' => 0.8, 'spirituality' => 0.7],
                'pressures' => ['solidarity' => 0.4, 'entropy' => 0.1],
            ],
        ];
    }

    protected function westernFeudal(): array
    {
        return [
            [
                'name' => 'Feudal Contract',
                'description' => 'A hierarchical system of land ownership and duties.',
                'ontology' => Material::ONTOLOGY_INSTITUTIONAL,
                'outputs' => ['order' => 0.6, 'stability' => 0.4],
                'pressures' => ['order' => 0.4, 'innovation' => -0.2, 'entropy' => -0.1],
            ],
            [
                'name' => 'Code of Chivalry',
                'description' => 'Moral system which goes beyond rules of combat.',
                'ontology' => Material::ONTOLOGY_SYMBOLIC,
                'outputs' => ['honor' => 0.8, 'order' => 0.3],
                'pressures' => ['order' => 0.1, 'stability' => 0.3, 'growth' => -0.05],
            ],
            [
                'name' => 'Guild System',
                'description' => 'Association of artisans or merchants who control the practice of their craft.',
                'ontology' => Material::ONTOLOGY_INSTITUTIONAL,
                'outputs' => ['growth' => 0.5, 'innovation' => 0.2],
                'pressures' => ['growth' => 0.2, 'innovation' => 0.1, 'order' => 0.1],
            ],
            [
                'name' => 'Divine Right',
                'description' => 'The doctrine that kings derive their authority from God.',
                'ontology' => Material::ONTOLOGY_SYMBOLIC,
                'outputs' => ['authority' => 0.9, 'stability' => 0.5],
                'pressures' => ['order' => 0.5, 'innovation' => -0.3, 'rebellion' => -0.2],
            ],
            [
                'name' => 'Crusade Spirit',
                'description' => 'Zealous drive to expand faith and territory.',
                'ontology' => Material::ONTOLOGY_SYMBOLIC,
                'outputs' => ['expansion' => 0.8, 'entropy' => 0.4],
                'pressures' => ['growth' => 0.4, 'entropy' => 0.3, 'stability' => -0.2],
            ],
            [
                'name' => 'Alchemy',
                'description' => 'Proto-scientific tradition seeking the Philosopher\'s Stone.',
                'ontology' => Material::ONTOLOGY_PHYSICAL,
                'outputs' => ['innovation' => 0.6, 'entropy' => 0.2],
                'pressures' => ['innovation' => 0.5, 'order' => -0.1],
            ],
        ];
    }

    protected function easternFeudal(): array
    {
        return [
            [
                'name' => 'Imperial Examination',
                'description' => 'Civil service examination system to select candidates for the state bureaucracy.',
                'ontology' => Material::ONTOLOGY_INSTITUTIONAL,
                'outputs' => ['order' => 0.8, 'meritocracy' => 0.6],
                'pressures' => ['order' => 0.5, 'innovation' => -0.1, 'stability' => 0.4],
            ],
            [
                'name' => 'Silk Road Trade',
                'description' => 'Network of trade routes connecting the East and West.',
                'ontology' => Material::ONTOLOGY_PHYSICAL,
                'outputs' => ['growth' => 0.9, 'cultural_exchange' => 0.7],
                'pressures' => ['growth' => 0.6, 'entropy' => 0.2, 'innovation' => 0.4],
            ],
            [
                'name' => 'Martial Arts Sects',
                'description' => 'Independent organizations of martial artists, often operating outside state law.',
                'ontology' => Material::ONTOLOGY_INSTITUTIONAL,
                'outputs' => ['chaos' => 0.4, 'honor' => 0.5],
                'pressures' => ['order' => -0.3, 'entropy' => 0.3, 'rebellion' => 0.2],
            ],
            [
                'name' => 'Mandate of Heaven',
                'description' => 'The idea that there could be only one legitimate ruler of China at a time, and that this ruler had the blessing of the gods.',
                'ontology' => Material::ONTOLOGY_SYMBOLIC,
                'outputs' => ['authority' => 1.0, 'stability' => 0.6],
                'pressures' => ['order' => 0.6, 'rebellion' => -0.4, 'entropy' => -0.2],
            ],
            [
                'name' => 'Feng Shui',
                'description' => 'Practice of arranging the pieces in living spaces in order to create balance with the natural world.',
                'ontology' => Material::ONTOLOGY_SYMBOLIC,
                'outputs' => ['harmony' => 0.7, 'superstition' => 0.5],
                'pressures' => ['stability' => 0.3, 'innovation' => -0.2],
            ],
        ];
    }

    protected function futuristic(): array
    {
        return [
            [
                'name' => 'Digital Tech',
                'ontology' => Material::ONTOLOGY_PHYSICAL,
                'outputs' => ['innovation' => 1, 'entropy' => 0.3, 'order' => 0.2],
                'pressures' => ['innovation' => 0.4, 'entropy' => 0.2, 'order' => 0.1],
            ],
        ];
    }

    protected function special(): array
    {
        return [
            [
                'name' => 'Hydro Essence',
                'description' => 'Primordial essence of water, bringing both life and overwhelming cataclysm.',
                'ontology' => Material::ONTOLOGY_PHYSICAL,
                'inputs' => [],
                'outputs' => ['entropy' => 0.8, 'growth' => 0.4],
                'pressures' => ['entropy' => 0.6, 'trauma' => 0.4, 'order' => -0.2],
            ],
            [
                'name' => 'Aether Shard',
                'description' => 'Crystallized magic or advanced energy, driving enlightenment and stability.',
                'ontology' => Material::ONTOLOGY_SYMBOLIC,
                'inputs' => [],
                'outputs' => ['innovation' => 0.9, 'order' => 0.7],
                'pressures' => ['innovation' => 0.5, 'order' => 0.4, 'growth' => 0.3],
            ],
            [
                'name' => 'Void Dust',
                'description' => 'Residual energy from the collapse, inducing fear and decay.',
                'ontology' => Material::ONTOLOGY_SYMBOLIC,
                'inputs' => [],
                'outputs' => ['entropy' => 1.0, 'trauma' => 1.0],
                'pressures' => ['entropy' => 0.8, 'trauma' => 0.6, 'order' => -0.5, 'growth' => -0.3],
            ],
        ];
    }
}
