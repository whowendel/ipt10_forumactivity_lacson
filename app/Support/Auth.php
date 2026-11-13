<?php
declare(strict_types=1);

namespace App\Support;

use App\Models\UserRepository;

final class Auth
{
    private ?array $user = null;
    private bool $loaded = false;

    public function __construct(private readonly UserRepository $users)
    {
    }

    public function user(): ?array
    {
        if (!$this->loaded) {
            $this->loaded = true;
            $id = Session::get('user_id');
            if (is_int($id)) {
                $this->user = $this->users->findById($id);
                if ($this->user === null) {
                    Session::forget('user_id');
                }
            }
        }
        return $this->user;
    }

    public function check(): bool
    {
        return $this->user() !== null;
    }

    public function id(): ?int
    {
        $user = $this->user();
        return $user === null ? null : (int) $user['id'];
    }

    public function login(int $userId): void
    {
        Session::regenerate();
        Session::set('user_id', $userId);
        $this->loaded = false;
    }

    public function logout(): void
    {
        Session::destroy();
        $this->user = null;
        $this->loaded = true;
    }
}
