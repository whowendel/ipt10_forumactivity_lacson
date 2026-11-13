<?php
declare(strict_types=1);

namespace App\Models;

use PDO;

final class CommentRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function forDiscussion(int $discussionId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT c.id, c.parent_id, c.body, c.created_at, u.username AS author
             FROM comments c
             JOIN users u ON u.id = c.user_id
             WHERE c.discussion_id = :discussion_id
             ORDER BY c.created_at ASC, c.id ASC'
        );
        $stmt->execute(['discussion_id' => $discussionId]);
        return $stmt->fetchAll();
    }

    public function findInDiscussion(int $commentId, int $discussionId): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, parent_id, discussion_id FROM comments
             WHERE id = :id AND discussion_id = :discussion_id'
        );
        $stmt->execute(['id' => $commentId, 'discussion_id' => $discussionId]);
        return $stmt->fetch() ?: null;
    }

    public function depthOf(int $commentId, int $limit): ?int
    {
        $stmt = $this->pdo->prepare('SELECT parent_id FROM comments WHERE id = :id');
        $depth = 0;
        $current = $commentId;

        while (true) {
            $stmt->execute(['id' => $current]);
            $row = $stmt->fetch();
            if ($row === false) {
                return null;
            }
            if ($row['parent_id'] === null) {
                return $depth;
            }
            $depth++;
            if ($depth > $limit) {
                return null;
            }
            $current = (int) $row['parent_id'];
        }
    }

    public function create(int $discussionId, ?int $parentId, int $userId, string $body): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO comments (discussion_id, parent_id, user_id, body, created_at)
             VALUES (:discussion_id, :parent_id, :user_id, :body, UTC_TIMESTAMP())'
        );
        $stmt->bindValue('discussion_id', $discussionId, PDO::PARAM_INT);
        $stmt->bindValue('parent_id', $parentId, $parentId === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
        $stmt->bindValue('user_id', $userId, PDO::PARAM_INT);
        $stmt->bindValue('body', $body, PDO::PARAM_STR);
        $stmt->execute();
        return (int) $this->pdo->lastInsertId();
    }
}
