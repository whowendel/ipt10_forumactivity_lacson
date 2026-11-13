<?php

declare(strict_types=1);

$base = rtrim($argv[1] ?? 'http://localhost:8000', '/');
$root = dirname(__DIR__);

$cfg = require $root . '/config/database.local.php';
$pdo = new PDO(
    sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', $cfg['host'], (int) $cfg['port'], $cfg['database']),
    $cfg['username'],
    $cfg['password'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

$passed = 0;
$failed = 0;

function check(string $label, bool $ok, string $detail = ''): void
{
    global $passed, $failed;
    $ok ? $passed++ : $failed++;
    echo ($ok ? 'PASS  ' : 'FAIL  ') . $label . ($ok || $detail === '' ? '' : '  [' . $detail . ']') . "\n";
}

function commentCount(PDO $pdo): int
{
    return (int) $pdo->query('SELECT COUNT(*) FROM comments')->fetchColumn();
}

final class Client
{
    private CurlHandle $ch;
    public int $status = 0;
    public string $body = '';
    public string $headers = '';

    public function __construct(private readonly string $base)
    {
        $this->ch = curl_init();
        curl_setopt_array($this->ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER         => true,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_COOKIEFILE     => '',
            CURLOPT_PROXY          => '',
            CURLOPT_TIMEOUT        => 15,
        ]);
    }

    public function get(string $path): self
    {
        curl_setopt($this->ch, CURLOPT_HTTPGET, true);
        return $this->send($path);
    }

    public function post(string $path, array $fields): self
    {
        curl_setopt($this->ch, CURLOPT_POST, true);
        curl_setopt($this->ch, CURLOPT_POSTFIELDS, http_build_query($fields));
        return $this->send($path);
    }

    private function send(string $path): self
    {
        curl_setopt($this->ch, CURLOPT_URL, $this->base . $path);
        $raw = curl_exec($this->ch);
        if ($raw === false) {
            fwrite(STDERR, 'Cannot reach ' . $this->base . ' - is the server running? ' . curl_error($this->ch) . "\n");
            exit(2);
        }
        $size          = curl_getinfo($this->ch, CURLINFO_HEADER_SIZE);
        $this->status  = curl_getinfo($this->ch, CURLINFO_RESPONSE_CODE);
        $this->headers = substr($raw, 0, $size);
        $this->body    = substr($raw, $size);
        return $this;
    }

    public function location(): string
    {
        return preg_match('/^Location:\s*(\S+)/mi', $this->headers, $m) ? $m[1] : '';
    }

    public function token(string $path): string
    {
        $this->get($path);
        return preg_match('/name="csrf_token" value="([a-f0-9]{64})"/', $this->body, $m) ? $m[1] : '';
    }

    public function sessionCookie(): string
    {
        foreach (curl_getinfo($this->ch, CURLINFO_COOKIELIST) as $line) {
            $parts = explode("\t", $line);
            if (($parts[5] ?? '') === 'forum_session') {
                return $parts[6] ?? '';
            }
        }
        return '';
    }
}

function commentHtml(string $page, int $id): string
{
    $start = strpos($page, 'id="comment-' . $id . '"');
    if ($start === false) {
        return '';
    }
    $end = strpos($page, '</article>', $start);
    return substr($page, $start, $end === false ? null : $end - $start);
}

function newCommentId(string $location): int
{
    return preg_match('/#comment-(\d+)$/', $location, $m) ? (int) $m[1] : 0;
}

$username = 'tester_' . bin2hex(random_bytes(4));
$password = bin2hex(random_bytes(12));
$member   = new Client($base);
$guest    = new Client($base);

echo "Reading (guest)\n";
$guest->get('/discussions/1');
check('discussion 1 loads for a guest', $guest->status === 200);
check('seed tree shows a depth-4 comment', str_contains(commentHtml($guest->body, 5), 'Depth 4'));
check('guest sees no reply form', !str_contains($guest->body, '/replies"'));
check('timestamps are labelled UTC', (bool) preg_match('/<time datetime="[0-9T:\-]+Z">[^<]+ UTC<\/time>/', $guest->body));
check('missing discussion returns 404', $guest->get('/discussions/999999')->status === 404);
check('malformed ID "abc" returns 400', $guest->get('/discussions/abc')->status === 400);
check('malformed ID "1%20OR%201=1" returns 400', $guest->get('/discussions/1%20OR%201=1')->status === 400);
check('logout by GET is not a route (404)', $guest->get('/logout')->status === 404);

echo "\nRegistration and login\n";
$form = ['username' => $username, 'email' => $username . '@example.test',
         'password' => $password, 'password_confirm' => $password];
$member->post('/register', $form + ['csrf_token' => 'wrong']);
check('registration with a bad CSRF token is rejected (403)', $member->status === 403);
$member->post('/register', $form + ['csrf_token' => $member->token('/register')]);
check('registration succeeds and redirects to /login', $member->status === 303 && $member->location() === '/login');
$stmt = $pdo->prepare('SELECT password_hash FROM users WHERE username = ?');
$stmt->execute([$username]);
$hash = (string) $stmt->fetchColumn();
check('password is stored as a hash, not plaintext', $hash !== $password && password_verify($password, $hash));
$member->post('/register', $form + ['csrf_token' => $member->token('/register')]);
check('duplicate account is rejected (422)', $member->status === 422 && str_contains($member->body, 'already'));

$member->post('/login', ['username' => 'mika', 'password' => '', 'csrf_token' => $member->token('/login')]);
check('seed user with NULL hash cannot log in', $member->status === 422);
$member->post('/login', ['username' => $username, 'password' => 'wrong-' . $password, 'csrf_token' => $member->token('/login')]);
check('incorrect password is rejected (422)', $member->status === 422 && str_contains($member->body, 'Incorrect username or password'));
$member->post('/login', ['username' => $username, 'password' => $password, 'csrf_token' => 'wrong']);
check('login with a bad CSRF token is rejected (403)', $member->status === 403);

$token  = $member->token('/login');
$before = $member->sessionCookie();
$member->post('/login', ['username' => $username, 'password' => $password, 'csrf_token' => $token]);
check('correct login redirects', $member->status === 303);
check('session ID is regenerated on login', $before !== '' && $member->sessionCookie() !== '' && $member->sessionCookie() !== $before);
check('session cookie is HttpOnly and SameSite', (bool) preg_match('/^Set-Cookie: forum_session=.*HttpOnly.*SameSite=Lax/mi', $member->headers));

echo "\nAuthenticated replies (written to discussion 2)\n";
$token = $member->token('/discussions/2');
check('member sees reply forms', str_contains($member->body, '/discussions/2/comments/9/replies'));

$member->post('/discussions/2/comments', ['body' => 'Second top-level comment (smoke test).', 'csrf_token' => $token, 'user_id' => '1']);
$topId = newCommentId($member->location());
check('top-level comment saved, then redirect', $member->status === 303 && $topId > 0);
$html = commentHtml($member->get('/discussions/2')->body, $topId);
check('top-level comment shows depth 0 and the session user as author',
    str_contains($html, 'Depth 0') && str_contains($html, '>' . $username . '<'));
$stmt = $pdo->prepare('SELECT user_id FROM comments WHERE id = ?');
$stmt->execute([$topId]);
check('forged user_id form field is ignored', (int) $stmt->fetchColumn() !== 1);

$parent = 9;
$ids    = [];
for ($depth = 1; $depth <= 12; $depth++) {
    $member->post("/discussions/2/comments/$parent/replies", ['body' => "Chain reply at depth $depth.", 'csrf_token' => $token]);
    $parent = newCommentId($member->location());
    if ($member->status !== 303 || $parent === 0) {
        break;
    }
    $ids[$depth] = $parent;
}
check('replies can be nested down to depth 12', count($ids) === 12);
$page = $member->get('/discussions/2')->body;
check('depth-5 reply shows "Depth 5" and its author',
    isset($ids[5]) && str_contains(commentHtml($page, $ids[5]), 'Depth 5') && str_contains(commentHtml($page, $ids[5]), '>' . $username . '<'));
check('indentation is capped after depth 8, labels stay accurate',
    isset($ids[8], $ids[10])
    && str_contains($page, 'class="replies replies-capped" id="replies-' . $ids[8] . '"')
    && !str_contains($page, 'class="replies replies-capped" id="replies-' . ($ids[7] ?? 0) . '"')
    && str_contains(commentHtml($page, $ids[10]), 'Depth 10'));
$count = commentCount($pdo);
$member->post("/discussions/2/comments/$parent/replies", ['body' => 'Too deep.', 'csrf_token' => $token]);
check('reply beyond the depth limit is rejected (422), no row', $member->status === 422 && commentCount($pdo) === $count);

echo "\nRejected writes (no row may be created)\n";
$count = commentCount($pdo);
$member->post('/discussions/2/comments', ['body' => 'Bad token.', 'csrf_token' => str_repeat('0', 64)]);
check('invalid CSRF token is rejected (403)', $member->status === 403 && commentCount($pdo) === $count);
$member->post('/discussions/2/comments', ['body' => 'No token.']);
check('missing CSRF token is rejected (403)', $member->status === 403 && commentCount($pdo) === $count);
$member->post('/discussions/2/comments/5/replies', ['body' => 'Cross-discussion parent.', 'csrf_token' => $token]);
check('parent from another discussion is rejected (422)', $member->status === 422 && commentCount($pdo) === $count);
$member->post('/discussions/999999/comments', ['body' => 'Nowhere.', 'csrf_token' => $token]);
check('posting to a missing discussion returns 404', $member->status === 404 && commentCount($pdo) === $count);
$member->post('/discussions/2/comments', ['body' => "  \n ", 'csrf_token' => $token]);
check('empty comment is rejected (422)', $member->status === 422 && commentCount($pdo) === $count);
$member->post('/discussions/2/comments', ['body' => str_repeat('a', 2001), 'csrf_token' => $token]);
check('over-long comment is rejected (422)', $member->status === 422 && commentCount($pdo) === $count);

echo "\nHTML escaping\n";
$payload = '<script>alert("xss")</script> <b onclick=\'x()\'>bold</b>';
$member->post('/discussions/2/comments', ['body' => $payload, 'csrf_token' => $token]);
$xssId = newCommentId($member->location());
$html  = commentHtml($member->get('/discussions/2')->body, $xssId);
check('script-like comment is saved as text', $xssId > 0);
check('it is displayed escaped, never as markup',
    str_contains($html, '&lt;script&gt;alert(&quot;xss&quot;)&lt;/script&gt;') && !str_contains($html, '<script>') && !str_contains($html, '<b '));

echo "\nLogout, then guest writes\n";
$member->post('/logout', ['csrf_token' => $token]);
check('logout by POST redirects', $member->status === 303);
$count = commentCount($pdo);
$member->post('/discussions/2/comments', ['body' => 'After logout.', 'csrf_token' => $token]);
check('post after logout redirects to /login, no row',
    $member->status === 303 && $member->location() === '/login' && commentCount($pdo) === $count);
$guest->post('/discussions/2/comments', ['body' => 'Direct guest POST.']);
check('direct guest POST (top-level) creates no row',
    $guest->status === 303 && $guest->location() === '/login' && commentCount($pdo) === $count);
$guest->post('/discussions/2/comments/9/replies', ['body' => 'Direct guest reply.', 'csrf_token' => $guest->token('/login')]);
check('direct guest POST (reply) creates no row',
    $guest->status === 303 && $guest->location() === '/login' && commentCount($pdo) === $count);

echo "\n$passed passed, $failed failed\n";
exit($failed === 0 ? 0 : 1);
