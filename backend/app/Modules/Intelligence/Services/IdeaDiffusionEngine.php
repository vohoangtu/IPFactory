<?php

namespace App\Modules\Intelligence\Services;

use App\Modules\World\Models\Universe;
use App\Modules\Intelligence\Entities\ActorState;
use Illuminate\Support\Facades\Log;

/**
 * Phase 50.1: Idea Diffusion Engine (Tầng Tư Tưởng) 🌿💡
 * 
 * Quản lý sự nảy sinh của các Ý tưởng (Ideas) từ Vĩ nhân và sự lan tỏa của chúng.
 */
class IdeaDiffusionEngine
{
    private const OPPOSITIONS = [
        'rationalism'  => 'spirituality',
        'spirituality' => 'rationalism',
        'expansionism' => 'order',
        'order'        => 'expansionism',
        'mercantilism' => 'spirituality', // Material vs Spiritual
    ];
    /**
     * Cập nhật sự lan tỏa của các ý tưởng.
     */
    public function step(Universe $universe): void
    {
        $stateVector = $universe->state_vector;
        $ideas = $stateVector['ideas'] ?? [];
        $schools = $stateVector['schools'] ?? [];

        foreach ($ideas as $id => &$idea) {
            $this->evolveIdea($idea, $universe);
            
            // Phase 54: Opposing Logic - Ideological Competition
            $this->applyCompetition($idea, $ideas, $universe);

            // Phase 54: School-to-Institution Crystallization (Crystallization at Influence > 0.8)
            if (($idea['influence_score'] ?? 0) > 0.8 && isset($idea['school_id'])) {
                $this->triggerInstitutionalization($id, $idea, $schools, $universe);
            }

            // Nếu đủ followers, hình thành School (Phase 50.2)
            if ($idea['followers'] >= 100 && !isset($idea['school_id'])) {
                $this->foundSchool($idea, $schools, $universe);
            }
        }

        $stateVector['ideas'] = $ideas;
        $stateVector['schools'] = $schools;
        
        // Cập nhật Hệ tư tưởng trội (Dominant Ideology)
        $stateVector['dominant_ideology'] = $this->calculateDominantIdeology($schools);
        
        $universe->state_vector = $stateVector;
    }

    /**
     * Vĩ nhân gieo mầm một ý tưởng mới.
     */
    public function sowIdea(Universe $universe, ActorState $actor): void
    {
        $stateVector = $universe->state_vector;
        $ideas = $stateVector['ideas'] ?? [];

        $type = $actor->heroicType;
        $theme = match($type) {
            'SCIENTIST' => 'rationalism',
            'GENERAL'   => 'expansionism',
            'RULER'     => 'order',
            'PROPHET'   => 'spirituality',
            'ARTIST'    => 'humanism',
            'MERCHANT'  => 'mercantilism',
            default     => 'survivalism'
        };

        $ideaId = "idea_" . bin2hex(random_bytes(3));
        $ideas[$ideaId] = [
            'id' => $ideaId,
            'origin_actor_id' => $actor->id,
            'theme' => $theme,
            'influence_score' => 0.1,
            'followers' => 1,
            'birth_tick' => $universe->current_tick ?? 0
        ];

        $stateVector['ideas'] = $ideas;
        $universe->state_vector = $stateVector;
        
        Log::info("IDEA SOWED: A new idea of '{$theme}' has been planted by {$actor->name}");
    }

    private function evolveIdea(array &$idea, Universe $universe): void
    {
        $fields = $universe->state_vector['fields'] ?? [];
        
        // Phase 54: Field-aware growth. Knowledge boosts rationalism, Meaning boosts spirituality, etc.
        $theme = $idea['theme'] ?? 'survivalism';
        $fieldKey = $this->mapThemeToField($theme);
        $fieldBoost = isset($fields[$fieldKey]) ? ($fields[$fieldKey] * 0.05) : 0.0;

        // Ý tưởng lan tỏa theo thời gian và dựa trên mức độ ổn định của Universe
        $growth = (0.01 + $fieldBoost) * (1.1 - ($universe->entropy ?? 0.5));
        $idea['influence_score'] = min(1.0, $idea['influence_score'] + $growth);
        
        // Followers tăng trưởng (proxy) - influenced by influence_score
        $followerGrowth = rand(1, 5) * (1.0 + $idea['influence_score']);
        $idea['followers'] += (int)$followerGrowth;
    }

    private function applyCompetition(array &$idea, array $allIdeas, Universe $universe): void
    {
        $theme = $idea['theme'] ?? null;
        if (!$theme || !isset(self::OPPOSITIONS[$theme])) return;

        $oppositeTheme = self::OPPOSITIONS[$theme];
        foreach ($allIdeas as $other) {
            if ($other['theme'] === $oppositeTheme) {
                // Drain influence based on mutual strength
                $drain = ($other['influence_score'] ?? 0) * 0.005;
                $idea['influence_score'] = max(0.01, $idea['influence_score'] - $drain);
            }
        }
    }

    private function triggerInstitutionalization(string $ideaId, array &$idea, array &$schools, Universe $universe): void
    {
        $schoolId = $idea['school_id'];
        if (!isset($schools[$schoolId]) || ($schools[$schoolId]['status'] === 'institutionalized')) {
            return;
        }

        // Potential for crystallization into an Institution
        $schools[$schoolId]['status'] = 'institutionalized';
        $idea['status'] = 'institutionalized';

        Log::info("COGNITIVE CRYSTALLIZATION: School '{$schools[$schoolId]['name']}' has successfully institutionalized due to high ideological influence.");
    }

    private function mapThemeToField(string $theme): string
    {
        return match($theme) {
            'rationalism'  => 'knowledge',
            'expansionism' => 'power',
            'order'        => 'power',
            'spirituality' => 'meaning',
            'humanism'     => 'status',
            'mercantilism' => 'wealth',
            default        => 'survival'
        };
    }

    private function foundSchool(array &$idea, array &$schools, Universe $universe): void
    {
        $schoolId = "school_" . bin2hex(random_bytes(3));
        $names = [
            'rationalism' => "Học phái Lý tính",
            'expansionism' => "Chiến lược Đại đế",
            'order' => "Chủ nghĩa Trật tự",
            'spirituality' => "Giáo phái Khai sáng",
            'humanism' => "Tư tưởng Nhân bản",
            'mercantilism' => "Hội buôn liên vùng"
        ];

        $schools[$schoolId] = [
            'id' => $schoolId,
            'name' => $names[$idea['theme']] ?? "Trường phái " . $idea['theme'],
            'founder_id' => $idea['origin_actor_id'],
            'idea_id' => $idea['id'],
            'idea_theme' => $idea['theme'],
            'influence' => 0.1,
            'status' => 'emerging'
        ];

        $idea['school_id'] = $schoolId;
        Log::info("SCHOOL FOUNDED: {$schools[$schoolId]['name']} has emerged from '{$idea['theme']}'");
    }

    private function calculateDominantIdeology(array $schools): array
    {
        $themeInfluence = [];
        foreach ($schools as $school) {
            $theme = $school['idea_theme'] ?? 'unknown';
            $themeInfluence[$theme] = ($themeInfluence[$theme] ?? 0) + $school['influence'];
        }
        
        arsort($themeInfluence);
        return [
            'dominant_theme' => array_key_first($themeInfluence) ?? 'none',
            'composition' => $themeInfluence
        ];
    }
}

