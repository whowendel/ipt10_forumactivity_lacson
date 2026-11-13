<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Models\CommentRepository;
use App\Models\DiscussionRepository;
use App\Services\CommentTree;
use App\Support\HttpException;
use App\Support\View;
use DateTimeImmutable;
use DateTimeZone;

final class DiscussionController extends Controller
{
    public function __construct(
        private readonly DiscussionRepository $discussions,
        private readonly CommentRepository $comments,
        private readonly CommentTree $tree,
        private readonly View $view,
        private readonly array $config,
    ) {
    }

    public function index(): void
    {
        $rows = [];
        foreach ($this->discussions->all() as $d) {
            $rows[] = [
                'url'           => '/discussions/' . (int) $d['id'],
                'title'         => $d['title'],
                'author'        => $d['author'],
                'comment_count' => (int) $d['comment_count'],
            ];
        }

        $this->view->render('home', [
            'page_title'      => 'Discussions',
            'discussions'     => $rows,
            'has_discussions' => $rows !== [],
        ]);
    }

    public function show(string $rawId): void
    {

        $id = $this->parseId($rawId);

        $discussion = $this->discussions->find($id);
        if ($discussion === null) {
            throw new HttpException(404, 'Discussion not found.');
        }

        $tree = $this->tree->build($this->comments->forDiscussion($id), $id);

        $created = new DateTimeImmutable($discussion['created_at'], new DateTimeZone('UTC'));

        $this->view->render('discussion', [
            'page_title'     => $discussion['title'],
            'title'          => $discussion['title'],
            'body'           => $discussion['body'],
            'author'         => $discussion['author'],
            'created_iso'    => $created->format('Y-m-d\TH:i:s\Z'),
            'created_label'  => $created->format('Y-m-d H:i') . ' UTC',
            'comments'       => $tree['comments'],
            'comment_count'  => $tree['count'],
            'has_comments'   => $tree['count'] > 0,
            'truncated'      => $tree['truncated'],
            'comment_action' => '/discussions/' . $id . '/comments',
            'max_length'     => $this->config['comment_max_length'],
        ]);
    }
}
