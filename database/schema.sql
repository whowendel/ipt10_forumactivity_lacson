
DROP DATABASE IF EXISTS nested_forum_lab;
CREATE DATABASE nested_forum_lab CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE nested_forum_lab;

SET time_zone = '+00:00';

CREATE TABLE users (
    id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
    username      VARCHAR(30)  NOT NULL,
    email         VARCHAR(255) NOT NULL,
    password_hash VARCHAR(255) NULL,
    created_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_users_username (username),
    UNIQUE KEY uq_users_email (email)
) ENGINE=InnoDB;

CREATE TABLE discussions (
    id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id    INT UNSIGNED NOT NULL,
    title      VARCHAR(150) NOT NULL,
    body       TEXT         NOT NULL,
    created_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    CONSTRAINT fk_discussions_user FOREIGN KEY (user_id) REFERENCES users (id)
) ENGINE=InnoDB;

CREATE TABLE comments (
    id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
    discussion_id INT UNSIGNED NOT NULL,
    parent_id     INT UNSIGNED NULL,
    user_id       INT UNSIGNED NOT NULL,
    body          TEXT         NOT NULL,
    created_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_comments_id_discussion (id, discussion_id),
    KEY idx_comments_discussion (discussion_id, created_at),
    CONSTRAINT fk_comments_discussion FOREIGN KEY (discussion_id) REFERENCES discussions (id),
    CONSTRAINT fk_comments_user FOREIGN KEY (user_id) REFERENCES users (id),
    CONSTRAINT fk_comments_parent_same_discussion
        FOREIGN KEY (parent_id, discussion_id) REFERENCES comments (id, discussion_id)
) ENGINE=InnoDB;

INSERT INTO users (id, username, email, password_hash, created_at) VALUES
    (1, 'mika', 'mika@example.test', NULL, '2026-11-02 01:00:00'),
    (2, 'renz', 'renz@example.test', NULL, '2026-11-02 01:05:00'),
    (3, 'tala', 'tala@example.test', NULL, '2026-11-02 01:10:00'),
    (4, 'jun',  'jun@example.test',  NULL, '2026-11-02 01:15:00'),
    (5, 'bea',  'bea@example.test',  NULL, '2026-11-02 01:20:00');

INSERT INTO discussions (id, user_id, title, body, created_at) VALUES
    (1, 1, 'How should a beginner practise SQL joins?',
        'I understand SELECT, but joins still confuse me. What worked for you?',
        '2026-11-03 02:00:00'),
    (2, 3, 'Share your favourite PHP debugging habit',
        'One habit that saves you time when something breaks.',
        '2026-11-04 03:00:00');

INSERT INTO comments (id, discussion_id, parent_id, user_id, body, created_at) VALUES
    (1, 1, NULL, 2, 'Draw the two tables on paper first.',                      '2026-11-03 02:10:00'),
    (2, 1, 1,    3, 'Yes, and write the matching column next to each arrow.',   '2026-11-03 02:20:00'),
    (3, 1, 2,    4, 'Then run the same query with LEFT JOIN and compare.',      '2026-11-03 02:30:00'),
    (4, 1, 3,    5, 'Comparing row counts helped me spot the missing matches.', '2026-11-03 02:40:00'),
    (5, 1, 4,    2, 'Good tip. NULLs from the right table are the clue.',       '2026-11-03 02:50:00'),
    (6, 1, 1,    4, 'Paper, or a whiteboard tool?',                             '2026-11-03 03:00:00'),
    (7, 1, NULL, 5, 'Use a tiny dataset, five rows per table.',                 '2026-11-03 03:10:00'),
    (8, 1, 7,    1, 'Thanks, I will make one tonight.',                         '2026-11-03 03:20:00');

INSERT INTO comments (id, discussion_id, parent_id, user_id, body, created_at) VALUES
    (9, 2, NULL, 4, 'var_dump early, then switch to a step debugger.',          '2026-11-04 03:10:00');
