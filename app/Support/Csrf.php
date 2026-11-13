<?php
declare(strict_types=1);

namespace App\Support;

final class Csrf
{
    public static function token(): string
    {
        $token = Session::get('_csrf');
        if (!is_string($token) || $token === '') {
            $token = bin2hex(random_bytes(32));
            Session::set('_csrf', $token);
        }
        return $token;
    }

    public static function verify(): void
    {
        $stored    = Session::get('_csrf');
        $submitted = $_POST['csrf_token'] ?? null;

        if (!is_string($stored) || $stored === ''
            || !is_string($submitted)
            || !hash_equals($stored, $submitted)) {
            throw new HttpException(403, 'Invalid or missing CSRF token. Go back, reload the page and try again.');
        }
    }
}
