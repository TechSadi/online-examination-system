<?php

declare(strict_types=1);

use App\Core\Env;

/**
 * Builds the application configuration array from environment variables.
 * Application code reads this through App\Core\Config, never through getenv().
 */
return [
    'app' => [
        'name'  => (string) Env::get('APP_NAME', 'ExamHub'),
        'env'   => (string) Env::get('APP_ENV', 'local'),
        'debug' => (bool) Env::get('APP_DEBUG', false),
        // Empty => URLs are generated relative to the document root.
        'url'   => rtrim((string) Env::get('APP_URL', ''), '/'),
        // Only enable behind a reverse proxy that sets X-Forwarded-Proto and
        // strips any copy the client sent. Otherwise a client could assert
        // its own connection was secure.
        'trust_proxy' => (bool) Env::get('APP_TRUST_PROXY', false),
    ],

    'db' => [
        'host'     => (string) Env::get('DB_HOST', 'localhost'),
        'port'     => (int) Env::get('DB_PORT', 3306),
        'name'     => (string) Env::get('DB_NAME', 'online_exam_db'),
        'user'     => (string) Env::get('DB_USER', 'root'),
        'password' => (string) Env::get('DB_PASSWORD', ''),
        'charset'  => (string) Env::get('DB_CHARSET', 'utf8mb4'),
        // Seconds to wait for a connection before giving up. A managed
        // database is a network hop away, unlike a local socket.
        'timeout'  => (int) Env::get('DB_TIMEOUT', 10),
        // Encrypt the connection. Required by every managed MySQL provider
        // and unnecessary for a local server on the same machine.
        'ssl'      => (bool) Env::get('DB_SSL', false),
        // Path to the provider's CA certificate. When set, the server's
        // certificate is verified against it; DB_SSL is then implied.
        'ssl_ca'   => (string) Env::get('DB_SSL_CA', ''),
    ],

    'session' => [
        'name'   => (string) Env::get('SESSION_NAME', 'examhub_session'),
        'secret' => (string) Env::get('SESSION_SECRET', ''),
        'secure' => (bool) Env::get('SESSION_SECURE', false),
        // Sign out after this long without a request.
        'idle_timeout'     => (int) Env::get('SESSION_IDLE_TIMEOUT', 1800),
        // Hard ceiling on a session's life, however active it is.
        'absolute_timeout' => (int) Env::get('SESSION_ABSOLUTE_TIMEOUT', 28800),
    ],

    'security' => [
        // bcrypt work factor. 12 is roughly 250ms on current hardware:
        // costly to attack offline, unnoticeable on a sign-in.
        'bcrypt_cost'         => (int) Env::get('BCRYPT_COST', 12),
        'password_min_length' => (int) Env::get('PASSWORD_MIN_LENGTH', 8),
        // Failed sign-ins allowed per identifier before a lockout.
        'login_max_attempts'  => (int) Env::get('LOGIN_MAX_ATTEMPTS', 5),
        // How long the window and the resulting lockout last, in seconds.
        'login_decay'         => (int) Env::get('LOGIN_DECAY', 900),
    ],

    'exam' => [
        'pass_mark'    => (int) Env::get('EXAM_PASS_MARK', 60),
        'max_duration' => (int) Env::get('EXAM_MAX_DURATION', 300),
        // Seconds a submission may arrive after the deadline and still count.
        // The client auto-submits at zero, so the request is already in
        // flight as time runs out; without this a slow connection would cost
        // a student their paper. Beyond it, the attempt is recorded expired.
        'submit_grace' => (int) Env::get('EXAM_SUBMIT_GRACE', 60),
    ],

    'paths' => [
        'root' => dirname(__DIR__, 2),
        'logs' => dirname(__DIR__, 2) . '/storage/logs',
    ],
];
