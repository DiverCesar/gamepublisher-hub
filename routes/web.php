<?php
declare(strict_types=1);

use App\Controllers\DashboardController;
use App\Controllers\GameController;
use App\Core\Router;

$router = new Router();

$router->get('/', function () {
    redirect('/dashboard');
});

$router->get('/dashboard', [DashboardController::class, 'index']);
$router->get('/games', [GameController::class, 'index']);
$router->get('/games/create', [GameController::class, 'create']);
$router->post('/games/store', [GameController::class, 'store']);
$router->get('/games/show', [GameController::class, 'show']);
$router->get('/games/edit', [GameController::class, 'edit']);
$router->post('/games/update', [GameController::class, 'update']);
$router->post('/games/destroy', [GameController::class, 'destroy']);

$router->dispatch();
