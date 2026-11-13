<?php
declare(strict_types=1);

namespace App\Models;

use PDO;

final class UserRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT id, username, email FROM users WHERE id = :id');
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public function findForLogin(string $username): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, username, password_hash FROM users WHERE username = :username'
        );
        $stmt->execute(['username' => $username]);
        return $stmt->fetch() ?: null;
    }

    public function usernameExists(string $username): bool
    {
        $stmt = $this->pdo->prepare('SELECT 1 FROM users WHERE username = :username');
        $stmt->execute(['username' => $username]);
        return $stmt->fetchColumn() !== false;
    }

    public function emailExists(string $email): bool
    {
        $stmt = $this->pdo->prepare('SELECT 1 FROM users WHERE email = :email');
        $stmt->execute(['email' => $email]);
        return $stmt->fetchColumn() !== false;
    }

    public function create(string $username, string $email, string $passwordHash): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO users (username, email, password_hash, created_at)
             VALUES (:username, :email, :password_hash, UTC_TIMESTAMP())'
        );
        $stmt->execute([
            'username'      => $username,
            'email'         => $email,
            'password_hash' => $passwordHash,
        ]);
        return (int) $this->pdo->lastInsertId();
    }
}
