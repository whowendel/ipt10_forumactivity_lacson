# Nested Forum Lab

Laboratory Activity: Visualizing Nested Forum Replies (PHP, MySQL and PDO).

A small forum that shows replies as a tree. Anyone may read. Only a logged-in user may post.

## Libraries

| Job | Library | Note |
|---|---|---|
| Routing | [bramus/router](https://github.com/bramus/router) | lecture library |
| Templates | [mustache/mustache](https://github.com/bobthecow/mustache.php) | lecture library |
| Data access | PDO (built into PHP) | **substitution**: used directly instead of Eloquent |

Eloquent runs on PDO underneath. Using PDO directly keeps every SQL statement visible in
`app/Models/`, which makes parameter binding easy to show. No SQL appears in controllers or templates.

## Requirements

- PHP 8.2 or newer with `pdo_mysql` and `mbstring`
- MySQL 8 or MariaDB 10.4+
- Composer

## Setup

    composer install
    mysql -u YOUR_USER -p < database/schema.sql

`schema.sql` drops and recreates the `nested_forum_lab` database with the seed data.

Copy `config/database.example.php` to `config/database.local.php` and enter your own
database username and password. That file is git-ignored.

## Run locally

    php -S localhost:8000 -t public public/index.php

Open <http://localhost:8000/> or go straight to <http://localhost:8000/discussions/1>.

The seed users have no password and cannot log in. Register your own account at `/register`.

On a production server, point the document root at `public/` and rewrite every request that
is not a real file to `public/index.php` (Apache: `FallbackResource /index.php`;
nginx: `try_files $uri /index.php$is_args$args;`). This is not needed for the local test.

## Routes

| Method | Path | Controller method | Login |
|---|---|---|---|
| GET | `/` | `DiscussionController::index` | no |
| GET | `/discussions/{id}` | `DiscussionController::show` | no |
| GET, POST | `/register` | `AuthController::showRegister`, `register` | no |
| GET, POST | `/login` | `AuthController::showLogin`, `login` | no |
| POST | `/logout` | `AuthController::logout` | yes |
| POST | `/discussions/{id}/comments` | `CommentController::storeTopLevel` | yes |
| POST | `/discussions/{id}/comments/{commentId}/replies` | `CommentController::storeReply` | yes |

Responses for discussion IDs:

- **400** for a malformed ID (`abc`, `0`, `-1`, `1.5`): rejected by the controller before any query.
- **404** for a well-formed ID with no discussion. The comment query does not run.

## Request flow

    Browser
      -> public/index.php                 front controller: session, PDO, wiring
      -> config/routes.php                Bramus Router picks a controller method
      -> app/Controllers/*                validates input, checks login and CSRF
      -> app/Models/*Repository           parameterized PDO queries, returns rows
      -> app/Services/CommentTree         groups rows by parent, calculates depth
      -> app/Views/*.mustache             Mustache renders escaped HTML
      -> Browser

## Layout

    public/index.php          front controller (only public PHP file)
    public/assets/            CSS, default avatar, collapse script
    app/Controllers/          HTTP handling
    app/Models/               PDO data access: users, discussions, comments
    app/Services/             CommentTree: flat rows -> nested tree with depth
    app/Support/              Database, Session, Csrf, Auth, View, HttpException
    app/Views/                Mustache templates and partials
    config/                   routes, limits, database settings
    database/schema.sql       schema and seed data
    tests/                    smoke.php (automated) and manual-checks.md

## Data model

- `comments.discussion_id` says which discussion a comment is in.
- `comments.parent_id` says which comment it replies to. `NULL` means top-level.
- A composite foreign key `(parent_id, discussion_id) -> comments (id, discussion_id)` makes the
  database refuse a parent from another discussion.
- Depth is calculated when the page is built. It is not stored.

## How the tree is built

1. One query fetches every comment of the discussion as flat rows.
2. `CommentTree` groups the rows by `parent_id`.
3. It walks down from the top-level group. A top-level comment is depth 0; each reply is its parent's depth + 1.
4. The `comment` partial includes itself for each child, so the HTML nests the same way.

Indentation stops growing after depth 8; the "Depth N" label keeps counting.
The renderer stops at 2000 comments or 64 levels and skips any comment already on the current
path, so corrupt data cannot loop forever. These limits are in `config/app.php`.

## Security

- **Passwords**: `password_hash` on register, `password_verify` on login. A `NULL` hash never authenticates.
- **Sessions**: ID regenerated after login; cookie is HttpOnly and SameSite=Lax, and Secure under HTTPS.
- **Authorization**: every write checks the session on the server. The author is the session user;
  a `user_id` form field is ignored.
- **CSRF**: a session-backed token on the register, login, logout, comment and reply forms, compared with `hash_equals`.
- **SQL injection**: every value is a bound parameter in a prepared statement, with emulated prepares off.
- **XSS**: all output uses Mustache `{{ }}`, which HTML-escapes. No `{{{ }}}` is used anywhere.
  A Content-Security-Policy header blocks inline scripts.
- **Cycles and depth**: `parent_id` is set once on insert and never updated, so a reply cannot become its
  own ancestor. Replies deeper than depth 12 are refused.

## Tests

With the server running:

    php tests/smoke.php

It runs 38 checks over HTTP (reading, registration, login, replies, depth cap, CSRF, guest POSTs,
cross-discussion parent, HTML escaping) and counts rows to prove rejected requests saved nothing.
It adds a test user and test comments to discussion 2. Re-import `database/schema.sql` afterwards.

`tests/manual-checks.md` lists the same checks to do by hand for screenshots.

## Optional challenge

`public/assets/replies.js` adds a "Hide replies" / "Show replies" button to each comment that has
replies. It is a real `<button type="button">` with `aria-controls` and `aria-expanded`, and it uses
the `hidden` attribute. The script creates the buttons, so with JavaScript off no buttons appear and all replies stay visible.

## Limitations

- All comments of a discussion load at once. No pagination.
- No rate limiting, account recovery, email verification or moderation.
- No page for creating discussions; they come from the seed data.
- Avatar upload and Gravatar are not implemented. Everyone gets the default avatar.
- The app expects to run at the web root (`/`), not in a subfolder.
