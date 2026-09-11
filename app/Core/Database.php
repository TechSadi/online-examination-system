<?php

declare(strict_types=1);

namespace App\Core;

use PDO;
use PDOException;
use PDOStatement;
use RuntimeException;

/**
 * Single PDO connection plus the small query helpers the repositories need.
 *
 * Every method binds its parameters; none accepts an interpolated value.
 * Connection failures become RuntimeException so the central error handler
 * decides what the user sees - credentials and DSN never reach the browser.
 *
 * The connection is described entirely by environment variables, including
 * its TLS settings. Production runs against a managed MySQL host rather than
 * a local server, and moving between providers must not require a code
 * change - only a different set of DB_* values.
 */
final class Database
{
    private static ?PDO $pdo = null;

    public static function connection(): PDO
    {
        if (self::$pdo instanceof PDO) {
            return self::$pdo;
        }

        try {
            self::$pdo = new PDO(
                self::dsn(),
                (string) Config::get('db.user'),
                (string) Config::get('db.password'),
                self::options()
            );
        } catch (PDOException $e) {
            // The PDOException message carries the host, the user and
            // sometimes the password. It is kept as the previous exception so
            // it reaches the log, and replaced here so it cannot reach a page.
            throw new RuntimeException('Database connection failed.', 0, $e);
        }

        return self::$pdo;
    }

    /** Reset the connection. Used by the CLI tooling between databases. */
    public static function disconnect(): void
    {
        self::$pdo = null;
    }

    private static function dsn(): string
    {
        return sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=%s',
            Config::get('db.host'),
            Config::get('db.port'),
            Config::get('db.name'),
            Config::get('db.charset')
        );
    }

    /**
     * PDO driver options.
     *
     * @return array<int,mixed>
     */
    private static function options(): array
    {
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,

            // A managed database lives across a network, not on localhost. A
            // request must fail in seconds rather than occupy a worker until
            // the platform's own timeout kills it.
            PDO::ATTR_TIMEOUT => max(1, (int) Config::get('db.timeout', 10)),

            // Pin the session to UTC.
            //
            // PHP writes every DATETIME it controls in UTC, but created_at and
            // date_taken are TIMESTAMP columns filled by the server, which
            // converts them using the session time zone. Left unset, those
            // columns follow whatever zone the managed host happens to run in
            // and a result would be stamped hours away from the attempt that
            // produced it. A numeric offset is used rather than a name so it
            // works on servers that never loaded the time-zone tables.
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET time_zone = '+00:00'",
        ];

        return $options + self::tlsOptions();
    }

    /**
     * TLS options for the connection.
     *
     * Traffic to a managed database crosses the public internet, so it must
     * be encrypted. Providers differ in how they present their certificate:
     * some publish a private CA to pin (DB_SSL_CA), others use a public one.
     *
     * @return array<int,mixed>
     */
    private static function tlsOptions(): array
    {
        $ca = trim((string) Config::get('db.ssl_ca', ''));

        if ($ca !== '') {
            if (!is_readable($ca)) {
                throw new RuntimeException(sprintf(
                    'DB_SSL_CA points at "%s", which cannot be read.',
                    $ca
                ));
            }

            return [
                PDO::MYSQL_ATTR_SSL_CA                     => $ca,
                PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT     => true,
            ];
        }

        if (!(bool) Config::get('db.ssl', false)) {
            return [];
        }

        // Encrypted, but the certificate is not verified: without a CA there
        // is nothing to verify it against. This protects against passive
        // interception, not against an active man in the middle, so it is a
        // fallback for providers that publish no CA bundle - not the default.
        return [PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => false];
    }

    /** @param array<int|string,mixed> $params */
    public static function run(string $sql, array $params = []): PDOStatement
    {
        $statement = self::connection()->prepare($sql);
        $statement->execute($params);

        return $statement;
    }

    /**
     * @param  array<int|string,mixed> $params
     * @return array<string,mixed>|null
     */
    public static function fetch(string $sql, array $params = []): ?array
    {
        $row = self::run($sql, $params)->fetch();

        return $row === false ? null : $row;
    }

    /**
     * @param  array<int|string,mixed> $params
     * @return list<array<string,mixed>>
     */
    public static function fetchAll(string $sql, array $params = []): array
    {
        return self::run($sql, $params)->fetchAll();
    }

    /** @param array<int|string,mixed> $params */
    public static function fetchColumn(string $sql, array $params = []): mixed
    {
        return self::run($sql, $params)->fetchColumn();
    }

    /** @param array<int|string,mixed> $params */
    public static function count(string $sql, array $params = []): int
    {
        return (int) self::fetchColumn($sql, $params);
    }

    /**
     * Execute a write and return the number of affected rows.
     *
     * @param array<int|string,mixed> $params
     */
    public static function execute(string $sql, array $params = []): int
    {
        return self::run($sql, $params)->rowCount();
    }

    /** @param array<int|string,mixed> $params */
    public static function insert(string $sql, array $params = []): int
    {
        self::run($sql, $params);

        return (int) self::connection()->lastInsertId();
    }

    /** Run a callback inside a transaction, rolling back on any throwable. */
    public static function transaction(callable $callback): mixed
    {
        $pdo = self::connection();
        $pdo->beginTransaction();

        try {
            $result = $callback($pdo);
            $pdo->commit();

            return $result;
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }
}
