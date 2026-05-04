<?php

/*
|--------------------------------------------------------------------------
| WorldOS Configuration — Modular Merger
|--------------------------------------------------------------------------
|
| This file merges all domain-specific WorldOS config files into a single
| 'worldos' namespace. Every config('worldos.xxx') call continues to work
| unchanged. Domain configs are in separate files for maintainability.
|
| Existing separate files (loaded by Laravel automatically):
|   - worldos_genres.php
|   - worldos_genre_dynamics.php
|   - worldos_narrative.php (narrative schedule config)
|   - worldos_engine_products.php
|
*/

return array_merge(
    require __DIR__ . '/worldos_simulation.php',
    require __DIR__ . '/worldos_social.php',
    require __DIR__ . '/worldos_intelligence.php',
    require __DIR__ . '/worldos_institutions.php',
    require __DIR__ . '/worldos_knowledge.php',
    require __DIR__ . '/worldos_observer.php',
);
