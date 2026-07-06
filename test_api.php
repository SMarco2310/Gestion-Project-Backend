<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$user = \App\Models\User::first();
$projet = clone \App\Models\Projet::first();

$request = \Illuminate\Http\Request::create('/api/projets/' . $projet->id, 'PUT', [
    'name' => 'Updated Name',
    'color' => 'blue',
    'status' => 'en cours'
]);
$request->setUserResolver(function () use ($user) {
    return $user;
});

app()->instance('request', $request);

try {
    $controller = new \App\Http\Controllers\ProjetController();
    $response = $controller->update(app()->make(\App\Http\Requests\UpdateProjetRequest::class), $projet->id);
    echo $response->getContent();
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
