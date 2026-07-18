<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

$user = App\Models\User::first();
auth()->login($user);

$request = Illuminate\Http\Request::create('/api/projets/1', 'PUT', [
    'name' => 'Test',
    'status' => 'à faire',
    'team_ids' => [],
    'user_ids' => []
]);
$request->headers->set('Accept', 'application/json');
$response = $kernel->handle($request);
echo $response->getContent();
