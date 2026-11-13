<?php
declare(strict_types=1);

namespace App\Services;

use DateTimeImmutable;
use DateTimeZone;

final class CommentTree
{
    private int $nodeCount = 0;
    private bool $truncated = false;

    private array $byParent = [];

    public function __construct(
        private readonly int $indentCap,
        private readonly int $maxReplyDepth,
        private readonly int $maxRenderDepth,
        private readonly int $maxRenderNodes,
    ) {
    }

    public function build(array $rows, int $discussionId): array
    {
        $this->nodeCount = 0;
        $this->truncated = false;
        $this->byParent  = [];

        foreach ($rows as $row) {
            $key = $row['parent_id'] === null ? 'root' : (int) $row['parent_id'];
            $this->byParent[$key][] = $row;
        }

        $comments = $this->children('root', 0, [], $discussionId);

        return [
            'comments'  => $comments,
            'count'     => $this->nodeCount,
            'truncated' => $this->truncated,
        ];
    }

    private function children(int|string $parentKey, int $depth, array $path, int $discussionId): array
    {
        $nodes = [];

        foreach ($this->byParent[$parentKey] ?? [] as $row) {
            $id = (int) $row['id'];

            if (isset($path[$id]) || $depth > $this->maxRenderDepth || $this->nodeCount >= $this->maxRenderNodes) {
                $this->truncated = true;
                continue;
            }
            $this->nodeCount++;

            $kids = $this->children($id, $depth + 1, $path + [$id => true], $discussionId);
            $time = new DateTimeImmutable($row['created_at'], new DateTimeZone('UTC'));

            $nodes[] = [
                'id'              => $id,
                'author'          => $row['author'],
                'body'            => $row['body'],
                'depth'           => $depth,
                'created_iso'     => $time->format('Y-m-d\TH:i:s\Z'),
                'created_label'   => $time->format('Y-m-d H:i') . ' UTC',
                'children'        => $kids,
                'has_children'    => $kids !== [],

                'children_capped' => $depth >= $this->indentCap,
                'can_be_replied'  => $depth < $this->maxReplyDepth,
                'reply_action'    => '/discussions/' . $discussionId . '/comments/' . $id . '/replies',
            ];
        }

        return $nodes;
    }
}
