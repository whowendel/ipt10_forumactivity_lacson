<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Models\CommentRepository;
use App\Models\DiscussionRepository;
use App\Support\Auth;
use App\Support\Csrf;
use App\Support\HttpException;
use App\Support\Session;
use App\Support\View;

final class CommentController extends Controller
{
    public function __construct(
        private readonly DiscussionRepository $discussions,
        private readonly CommentRepository $comments,
        private readonly Auth $auth,
        private readonly array $config,
    ) {
    }

    public function storeTopLevel(string $rawDiscussionId): void
    {
        $this->store($rawDiscussionId, null);
    }

    public function storeReply(string $rawDiscussionId, string $rawParentId): void
    {
        $this->store($rawDiscussionId, $rawParentId);
    }

    private function store(string $rawDiscussionId, ?string $rawParentId): void
    {

        $userId = $this->auth->id();
        if ($userId === null) {
            Session::flash('Please log in to post a reply.');
            View::redirect('/login');
        }

        Csrf::verify();

        $discussionId = $this->parseId($rawDiscussionId);
        $parentId     = $rawParentId === null ? null : $this->parseId($rawParentId);
        $back         = '/discussions/' . $discussionId;

        if ($this->discussions->find($discussionId) === null) {
            throw new HttpException(404, 'Discussion not found.');
        }

        if ($parentId !== null) {
            if ($this->comments->findInDiscussion($parentId, $discussionId) === null) {
                throw new HttpException(422, 'That comment does not belong to this discussion.', $back);
            }
            $limit       = (int) $this->config['max_reply_depth'];
            $parentDepth = $this->comments->depthOf($parentId, $limit);
            if ($parentDepth === null || $parentDepth >= $limit) {
                throw new HttpException(422, 'This thread has reached the maximum reply depth.', $back);
            }
        }

        $body = trim(str_replace("\r\n", "\n", $this->input('body')));
        $max  = (int) $this->config['comment_max_length'];

        if (!mb_check_encoding($body, 'UTF-8')) {
            throw new HttpException(422, 'The comment contains invalid characters.', $back);
        }
        if ($body === '') {
            throw new HttpException(422, 'The comment cannot be empty.', $back);
        }
        if (mb_strlen($body, 'UTF-8') > $max) {
            throw new HttpException(422, 'The comment is too long (maximum ' . $max . ' characters).', $back);
        }

        $newId = $this->comments->create($discussionId, $parentId, $userId, $body);

        View::redirect($back . '#comment-' . $newId);
    }
}
