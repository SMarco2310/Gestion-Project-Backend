<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

$response = $kernel->handle(
    $request = Illuminate\Http\Request::create(
        '/api/workspaces/019f2e32-0000-0000-0000-000000000000/kanban-columns',
        'PUT',
        ['kanban_columns' => ['Inbox', 'To Do', 'Nouvelle colonne'], 'kanban_colors' => []]
    )
);

echo $response->getContent();
