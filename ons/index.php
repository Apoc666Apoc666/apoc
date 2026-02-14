<?php
declare(strict_types=1);

require_once __DIR__ . '/app/core/bootstrap.php';

use App\Core\Router;
use App\Core\Response;

$router = new Router();

/**
 * Routes (minimal). Later splitsen per module.
 * Let op: /ai wordt extern via reverse proxy; dus hier niet afhandelen.
 * /admin gaat naar Pi-hole via reverse proxy; dus hier niet afhandelen.
 */

$router->get('/', 'App\\Modules\\Home\\Controller@index');

$router->get('/login', 'App\\Modules\\Auth\\Controller@loginForm');
$router->post('/login', 'App\\Modules\\Auth\\Controller@loginSubmit');
$router->post('/logout', 'App\\Modules\\Auth\\Controller@logout');

$router->get('/register', 'App\\Modules\\Auth\\Controller@registerForm');
$router->post('/register', 'App\\Modules\\Auth\\Controller@registerSubmit');

$router->get('/profile', 'App\\Modules\\Profile\\Controller@show');
$router->post('/profile', 'App\\Modules\\Profile\\Controller@update');

$router->get('/mood', 'App\\Modules\\Mood\\Controller@show');
$router->post('/mood', 'App\\Modules\\Mood\\Controller@set');

$router->get('/couple/invite', 'App\\Modules\\Couple\\Controller@inviteForm');
$router->post('/couple/invite', 'App\\Modules\\Couple\\Controller@inviteSubmit');

$router->get('/couple/accept', 'App\\Modules\\Couple\\Controller@acceptForm');   // ?token=...
$router->post('/couple/accept', 'App\\Modules\\Couple\\Controller@acceptSubmit'); // token in POST

$router->get('/cards', 'App\\Modules\\Cards\\Controller@show');
$router->post('/cards/init', 'App\\Modules\\Cards\\Controller@initDeck');

$router->post('/cards/draw', 'App\\Modules\\Cards\\Controller@draw');
$router->post('/cards/return', 'App\\Modules\\Cards\\Controller@returnToDeck');
$router->post('/cards/complete', 'App\\Modules\\Cards\\Controller@complete');

$router->get('/chaos', 'App\\Modules\\Chaos\\Controller@show');
$router->post('/chaos/toggle', 'App\\Modules\\Chaos\\Controller@toggle');

$router->get('/gallery', [\App\Modules\Gallery\Controller::class, 'index']);
$router->get('/gallery/upload', [\App\Modules\Gallery\Controller::class, 'uploadForm']);
$router->post('/gallery/upload', [\App\Modules\Gallery\Controller::class, 'upload']);
$router->get('/gallery/view', [\App\Modules\Gallery\Controller::class, 'view']);
$router->get('/gallery/thumb', [\App\Modules\Gallery\Controller::class, 'thumb']);



$router->dispatch($_SERVER['REQUEST_METHOD'] ?? 'GET', $_SERVER['REQUEST_URI'] ?? '/');
Response::send();
