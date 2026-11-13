<?php
declare(strict_types=1);

namespace App\Support;

use PDO;
use RuntimeException;

final class Database
{
    public static function connect(string $configDir): PDO
    {
        $file = $configDir . '/database.local.php';
        if (!is_file($file)) {
            throw new RuntimeException(
                'Missing config/database.local.php. Copy config/database.example.php and fill it in.'
            );
        }
        $cfg = require $file;

        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
            $cfg['host'],
            (int) $cfg['port'],
            $cfg['database']
        );

        $pdo = new PDO($dsn, $cfg['username'], $cfg['password'], [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,

            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);

        $pdo->exec("SET time_zone = '+00:00'");

        return $pdo;
    }
}
