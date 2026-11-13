<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Support\HttpException;

abstract class Controller
{

    protected function parseId(string $raw): int
    {
        if (!ctype_digit($raw) || strlen($raw) > 9 || (int) $raw < 1) {
            throw new HttpException(400, 'Bad request: the ID in the address is not a valid number.');
        }
        return (int) $raw;
    }

    protected function input(string $key): string
    {
        $value = $_POST[$key] ?? '';
        return is_string($value) ? $value : '';
    }
}
