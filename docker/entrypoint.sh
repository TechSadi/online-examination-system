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

# ── Migrations ──────────────────────────────────────────────
# Opt-in, because running schema changes automatically on boot
# is only safe under conditions this deployment happens to meet
# and another might not: one instance, and migrations that are
# all re-runnable and backwards compatible with the code already
# serving.
#
# It runs before Apache rather than alongside it, so a failed
# migration fails the start, the health check never passes, and
# Render keeps the previous container serving instead of
# replacing it with one whose schema is half applied.
#
# Leave it off and run `php bin/migrate.php` yourself against
# the same DB_* values - the managed database is reachable from
# anywhere, so that needs no shell on the container.
if [ "${RUN_MIGRATIONS:-false}" = "true" ]; then
    echo "entrypoint: applying database migrations"
    php /var/www/html/bin/migrate.php
fi

echo "entrypoint: serving ExamHub on 0.0.0.0:${PORT}"

# exec, so Apache becomes PID 1 and receives the platform's stop
# signal directly. Without it Apache would be a child of this
# shell, which ignores SIGTERM, and every deploy would wait out
# the platform's kill timeout before the old container died.
exec "$@"
