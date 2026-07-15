<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$projets = App\Models\Projet::whereNull('reference_code')->orWhere('reference_code', '')->get();
foreach ($projets as $projet) {
    // Re-trigger saving to generate reference_code
    // Actually we can just assign it
    $prefix = 'PRJ-';
    $paddingLength = 4;
    $lastRecord = App\Models\Projet::whereNotNull('reference_code')
        ->where('reference_code', 'like', $prefix . '%')
        ->orderByRaw('CAST(SUBSTRING(reference_code, ' . (strlen($prefix) + 1) . ') AS UNSIGNED) DESC')
        ->first();
    if (! $lastRecord || ! $lastRecord->reference_code) {
        $projet->reference_code = $prefix . str_pad(1, $paddingLength, '0', STR_PAD_LEFT);
    } else {
        $lastNumber = (int) substr($lastRecord->reference_code, strlen($prefix));
        $newNumber = $lastNumber + 1;
        $projet->reference_code = $prefix . str_pad($newNumber, $paddingLength, '0', STR_PAD_LEFT);
    }
    $projet->save();
}

$taches = App\Models\Tache::whereNull('reference_code')->orWhere('reference_code', '')->get();
foreach ($taches as $tache) {
    $prefix = 'TSK-';
    $paddingLength = 4;
    $lastRecord = App\Models\Tache::whereNotNull('reference_code')
        ->where('reference_code', 'like', $prefix . '%')
        ->orderByRaw('CAST(SUBSTRING(reference_code, ' . (strlen($prefix) + 1) . ') AS UNSIGNED) DESC')
        ->first();
    if (! $lastRecord || ! $lastRecord->reference_code) {
        $tache->reference_code = $prefix . str_pad(1, $paddingLength, '0', STR_PAD_LEFT);
    } else {
        $lastNumber = (int) substr($lastRecord->reference_code, strlen($prefix));
        $newNumber = $lastNumber + 1;
        $tache->reference_code = $prefix . str_pad($newNumber, $paddingLength, '0', STR_PAD_LEFT);
    }
    $tache->save();
}
echo "Done.\n";
