<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Models\UserRepository;
use App\Support\Auth;
use App\Support\Csrf;
use App\Support\Session;
use App\Support\View;
use PDOException;

final class AuthController extends Controller
{

    private const DUMMY_HASH = '$2y$10$ketbK8OAxK.Kb22CSOQtIuGJAXIHSoCZJ6L.HdA9j6ge3SmRL3PE2';

    public function __construct(
        private readonly UserRepository $users,
        private readonly Auth $auth,
        private readonly View $view,
    ) {
    }

    public function showRegister(): void
    {
        if ($this->auth->check()) {
            View::redirect('/');
        }
        $this->view->render('register', ['page_title' => 'Register']);
    }

    public function register(): void
    {
        Csrf::verify();
        if ($this->auth->check()) {
            View::redirect('/');
        }

        $username = trim($this->input('username'));
        $email    = strtolower(trim($this->input('email')));
        $password = $this->input('password');
        $confirm  = $this->input('password_confirm');

        $errors = [];
        if (!preg_match('/\A[A-Za-z0-9_]{3,30}\z/', $username)) {
            $errors[] = 'Username must be 3 to 30 characters: letters, numbers and underscores only.';
        }
        if (strlen($email) > 255 || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            $errors[] = 'Enter a valid email address.';
        }

        if (strlen($password) < 8 || strlen($password) > 72) {
            $errors[] = 'Password must be 8 to 72 characters.';
        } elseif (!hash_equals($password, $confirm)) {
            $errors[] = 'The two passwords do not match.';
        }

        if ($errors === []) {
            if ($this->users->usernameExists($username)) {
                $errors[] = 'That username is already taken.';
            }
            if ($this->users->emailExists($email)) {
                $errors[] = 'That email address is already registered.';
            }
        }

        if ($errors === []) {
            try {
                $this->users->create($username, $email, password_hash($password, PASSWORD_DEFAULT));
            } catch (PDOException $e) {
                if ($e->getCode() !== '23000') {
                    throw $e;
                }

                $errors[] = 'That username or email address is already registered.';
            }
        }

        if ($errors !== []) {
            $this->view->render('register', [
                'page_title'   => 'Register',
                'errors'       => $errors,
                'has_errors'   => true,
                'old_username' => $username,
                'old_email'    => $email,
            ], 422);
            return;
        }

        Session::flash('Account created. You can log in now.');
        View::redirect('/login');
    }

    public function showLogin(): void
    {
        if ($this->auth->check()) {
            View::redirect('/');
        }
        $this->view->render('login', ['page_title' => 'Log in']);
    }

    public function login(): void
    {
        Csrf::verify();
        if ($this->auth->check()) {
            View::redirect('/');
        }

        $username = trim($this->input('username'));
        $password = $this->input('password');

        $user = $this->users->findForLogin($username);
        $hash = $user['password_hash'] ?? null;

        $hasUsableHash = is_string($hash) && $hash !== '';
        $passwordOk    = password_verify($password, $hasUsableHash ? $hash : self::DUMMY_HASH);

        if ($user === null || !$hasUsableHash || !$passwordOk) {

            $this->view->render('login', [
                'page_title'   => 'Log in',
                'errors'       => ['Incorrect username or password.'],
                'has_errors'   => true,
                'old_username' => $username,
            ], 422);
            return;
        }

        $this->auth->login((int) $user['id']);
        Session::flash('Welcome back, ' . $user['username'] . '.');
        View::redirect('/');
    }

    public function logout(): void
    {
        Csrf::verify();
        $this->auth->logout();
        View::redirect('/');
    }
}
