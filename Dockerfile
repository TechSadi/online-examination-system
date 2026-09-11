# ============================================================
#  ExamHub - production image
#
#  Render has no native PHP runtime; PHP services deploy as
#  containers. This is the simplest image that runs this
#  application correctly, and nothing more: one process, Apache
#  with mod_php, no process manager, no reverse proxy in front
#  of another web server, no framework runtime. The application
#  has no Composer dependencies, so there is no install step and
#  no vendor directory to build.
#
#  A single stage is used on purpose. Multi-stage exists to keep
#  build tooling out of the final image, and there is no build
#  tooling here - the "build" is a copy.
# ============================================================

FROM php:8.3-apache

# ── System packages ─────────────────────────────────────────
# The base image ships mbstring, json, session, PDO and
# pdo_sqlite. pdo_mysql is the one extension this application
# needs that is not already compiled in.
#
# The apt lists are removed in the same layer that created them;
# left behind they are dead weight in every published layer.
RUN set -eux; \
    docker-php-ext-install -j"$(nproc)" pdo_mysql; \
    docker-php-ext-enable opcache; \
    rm -rf /var/lib/apt/lists/*

# ── Apache ──────────────────────────────────────────────────
# mod_rewrite serves the 404 handler and mod_headers is used by
# the container vhost. Neither is enabled in the base image.
RUN a2enmod rewrite headers

COPY docker/apache-vhost.conf /etc/apache2/sites-available/000-default.conf
COPY docker/php.ini           /usr/local/etc/php/conf.d/examhub.ini

# ── Application ─────────────────────────────────────────────
# .dockerignore keeps .env, .git and local logs out of the
# image. Production configuration arrives as environment
# variables from Render, never baked into a layer - a layer is
# readable by anyone who can pull the image.
WORKDIR /var/www/html
COPY . /var/www/html

# Apache runs as www-data and must not be able to rewrite the
# code it is serving: a writable document root turns any file
# write bug into a way to install a web shell. Only the log
# directory is writable, and only because the file log channel
# is still available if someone sets LOG_CHANNEL=file.
RUN set -eux; \
    chown -R root:root /var/www/html; \
    chmod -R a=rX      /var/www/html; \
    mkdir -p           /var/www/html/storage/logs; \
    chown -R www-data:www-data /var/www/html/storage/logs; \
    chmod -R u=rwX,go=  /var/www/html/storage/logs

# ── Start ───────────────────────────────────────────────────
# Render assigns a port through $PORT and requires the server to
# bind 0.0.0.0 on it. Apache's Listen directive is static, so
# the entrypoint writes the assigned port in before handing over
# to Apache as PID 1.
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

EXPOSE 10000

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["apache2-foreground"]
