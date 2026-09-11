<?php

declare(strict_types=1);

/**
 * Apply pending database migrations.
 *
 *   php bin/migrate.php            apply everything not yet applied
 *   php bin/migrate.php --status   list what is applied and what is pending
 *   php bin/migrate.php --seed     also load database/seeds/demo.sql
 *
 * Production must never be brought up to date by hand in phpMyAdmin, because
 * nothing then records what was run and the next environment becomes a guess.
 * This keeps a ledger in the database itself, applies files in filename order
 * and skips anything already recorded - so running it twice is a no-op, and
 * the same command is correct on a new database and on one several versions
 * behind.
 *
 * It connects through the ordinary application configuration, so it targets
 * whatever DB_* points at: the local MySQL during development, the managed
 * host in production. There is no second set of credentials to keep in step.
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once dirname(__DIR__) . '/app/bootstrap.php';

use App\Core\Config;
use App\Core\Database;

const LEDGER = 'schema_migrations';

$options = array_slice($argv, 1);
$status  = in_array('--status', $options, true);
$seed    = in_array('--seed', $options, true);

/** Write a line to stdout. */
function say(string $line = ''): void
{
    fwrite(STDOUT, $line . PHP_EOL);
}

/** Write a line to stderr and stop with a failing exit code. */
function fail(string $line): never
{
    fwrite(STDERR, $line . PHP_EOL);
    exit(1);
}

/**
 * Split a file into executable statements.
 *
 * MySQL will not take a whole file in one call, so it is cut on semicolons.
 * Quoted strings and comments are tracked while scanning, so a semicolon
 * inside either - one in a question's text, one in a sentence of explanation -
 * does not split a statement in half.
 *
 * @return list<string>
 */
function statements(string $sql): array
{
    $out     = [];
    $current = '';
    $quote   = null;
    $length  = strlen($sql);
    $escape  = chr(92);

    for ($i = 0; $i < $length; $i++) {
        $char = $sql[$i];
        $next = $sql[$i + 1] ?? '';

        if ($quote === null) {
            // Line comments: "-- " and "#" both run to the end of the line.
            if (($char === '-' && $next === '-') || $char === '#') {
                $break = strpos($sql, "\n", $i);
                $i     = $break === false ? $length : $break;
                continue;
            }

            // Block comments.
            if ($char === '/' && $next === '*') {
                $close = strpos($sql, '*/', $i + 2);
                $i     = $close === false ? $length : $close + 1;
                continue;
            }

            if ($char === "'" || $char === '"' || $char === chr(96)) {
                $quote = $char;
            } elseif ($char === ';') {
                $statement = trim($current);
                if ($statement !== '') {
                    $out[] = $statement;
                }
                $current = '';
                continue;
            }
        } elseif ($char === $escape) {
            // An escape inside a string: take the next character literally so
            // an escaped quote is not mistaken for a closing one.
            $current .= $char . $next;
            $i++;
            continue;
        } elseif ($char === $quote) {
            $quote = null;
        }

        $current .= $char;
    }

    $statement = trim($current);
    if ($statement !== '') {
        $out[] = $statement;
    }

    return $out;
}

/** Create the ledger if this database has never been migrated. */
function ensureLedger(): void
{
    Database::connection()->exec(
        'CREATE TABLE IF NOT EXISTS ' . LEDGER . ' (
            migration  VARCHAR(191) NOT NULL PRIMARY KEY,
            applied_at DATETIME     NOT NULL
         ) ENGINE=InnoDB'
    );
}

/** @return list<string> */
function applied(): array
{
    return array_map(
        static fn (array $row): string => (string) $row['migration'],
        Database::fetchAll('SELECT migration FROM ' . LEDGER . ' ORDER BY migration')
    );
}

/** @return list<string> absolute paths, in filename order */
function migrationFiles(): array
{
    $files = glob(dirname(__DIR__) . '/database/migrations/*.sql') ?: [];

    sort($files, SORT_STRING);

    return array_values($files);
}

/**
 * Run one migration file, then record it.
 *
 * The ledger row is written last and only on success. MySQL commits DDL
 * implicitly, so a file that dies halfway cannot be rolled back - which is why
 * every statement in every migration is written to be safe to re-run, and why
 * an unrecorded file is simply attempted again on the next run.
 */
function apply(string $path): void
{
    $name = basename($path);
    $pdo  = Database::connection();

    say('  applying ' . $name . ' ...');

    foreach (statements((string) file_get_contents($path)) as $statement) {
        try {
            $pdo->exec($statement);
        } catch (PDOException $e) {
            fail(sprintf(
                "  FAILED in %s\n  %s\n  statement: %s",
                $name,
                $e->getMessage(),
                strlen($statement) > 300 ? substr($statement, 0, 300) . ' ...' : $statement
            ));
        }
    }

    Database::execute(
        'INSERT INTO ' . LEDGER . ' (migration, applied_at) VALUES (?, UTC_TIMESTAMP())',
        [$name]
    );
}

/* ── Run ─────────────────────────────────────────────────── */

say('ExamHub database migrations');
say(sprintf(
    '  target: %s@%s:%s/%s',
    Config::get('db.user'),
    Config::get('db.host'),
    Config::get('db.port'),
    Config::get('db.name')
));
say();

try {
    Database::connection();
} catch (Throwable) {
    // The detail is in the logged exception. The message here is deliberately
    // free of credentials, because build logs are not always private.
    fail('  Cannot connect to the database. Check the DB_* variables.');
}

ensureLedger();

$done    = applied();
$files   = migrationFiles();
$pending = array_values(array_filter(
    $files,
    static fn (string $path): bool => !in_array(basename($path), $done, true)
));

if ($status) {
    foreach ($files as $path) {
        $name = basename($path);
        say(sprintf('  [%s] %s', in_array($name, $done, true) ? 'x' : ' ', $name));
    }
    say();
    say(sprintf('  %d applied, %d pending.', count($done), count($pending)));
    exit(0);
}

if ($pending === []) {
    say('  Nothing to apply - the database is up to date.');
} else {
    foreach ($pending as $path) {
        apply($path);
    }
    say();
    say(sprintf('  Applied %d migration%s.', count($pending), count($pending) === 1 ? '' : 's'));
}

if ($seed) {
    $path = dirname(__DIR__) . '/database/seeds/demo.sql';

    if (!is_file($path)) {
        fail('  No seed file at database/seeds/demo.sql.');
    }

    say();
    say('  seeding demo content ...');

    $pdo = Database::connection();
    foreach (statements((string) file_get_contents($path)) as $statement) {
        $pdo->exec($statement);
    }

    say('  Demo exams and questions loaded.');
}

say();
say('Done.');
