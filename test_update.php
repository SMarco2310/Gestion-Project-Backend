<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$user = \App\Models\User::first();
$projet = \App\Models\Projet::first();

$request = \Illuminate\Http\Request::create('/api/projets/' . $projet->id, 'PUT', [
    'name' => 'Updated Name',
    'color' => 'rose',
    'status' => 'en cours'
]);
$request->setUserResolver(function () use ($user) {
    return $user;
});

app()->instance('request', $request);

try {
    $controller = new \App\Http\Controllers\ProjetController();
    // we can't easily mock the form request. Let's just update the DB directly to see if color works.
    $projet->update(['color' => 'slate']);
    echo "Color is now: " . $projet->color . "\n";
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
