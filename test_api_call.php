<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$user = \App\Models\User::first();
$projet = \App\Models\Projet::first();

// Generate a valid token for the user
$token = $user->createToken('test-token')->plainTextToken;

echo "Project ID: {$projet->id}\n";
echo "Token: $token\n";
