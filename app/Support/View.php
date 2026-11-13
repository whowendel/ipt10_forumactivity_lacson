<?php
declare(strict_types=1);

namespace App\Support;

use Mustache_Engine;
use Mustache_Loader_FilesystemLoader;

final class View
{
    private Mustache_Engine $mustache;

    public function __construct(string $viewDir, private readonly Auth $auth, private readonly string $appName)
    {
        $this->mustache = new Mustache_Engine([
            'loader'           => new Mustache_Loader_FilesystemLoader($viewDir),
            'partials_loader'  => new Mustache_Loader_FilesystemLoader($viewDir . '/partials'),
            'charset'          => 'UTF-8',
            'entity_flags'     => ENT_QUOTES | ENT_SUBSTITUTE,
            'strict_callables' => true,
        ]);
    }

    public function render(string $template, array $data = [], int $status = 200): void
    {
        $user = $this->auth->user();

        $shared = [
            'app_name'     => $this->appName,
            'logged_in'    => $user !== null,
            'current_user' => $user === null ? '' : $user['username'],
            'csrf_token'   => Csrf::token(),
            'flash'        => Session::pullFlash() ?? '',
        ];

        http_response_code($status);
        header('Content-Type: text/html; charset=UTF-8');
        echo $this->mustache->render($template, $data + $shared);
    }

    public static function redirect(string $path): never
    {
        header('Location: ' . $path, true, 303);
        exit;
    }
}
