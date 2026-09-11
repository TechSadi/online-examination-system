<?php

declare(strict_types=1);

/**
 * Create the first administrator, or reset an existing one's password.
 *
 *   php bin/create-admin.php
 *
 * Reads ADMIN_USERNAME, ADMIN_EMAIL, ADMIN_NAME and ADMIN_PASSWORD from the
 * environment. Where the terminal is interactive and a value is missing, it
 * asks; where it is not - a container's start command, a CI job - a missing
 * value is an error rather than a prompt nobody will ever see.
 *
 * This exists because the schema used to seed an administrator with the
 * password "admin123" and the matching bcrypt hash committed to the
 * repository. Every deployment that imported that file therefore shipped with
 * a published credential for a full-privilege account, and anyone who had read
 * the repository could sign in to any of them. A password typed into an
 * environment variable is hashed here and never written to a file.
 *
 * Safe to run repeatedly: an existing username or email has its password and
 * name updated rather than being duplicated, which is what makes it usable as
 * a password reset when someone is locked out.
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once dirname(__DIR__) . '/app/bootstrap.php';

use App\Core\Config;
use App\Core\ConfigurationException;
use App\Core\Database;
use App\Core\Env;
use App\Repositories\AdminRepository;
use App\Services\AuthService;

function say(string $line = ''): void
{
    fwrite(STDOUT, $line . PHP_EOL);
}

function fail(string $line): never
{
    fwrite(STDERR, $line . PHP_EOL);
    exit(1);
}

/** Whether a human is on the other end of stdin. */
function interactive(): bool
{
    return function_exists('stream_isatty') && @stream_isatty(STDIN);
}

/**
 * A value from the environment, or asked for when there is a terminal.
 *
 * $hidden turns off echo for a password so it does not stay on screen or in
 * a screenshot. The `stty` fallback is Unix-only; on Windows the value is
 * visible, which is worth saying rather than pretending otherwise.
 */
function value(string $key, string $prompt, string $default = '', bool $hidden = false): string
{
    $fromEnv = (string) (Env::get($key, '') ?? '');

    if ($fromEnv !== '') {
        return $fromEnv;
    }

    // No terminal - a container's start command, a CI job. A value with a
    // sensible default takes it; one without is a hard error, because the
    // alternative is a prompt printed into a log nobody is reading.
    if (!interactive()) {
        if ($default !== '') {
            return $default;
        }

        fail(sprintf('  %s is not set, and there is no terminal to ask on.', $key));
    }

    $suffix = $default === '' ? '' : sprintf(' [%s]', $default);
    fwrite(STDOUT, $prompt . $suffix . ': ');

    if ($hidden && stripos(PHP_OS_FAMILY, 'Windows') === false) {
        @shell_exec('stty -echo');
        $input = (string) fgets(STDIN);
        @shell_exec('stty echo');
        fwrite(STDOUT, PHP_EOL);
    } else {
        $input = (string) fgets(STDIN);
    }

    $input = trim($input);

    return $input === '' ? $default : $input;
}

/* ── Collect ─────────────────────────────────────────────── */

say('ExamHub administrator');
say();

$username = value('ADMIN_USERNAME', '  Username', 'admin');
$email    = value('ADMIN_EMAIL', '  Email address');
$name     = value('ADMIN_NAME', '  Full name', 'Administrator');
$password = value('ADMIN_PASSWORD', '  Password', '', true);

/* ── Validate ────────────────────────────────────────────── */

$minimum = max(1, (int) Config::get('security.password_min_length', 8));
$errors  = [];

if (preg_match('/^[A-Za-z0-9_.-]{3,60}$/', $username) !== 1) {
    $errors[] = 'Username must be 3-60 characters of letters, digits, dot, dash or underscore.';
}

if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
    $errors[] = 'Email address is not valid.';
}

// The same rules the registration form applies. An account created here is an
// ordinary administrator, so it must not be allowed a weaker password than one
// created through the interface.
if (mb_strlen($password) < $minimum) {
    $errors[] = sprintf('Password must be at least %d characters.', $minimum);
}

if (strlen($password) > AuthService::MAX_PASSWORD_BYTES) {
    $errors[] = sprintf(
        'Password cannot exceed %d bytes - bcrypt silently ignores anything past that.',
        AuthService::MAX_PASSWORD_BYTES
    );
}

if ($errors !== []) {
    foreach ($errors as $error) {
        fwrite(STDERR, '  ' . $error . PHP_EOL);
    }
    exit(1);
}

/* ── Write ───────────────────────────────────────────────── */

try {
    Database::connection();
} catch (ConfigurationException $e) {
    // Safe to repeat: it names a value the operator set, not one
    // the driver reported back with a host and a user in it.
    fail('  ' . $e->getMessage());
} catch (Throwable) {
    fail('  Cannot connect to the database. Check the DB_* variables.');
}

$hash     = (new AuthService())->hash($password);
$existing = Database::fetch(
    'SELECT admin_id, username FROM admins WHERE username = ? OR email = ? LIMIT 1',
    [$username, $email]
);

if ($existing !== null) {
    Database::execute(
        'UPDATE admins SET username = ?, email = ?, full_name = ?, password = ? WHERE admin_id = ?',
        [$username, $email, $name, $hash, (int) $existing['admin_id']]
    );

    say();
    say(sprintf('  Updated administrator "%s" and reset the password.', $username));
    exit(0);
}

(new AdminRepository())->create($username, $email, $name, $hash);

say();
say(sprintf('  Created administrator "%s".', $username));
say('  Sign in at /admin/login.php');
