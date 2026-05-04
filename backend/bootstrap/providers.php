<?php

return [
    App\Providers\AppServiceProvider::class ,
    App\Providers\RepositoryServiceProvider::class ,
    App\Modules\Simulation\Providers\SimulationServiceProvider::class ,
    App\Modules\Narrative\Providers\NarrativeServiceProvider::class ,
    App\Modules\Intelligence\Providers\IntelligenceServiceProvider::class ,
    App\Modules\Institutions\Providers\InstitutionsServiceProvider::class ,
    App\Modules\Psychology\Providers\PsychologyServiceProvider::class ,
    App\Modules\SocialGraph\Providers\SocialGraphServiceProvider::class ,
    App\Modules\World\Providers\WorldServiceProvider::class ,
    App\Modules\WorldOS\Providers\WorldOSServiceProvider::class ,
    App\Modules\Knowledge\Providers\KnowledgeServiceProvider::class ,
    App\Modules\Achievement\Providers\AchievementServiceProvider::class ,
    Laravel\Sanctum\SanctumServiceProvider::class
];
