<?php
declare(strict_types=1);

namespace App\Support;

final class Session
{
    public static function start(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        $https = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';

        ini_set('session.use_strict_mode', '1');
        ini_set('session.use_only_cookies', '1');
        session_name('forum_session');
        session_set_cookie_params([
            'lifetime' => 0,
            'path'     => '/',
            'secure'   => $https,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_start();
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return $_SESSION[$key] ?? $default;
    }

    public static function set(string $key, mixed $value): void
    {
        $_SESSION[$key] = $value;
    }

    public static function forget(string $key): void
    {
        unset($_SESSION[$key]);
    }

    public static function regenerate(): void
    {
        session_regenerate_id(true);
    }

    public static function destroy(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', [
                'expires'  => time() - 3600,
                'path'     => $p['path'],
                'secure'   => $p['secure'],
                'httponly' => $p['httponly'],
                'samesite' => $p['samesite'],
            ]);
        }
        session_destroy();
    }

    public static function flash(string $message): void
    {
        $_SESSION['_flash'] = $message;
    }

    public static function pullFlash(): ?string
    {
        $message = $_SESSION['_flash'] ?? null;
        unset($_SESSION['_flash']);
        return is_string($message) ? $message : null;
    }
}
