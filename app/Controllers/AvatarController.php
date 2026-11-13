<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Services\AvatarGenerator;
use App\Support\HttpException;

final class AvatarController extends Controller
{
    public function __construct(private readonly AvatarGenerator $generator)
    {
    }

    public function show(string $username): void
    {

        if (!preg_match('/\A[A-Za-z0-9_]{3,30}\z/', $username)) {
            throw new HttpException(404, 'Avatar not found.');
        }

        $svg = $this->generator->render($username);

        header('Content-Type: image/svg+xml; charset=UTF-8');
        header('Cache-Control: public, max-age=86400, immutable');
        echo $svg;
    }
}
