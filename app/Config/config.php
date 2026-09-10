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
    ],

    'db' => [
        'host'     => (string) Env::get('DB_HOST', 'localhost'),
        'port'     => (int) Env::get('DB_PORT', 3306),
        'name'     => (string) Env::get('DB_NAME', 'online_exam_db'),
        'user'     => (string) Env::get('DB_USER', 'root'),
        'password' => (string) Env::get('DB_PASSWORD', ''),
        'charset'  => (string) Env::get('DB_CHARSET', 'utf8mb4'),
    ],

    'session' => [
        'name'   => (string) Env::get('SESSION_NAME', 'examhub_session'),
        'secret' => (string) Env::get('SESSION_SECRET', ''),
        'secure' => (bool) Env::get('SESSION_SECURE', false),
    ],

    'exam' => [
        'pass_mark'    => (int) Env::get('EXAM_PASS_MARK', 60),
        'max_duration' => (int) Env::get('EXAM_MAX_DURATION', 300),
    ],

    'paths' => [
        'root' => dirname(__DIR__, 2),
        'logs' => dirname(__DIR__, 2) . '/storage/logs',
    ],
];
