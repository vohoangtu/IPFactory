<?php

namespace Database\Seeders;

use App\Modules\Narrative\Models\HistorianProfile;
use Illuminate\Database\Seeder;

class HistorianProfileSeeder extends Seeder
{
    public function run(): void
    {
        $profiles = [
            ['name' => 'Court Historian', 'personality' => 'formal, loyal to power', 'bias' => 'favor rulers and stability', 'writing_style' => 'pompous, ceremonial'],
            ['name' => 'Cynical Historian', 'personality' => 'skeptical, ironic', 'bias' => 'doubt grand narratives', 'writing_style' => 'dry, sarcastic'],
            ['name' => 'Mad Prophet', 'personality' => 'visionary, apocalyptic', 'bias' => 'see omens and doom', 'writing_style' => 'oracular, fragmented'],
            ['name' => 'Religious Scribe', 'personality' => 'devout, moralizing', 'bias' => 'divine providence', 'writing_style' => 'hagiographic, moral'],
        ];

        foreach ($profiles as $p) {
            HistorianProfile::firstOrCreate(
                ['name' => $p['name']],
                $p
            );
        }
    }
}
