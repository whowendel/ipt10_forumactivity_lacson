<?php
declare(strict_types=1);

namespace App\Models;

use PDO;

final class DiscussionRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function all(): array
    {
        $stmt = $this->pdo->query(
            'SELECT d.id, d.title, d.created_at, u.username AS author,
                    (SELECT COUNT(*) FROM comments c WHERE c.discussion_id = d.id) AS comment_count
             FROM discussions d
             JOIN users u ON u.id = d.user_id
             ORDER BY d.created_at DESC, d.id DESC'
        );
        return $stmt->fetchAll();
    }

    public function find(int $id): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT d.id, d.title, d.body, d.created_at, u.username AS author
             FROM discussions d
             JOIN users u ON u.id = d.user_id
             WHERE d.id = :id'
        );
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }
}
