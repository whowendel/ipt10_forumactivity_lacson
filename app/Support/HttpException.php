<?php
declare(strict_types=1);

namespace App\Support;

use RuntimeException;

final class HttpException extends RuntimeException
{
    public function __construct(
        public readonly int $status,
        string $message,
        public readonly ?string $backUrl = null,
    ) {
        parent::__construct($message);
    }
}
