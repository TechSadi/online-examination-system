#!/bin/sh
# ============================================================
#  ExamHub - container entrypoint
#
#  Render assigns a port at runtime through $PORT and requires
#  the service to bind 0.0.0.0 on it. Apache's Listen directive
#  is parsed from a file and cannot read an environment
#  variable, so the value is written in before Apache starts.
#
#  Everything else - database, URL, secrets - reaches the
#  application as ordinary environment variables and needs no
#  help from this script.
# ============================================================

set -eu

# Render's documented default. Also what makes `docker run` work
# locally without having to remember to pass PORT.
PORT="${PORT:-10000}"

case "$PORT" in
    ''|*[!0-9]*)
        echo "entrypoint: PORT must be a number, got '$PORT'" >&2
        exit 1
        ;;
esac

# Apache reserves nothing below 1024 for us: the container runs
# the parent process as root but drops to www-data for workers,
# and binding a privileged port is a capability this container
# should not need.
if [ "$PORT" -lt 1 ] || [ "$PORT" -gt 65535 ]; then
    echo "entrypoint: PORT $PORT is outside 1-65535" >&2
    exit 1
fi

sed -i "s/^Listen .*/Listen ${PORT}/" /etc/apache2/ports.conf
sed -i "s/<VirtualHost \*:[0-9]\{1,\}>/<VirtualHost *:${PORT}>/" \
    /etc/apache2/sites-available/000-default.conf

# ── Database CA certificate ─────────────────────────────────
# Render mounts a Secret File at /etc/secrets/<name> owned by
# root with group 1000 and no world-read bit. This script runs
# as root, so it can read it. Apache's workers drop to www-data,
# which is uid 33 and in neither - so it cannot.
#
# The failure that produces is genuinely confusing: the
# entrypoint migrates the database successfully, Apache starts,
# and then every single request fails with "DB_SSL_CA points at
# ... which does not exist or cannot be read", naming a file that
# is plainly there. Two different users, one path.
#
# So the certificate is staged somewhere www-data can read and
# DB_SSL_CA is re-pointed at the copy, which Apache inherits
# through the exec below.
#
# Nothing is weakened by the copy. A CA certificate is a public
# document - it is published precisely so that clients can check
# a server against it, and it is worthless to an attacker. The
# private key is the secret, and that never leaves the database
# provider. Only the private half of a keypair would deserve the
# permissions Render applies here.
#
# Staging it rather than adding www-data to group 1000 keeps this
# working if Render ever changes that gid, and keeps it correct
# on any other host.
CA_STAGED=/usr/local/share/examhub-db-ca.pem

if [ -n "${DB_SSL_CA:-}" ]; then
    if [ -r "${DB_SSL_CA}" ]; then
        install -m 0444 "${DB_SSL_CA}" "${CA_STAGED}"
        DB_SSL_CA="${CA_STAGED}"
        export DB_SSL_CA
        echo "entrypoint: database CA staged at ${CA_STAGED} for www-data"
    else
        # Do not fail here. The application raises a
        # ConfigurationException naming the path, which is a
        # better message than anything this script can give, and
        # it reaches the log the operator is already reading.
        echo "entrypoint: WARNING - DB_SSL_CA is '${DB_SSL_CA}' but that file cannot be read" >&2
    fi
fi

# ── Migrations ──────────────────────────────────────────────
# Opt-in, because running schema changes automatically on boot
# is only safe under conditions this deployment happens to meet
# and another might not: one instance, and migrations that are
# all re-runnable and backwards compatible with the code already
# serving.
#
# It runs before Apache rather than alongside it, so a broken
# migration is caught before a single request is served.
#
# Leave it off and run `php bin/migrate.php` yourself against
# the same DB_* values - the managed database is reachable from
# anywhere, so that needs no shell on the container.
#
# Two failures, told apart by exit code. A migration that broke
# partway through leaves a schema nobody can reason about, and
# that must stop the container. A database that simply did not
# answer has changed nothing at all - and exiting for it is what
# turned an idle free-tier database into a crash loop, because
# every cold start re-ran this and every one of them died.
#
# So an unreachable database now starts Apache anyway. The health
# check still fails, so Render will not route to it or let it
# replace a working deploy, and the instant the database answers
# the application serves normally with no redeploy and nobody
# woken up. A container returning 503 is strictly more useful
# than no container at all.
if [ "${RUN_MIGRATIONS:-false}" = "true" ]; then
    echo "entrypoint: applying database migrations"

    # set -e would take the exit code away before it can be read.
    set +e
    php /var/www/html/bin/migrate.php
    migrate_status=$?
    set -e

    # 75 is EX_TEMPFAIL: bin/migrate.php could not reach the
    # database and stopped before touching the schema.
    if [ "${migrate_status}" -eq 75 ]; then
        echo "entrypoint: WARNING - database unreachable, so no migrations ran." >&2
        echo "entrypoint: starting anyway; /healthz.php will report 503 until it answers." >&2
    elif [ "${migrate_status}" -ne 0 ]; then
        echo "entrypoint: migrations failed (exit ${migrate_status}); refusing to serve a half-applied schema" >&2
        exit "${migrate_status}"
    fi
fi

echo "entrypoint: serving ExamHub on 0.0.0.0:${PORT}"

# exec, so Apache becomes PID 1 and receives the platform's stop
# signal directly. Without it Apache would be a child of this
# shell, which ignores SIGTERM, and every deploy would wait out
# the platform's kill timeout before the old container died.
exec "$@"
