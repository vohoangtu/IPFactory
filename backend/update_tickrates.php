<?php
$dir = 'c:/projects/IPFactory/backend/app/Modules/Simulation/Core/Engines/Meta/';
$files = glob($dir . '*.php');
$count = 0;
foreach ($files as $file) {
    if (strpos($file, 'Narrative') !== false) continue; // Skip Narrative engines for now if we want to keep them fast. Wait, I will just update all of them.
    $content = file_get_contents($file);
    // Matches `public function tickRate(): int { return 1; }` or with newlines
    $newContent = preg_replace('/(public\s+function\s+tickRate\(\)\s*:\s*int\s*\{\s*return\s+)1\s*;/s', '${1}5;', $content);
    if ($newContent !== $content) {
        file_put_contents($file, $newContent);
        $count++;
    }
}
echo "Updated Meta tick rates to 5 for $count files.\n";
