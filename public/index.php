<?php

declare(strict_types=1);

use App\Controllers\AuthController;
use App\Controllers\AvatarController;
use App\Controllers\CommentController;
use App\Controllers\DiscussionController;
use App\Models\CommentRepository;
use App\Models\DiscussionRepository;
use App\Models\UserRepository;
use App\Services\AvatarGenerator;
use App\Services\CommentTree;
use App\Support\Auth;
use App\Support\Database;
use App\Support\HttpException;
use App\Support\Session;
use App\Support\View;
use Bramus\Router\Router;

if (PHP_SAPI === 'cli-server') {
    $file = __DIR__ . parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    if (is_file($file) && realpath($file) !== __FILE__) {
        return false;
    }
    $_SERVER['SCRIPT_NAME'] = '/index.php';
}

$root = dirname(__DIR__);

if (!is_file($root . '/vendor/autoload.php')) {
    http_response_code(500);
    exit('Dependencies are missing. Run "composer install" in the project folder.');
}
require $root . '/vendor/autoload.php';

header("Content-Security-Policy: default-src 'self'; frame-ancestors 'none'; base-uri 'self'; form-action 'self'");
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Referrer-Policy: same-origin');

const STATUS_TEXT = [
    400 => 'Bad Request',
    403 => 'Forbidden',
    404 => 'Not Found',
    422 => 'Unprocessable Content',
    500 => 'Server Error',
];

$view = null;

try {
    $config = require $root . '/config/app.php';

    Session::start();
    $pdo = Database::connect($root . '/config');

    $users       = new UserRepository($pdo);
    $discussions = new DiscussionRepository($pdo);
    $comments    = new CommentRepository($pdo);

    $auth = new Auth($users);
    $view = new View($root . '/app/Views', $auth, $config['app_name']);
    $tree = new CommentTree(
        $config['indent_cap'],
        $config['max_reply_depth'],
        $config['max_render_depth'],
        $config['max_render_nodes'],
    );

    $router = new Router();
    $registerRoutes = require $root . '/config/routes.php';
    $registerRoutes(
        $router,
        new DiscussionController($discussions, $comments, $tree, $view, $config),
        new AuthController($users, $auth, $view),
        new CommentController($discussions, $comments, $auth, $config),
        new AvatarController(new AvatarGenerator()),
    );
    $router->run();
} catch (HttpException $e) {
    $view->render('error', [
        'page_title'  => (string) $e->status,
        'status'      => $e->status,
        'status_text' => STATUS_TEXT[$e->status] ?? 'Error',
        'message'     => $e->getMessage(),
        'back_url'    => $e->backUrl ?? '',
    ], $e->status);
} catch (Throwable $e) {
    error_log(get_class($e) . ': ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
    http_response_code(500);
    header('Content-Type: text/plain; charset=UTF-8');
    echo "500 Server Error\n\nSomething went wrong. Check the PHP error log.\n";
}
