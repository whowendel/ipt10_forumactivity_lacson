<?php
declare(strict_types=1);

use App\Controllers\AuthController;
use App\Controllers\AvatarController;
use App\Controllers\CommentController;
use App\Controllers\DiscussionController;
use App\Support\HttpException;
use Bramus\Router\Router;

return static function (
    Router $router,
    DiscussionController $discussions,
    AuthController $auth,
    CommentController $comments,
    AvatarController $avatars,
): void {

    $router->get('/avatar/([A-Za-z0-9_]+)\.svg', fn (string $username) => $avatars->show($username));

    $router->get('/', fn () => $discussions->index());
    $router->get('/discussions/([^/]+)', fn (string $id) => $discussions->show($id));

    $router->get('/register', fn () => $auth->showRegister());
    $router->post('/register', fn () => $auth->register());
    $router->get('/login', fn () => $auth->showLogin());
    $router->post('/login', fn () => $auth->login());
    $router->post('/logout', fn () => $auth->logout());

    $router->post('/discussions/([^/]+)/comments', fn (string $id) => $comments->storeTopLevel($id));
    $router->post(
        '/discussions/([^/]+)/comments/([^/]+)/replies',
        fn (string $id, string $commentId) => $comments->storeReply($id, $commentId)
    );

    $router->set404(function (): void {
        throw new HttpException(404, 'Page not found.');
    });
};
