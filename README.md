# ExamHub — Online Examination System

A full-stack online examination system built with **PHP 8.1+ and MySQL**, with no
framework and no Composer dependencies. Students register, take timed multiple-choice
exams and see their results; administrators manage exams, questions, students and other
administrators.

Runs on XAMPP locally and as a container on Render in production, from the same source —
the difference between them is entirely environment variables.
See **[Production deployment — Render](#production-deployment--render)**.

---

## Quick start (XAMPP)

### 1. Place the project

```
htdocs/
└── online-exam-system/     <- the whole folder goes here
```

The application does not hardcode its folder name, so any directory name works, as does
serving it from a virtual-host root.

### 2. Configure the environment

```bash
cp .env.example .env
```

Edit `.env` and set at least `DB_USER` and `DB_PASSWORD`.
**`.env` is git-ignored and must never be committed.**

### 3. Create the database

Create an empty database, then let the migration runner build it:

```bash
mysql -u root -p -e "CREATE DATABASE online_exam_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

php bin/migrate.php --seed
```

`bin/migrate.php` keeps a ledger in the database and applies only what is missing, so it
is safe to run repeatedly and correct on a database several versions behind. `--seed`
adds two demo exams; leave it off for a database you intend to use for real.

### 4. Create an administrator

```bash
php bin/create-admin.php
```

It asks for a username, email and password, and writes only the bcrypt hash. There is no
default account and no password published anywhere in this repository — earlier versions
seeded `admin` / `admin123` from the schema file, which meant every deployment shipped
with the same known credential.

Run it again at any time to reset a password.

### 5. Start Apache and MySQL, then open

```
http://localhost/online-exam-system/
```

Further administrators are created from inside the admin panel
(Manage Admins → Add New Admin); there is no public admin registration.

---

## Project structure

```
online-exam-system/
├── index.php                   Public home page (entry point)
├── error.php                   404/403/500 handler (server-dispatched)
├── healthz.php                 Health check — 200 only when the database answers
├── theme.php                   Stores the light/dark preference (POST only)
├── robots.txt                  Keeps everything behind a sign-in out of search
├── .env.example                Environment template — copy to .env
├── .htaccess                   Denies web access to the non-public tree
│
├── Dockerfile                  Production image (php:8.3-apache)
├── render.yaml                 Render Blueprint — the service, declared
├── .dockerignore               Keeps .env and .git out of every image layer
├── docker/
│   ├── apache-vhost.conf       Container vhost: AllowOverride All, ErrorDocument
│   ├── php.ini                 Production PHP settings (opcache, no disclosure)
│   └── entrypoint.sh           Binds Apache to Render's $PORT, then execs it
│
├── bin/
│   ├── migrate.php             Applies pending migrations, with a ledger
│   ├── create-admin.php        Creates or resets an administrator
│   └── router.php              Makes `php -S` obey the same rules as Apache
│
├── app/
│   ├── bootstrap.php           Autoloader, config, error handling, session
│   ├── Config/config.php       Builds the config array from environment variables
│   ├── Core/                   Framework-ish plumbing
│   │   ├── Config.php          Dot-notation config reader
│   │   ├── ConfigurationException.php  A wrong setting, not a wrong world
│   │   ├── Database.php        PDO connection + query helpers, TLS, UTC
│   │   ├── Env.php             .env parser
│   │   ├── ErrorHandler.php    Central error/exception handling and logging
│   │   ├── ErrorPage.php       The 4xx/5xx pages, with a dependency-free fallback
│   │   ├── Flash.php           One-request messages, old input, validation errors
│   │   ├── Icons.php           The SVG icon set and its inline sprite
│   │   ├── Paginator.php       Page arithmetic for a listing
│   │   ├── Request.php         Typed request input
│   │   ├── Response.php        Redirect helpers
│   │   ├── Session.php         Session lifecycle and cookie hardening
│   │   ├── Sorter.php          Request-chosen sort, resolved against an allowlist
│   │   ├── Theme.php           Light / dark / system preference
│   │   ├── Url.php             URL generation and asset cache-busting
│   │   └── View.php            Template rendering with layouts
│   ├── Controllers/            One class per area; page files just dispatch here
│   │   ├── HomeController.php
│   │   ├── ThemeController.php
│   │   ├── Admin/
│   │   └── Student/
│   ├── Middleware/Auth.php     requireStudent / requireAdmin / requireGuest
│   ├── Repositories/           All SQL lives here, one class per table
│   ├── Services/               Business rules (grading, authentication)
│   ├── Validation/Validator.php
│   ├── Helpers/functions.php   View helpers: e(), url(), asset(), icon(), …
│   └── Views/
│       ├── layouts/            app.php (public/student/exam), admin.php, error.php
│       ├── partials/           head, topbar, account menu, sidebar, footer,
│       │                       alerts, confirm dialog, pagination, sort header
│       ├── admin/
│       └── student/
│
├── admin/                      Admin entry points (thin dispatchers)
├── student/                    Student entry points (thin dispatchers)
│
├── public/assets/
│   ├── css/                    Layered; see docs/design-system.md
│   │   ├── tokens.css          Design decisions, named. Draws nothing.
│   │   ├── base.css            Elements, typography, focus, motion, print
│   │   ├── components.css      The reusable component vocabulary
│   │   ├── layout.css          Application shell and responsive behaviour
│   │   ├── pages.css           Single-screen compositions
│   │   └── exam.css            The examination interface only
│   └── js/{app.js, exam.js}
│
├── docs/design-system.md       Tokens, components, accessibility, known gaps
│
├── database/
│   ├── migrations/             The schema, as an ordered, re-runnable history
│   │   ├── 000_baseline.sql    Everything, for a new database
│   │   ├── 001_phase2_integrity.sql
│   │   └── 002_phase3_security.sql
│   └── seeds/demo.sql          Optional demo exams — never loaded automatically
│
└── storage/logs/               Application error log, when LOG_CHANNEL=file
```

### How a request flows

```
Browser
  └─> student/dashboard.php          entry point: 2 lines
        └─> app/bootstrap.php        autoload, config, errors, session
        └─> DashboardController      authorisation, then orchestration
              ├─> Middleware\Auth    requireStudent()
              ├─> Repositories       all SQL
              ├─> Services           grading, pass/fail rules
              └─> View::render()     template + layout -> HTML
```

Every page in `admin/` and `student/` is a two-line dispatcher. Application logic lives
in `app/`, which is not web-accessible.

---

## Environment configuration

All environment-specific values come from `.env`; none are hardcoded in source.

| Variable | Purpose |
|---|---|
| `APP_ENV` | `local` or `production`. Production always hides error detail. |
| `APP_DEBUG` | Show detailed errors in the browser. Ignored when `APP_ENV=production`. |
| `APP_URL` | Absolute base URL. Leave empty — URLs are then generated root-relative, which is correct under XAMPP, at a virtual-host root and on Render, including after a custom domain is added. |
| `APP_NAME` | Application name shown in page titles. |
| `LOG_CHANNEL` | `file` writes to `storage/logs/app.log`; `stderr` writes to the process's error stream. Use `stderr` in a container — its filesystem does not survive a deploy. |
| `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, `DB_PASSWORD`, `DB_CHARSET` | Database connection. |
| `DB_TIMEOUT` | Seconds to wait for a connection. A managed database is a network hop away, not a local socket. |
| `DB_SSL` | Encrypt the connection. Required by every managed MySQL provider. |
| `DB_SSL_CA` | Path to the provider's CA certificate. When set, the server's certificate is **verified** against it and `DB_SSL` is implied. Without a CA the connection is encrypted but unverified. |
| `SESSION_NAME` | Session cookie name. |
| `SESSION_SECURE` | Force the `Secure` cookie flag. Enabled automatically over HTTPS. |
| `EXAM_PASS_MARK` | Percentage required to pass. Previously the literal `60` repeated in six files. |
| `EXAM_MAX_DURATION` | Maximum exam length in minutes. |

Real environment variables take precedence over `.env`, so server-level configuration
wins in production.

---

### Security-related variables

| Variable | Default | Purpose |
|---|---|---|
| `SESSION_IDLE_TIMEOUT` | `1800` | Seconds of inactivity before sign-out |
| `SESSION_ABSOLUTE_TIMEOUT` | `28800` | Hard ceiling on a session's life |
| `BCRYPT_COST` | `12` | Password hashing work factor; raise as hardware improves |
| `PASSWORD_MIN_LENGTH` | `8` | Minimum accepted at registration |
| `LOGIN_MAX_ATTEMPTS` | `5` | Failed sign-ins per identifier before lockout |
| `LOGIN_DECAY` | `900` | Length of both the counting window and the lockout |
| `EXAM_SUBMIT_GRACE` | `60` | Seconds a submission may arrive late and still count |
| `APP_TRUST_PROXY` | `false` | Honour `X-Forwarded-Proto`; enable **only** behind a proxy that strips the client's copy |

---

## Database

The schema is a set of ordered migration files, applied by a runner that records what it
has already done:

```bash
php bin/migrate.php            # apply everything pending
php bin/migrate.php --status   # show what is applied and what is not
php bin/migrate.php --seed     # also load database/seeds/demo.sql
```

The runner connects through the ordinary `DB_*` configuration, so the same command
targets the local MySQL during development and the managed host in production — there is
no second set of credentials to keep in step, and no step that involves opening
phpMyAdmin and pasting SQL.

Three properties make this safe to run anywhere:

- **A ledger.** `schema_migrations` records each applied file, so a second run is a no-op
  and the state of a database is a fact rather than a guess.
- **Every migration is re-runnable.** `000_baseline.sql` creates the whole schema for a
  new database; `001` and `002` bring a database created before Phase 2 or Phase 3
  forward, and are written with `information_schema` guards so they do nothing on a
  database that already has what they add.
- **No migration names a database.** There is no `CREATE DATABASE` and no `USE`: a
  managed provider hands you a database that already exists, under a name it chose, on a
  connection with no privilege to create another. `DB_NAME` selects it.

Verified against MySQL 8.0 on a fresh database and on a reconstructed pre-Phase-2 one:
columns widened, indexes and both check constraints created, existing results backfilled
into attempts, and a second run changing nothing.

Demo content is a separate seed and is never loaded automatically — a production database
should not come up already containing a quiz about the capital of France.

### Tables

| Table | Notes |
|---|---|
| `admins` | `username` and `email` unique |
| `students` | `email` unique |
| `exams` | `duration` in minutes |
| `questions` | 4 options; `correct_answer` constrained to 1–4; cascades from `exams` |
| `results` | one row per (student, exam); links to the attempt that produced it |
| `exam_attempts` | one sitting: `started_at`, `expires_at`, `status`, `score` |
| `attempt_answers` | what was chosen per question, and whether it was correct |
| `login_attempts` | failed sign-ins, for throttling |

`results` carries `UNIQUE (student_id, exam_id)` and `exam_attempts` carries
`UNIQUE (student_id, exam_id)`. Those two keys are what make one attempt per
exam a storage guarantee rather than a hopeful check in application code.

Attempt timestamps are `DATETIME` written by PHP in UTC. The database clock is
never consulted for an exam deadline, because the MySQL server's timezone is
not guaranteed to match the application's.

Migration 002 backfills an attempt for every result recorded before Phase 3, so
existing history stays consistent, and is safe to re-run.

---

## Development workflow

```bash
# Serve without Apache. The router is not optional: the built-in server reads
# no .htaccess, so without it .env is downloadable - database password and all -
# anything under app/ runs as a script, and an unknown URL returns the home page
# with a 200.
php -S localhost:8000 bin/router.php

# Syntax-check everything
find . -name "*.php" -not -path "./.git/*" -exec php -l {} ;

# Apply pending migrations / check what is pending
php bin/migrate.php
php bin/migrate.php --status
```

Conventions:

- Application classes are `App\...`, autoloaded from `app/`.
- **All SQL goes in a repository.** Controllers and views never build queries.
- **All output is escaped** with `e()`. Never echo a raw value.
- **All URLs come from `url()` or `asset()`.** Never hardcode a path.
- Business rules belong in `app/Services`, not in a template.
- Destructive actions are POST, never a GET link.
- **Every POST form includes `<?= csrf_field() ?>`.** The bootstrap rejects any
  state-changing request without a valid token, so a form that omits it will
  simply not work.
- Anything a student could gain by cheating is decided in `ExamService`, from
  the database — never from the request.

---

## Production deployment — Render

The production deployment is a **Docker web service on Render** talking to a **managed
MySQL database hosted elsewhere**.

### Why this shape

Render has no native PHP runtime — PHP services deploy as containers, which is what
`Dockerfile` is for. Render's managed databases are PostgreSQL and Key Value; there is no
managed MySQL, and this application is MySQL throughout: the schema, both migrations and
every repository. Rewriting all of that to reach a database Render happens to host would
be a far larger change, with a far larger blast radius, than pointing `DB_*` at a managed
MySQL somewhere else.

So the database is external, and the application knows nothing about which provider it
is. Aiven, TiDB Cloud, Clever Cloud and PlanetScale all work; moving between them is a
change of environment variables and nothing else.

```
                    ┌──────────────────────────────┐
   HTTPS            │ Render web service (Docker)  │
  ────────────────► │  php:8.3-apache              │
   TLS terminated   │  Apache on $PORT             │      TLS
   by Render        │  DocumentRoot = project root │ ───────────────►  Managed MySQL
                    │  /healthz.php gates deploys  │   DB_SSL[_CA]     (external)
                    └──────────────────────────────┘
```

### 1. Prerequisites

- A Render account, and this repository pushed to GitHub or GitLab.
- A managed **MySQL 5.7+ or MariaDB** database, created before the first deploy. A
  blueprint cannot create one, so this is the single manual step.
- PHP 8.1+ locally, only if you intend to run the migrations from your own machine.

### 2. Create the database

Create a MySQL instance with your provider of choice and note six things: **host, port,
database name, username, password**, and whether they publish a **CA certificate**.

Most managed providers require TLS and most publish a CA bundle. Download it if they do —
it is what turns "encrypted" into "encrypted and verified", which is the difference
between resisting eavesdropping and resisting an active attacker.

### 3. Create the Render service

**Render Dashboard → New → Blueprint**, and select this repository. Render reads
`render.yaml` and creates the service from it, which is why the deployment is reviewable
in a pull request rather than a set of dashboard settings nobody can diff.

It prompts for the five values marked `sync: false`:

```
DB_HOST      the managed database's host
DB_PORT      usually 3306
DB_NAME      the database name
DB_USER      the database user
DB_PASSWORD  that user's password
```

Everything else is already declared in `render.yaml`.

### 4. Attach the CA certificate (recommended)

If your provider publishes a CA bundle:

1. **Service → Environment → Secret Files → Add Secret File.**
2. Filename `mysql-ca.pem`, contents the PEM you downloaded.
3. Set `DB_SSL_CA` to the path Render shows for the file — normally
   `/etc/secrets/mysql-ca.pem`.

Leave `DB_SSL_CA` empty and `DB_SSL=true` if there is no bundle. The connection is still
encrypted; it just is not verified, and the application says as much in
`Database::tlsOptions()` rather than pretending otherwise.

If the path is wrong, the log names the path it tried — not a generic "check the DB_*
variables", because there are ten of them.

Nothing else is needed, but it is worth knowing what the entrypoint does with that file.
Render mounts a Secret File owned by `root` with group `1000` and no world-read bit. The
entrypoint runs as root and can read it; Apache's workers drop to `www-data`, which is
uid 33 and in neither, and cannot. Left alone that produces a genuinely baffling failure
— the database migrates successfully on boot, then every request reports a file that is
plainly there as unreadable. So `docker/entrypoint.sh` stages the certificate at
`/usr/local/share/examhub-db-ca.pem` with mode `0444` and re-points `DB_SSL_CA` at the
copy.

That copy weakens nothing. A CA certificate is a public document, published so that
clients can check a server against it; the private key is the secret and never leaves the
database provider.

### 5. Deploy

Render builds the image and starts it. Watch the logs for:

```
entrypoint: applying database migrations     (RUN_MIGRATIONS=true)
ExamHub database migrations
  applying 000_baseline.sql ...
entrypoint: serving ExamHub on 0.0.0.0:10000
```

`RUN_MIGRATIONS=true` applies pending migrations before Apache starts, so a failed
migration fails the start, `/healthz.php` never passes, and Render keeps the previous
container serving rather than replacing it with a half-migrated schema.

Prefer to run them yourself? Set `RUN_MIGRATIONS=false` and run this from your own
machine — the managed database is reachable from anywhere, so it needs no shell on the
container:

```bash
DB_HOST=... DB_PORT=... DB_NAME=... DB_USER=... DB_PASSWORD=... DB_SSL=true \
  php bin/migrate.php
```

### 6. Create the first administrator

There is no default account. Using the same `DB_*` values:

```bash
DB_HOST=... DB_PORT=... DB_NAME=... DB_USER=... DB_PASSWORD=... DB_SSL=true \
ADMIN_USERNAME=admin ADMIN_EMAIL=you@example.com ADMIN_NAME="Your Name" \
ADMIN_PASSWORD='a long unique password' \
  php bin/create-admin.php
```

Only the bcrypt hash is written. Run it again at any time to reset the password.

Further administrators are created from inside the admin panel.

### 7. Verify

```bash
curl -s https://<your-service>.onrender.com/healthz.php
# {"status": "ok", "checks": {"app": "ok", "database": "ok"}, ...}
```

Then, in a clean browser session (a private window — a stale cookie will hide a broken
session): register a student, sign in, sit an exam, submit it, read the result, sign out,
sign in as the administrator, create an exam, add a question, sign out.

Worth checking explicitly, because each has a production-only failure mode:

| Check | Why it can only fail in production |
|---|---|
| A URL that does not exist returns the styled 404 | `AllowOverride All`, without which Apache ignores `.htaccess` entirely |
| `/.env` and `/app/Core/Database.php` are refused | same rule — and without it the source tree is public |
| The session survives navigation | `Secure` cookies only work once `APP_TRUST_PROXY` lets the app see HTTPS |
| Stylesheets load | asset URLs are root-relative, so a wrong `APP_URL` breaks them |
| A wrong password five times locks out only that account | `APP_TRUST_PROXY` again — without it every user shares the load balancer's address |

### Updating

Push to the branch the service tracks; Render rebuilds and redeploys. The health check
gates the switchover, so a container that cannot reach its database never replaces a
working one.

A new migration is picked up automatically while `RUN_MIGRATIONS=true`.

### Configuration reference

Declared in `render.yaml`; override in the dashboard if you need to.

| Variable | Value | Why |
|---|---|---|
| `APP_ENV` | `production` | Forces `Config::isDebug()` to false whatever `APP_DEBUG` says |
| `APP_DEBUG` | `false` | No stack trace ever reaches a page |
| `APP_URL` | *(empty)* | URLs stay root-relative, so they are correct on `onrender.com` and remain correct after a custom domain |
| `APP_TRUST_PROXY` | `true` | Render terminates TLS and sets `X-Forwarded-Proto`. This is what turns on `Secure` cookies and HSTS, and what lets the login throttle see the real client address |
| `LOG_CHANNEL` | `stderr` | A container's filesystem is discarded on every deploy; Render collects stderr |
| `SESSION_SECURE` | `true` | Belt and braces alongside `APP_TRUST_PROXY` |
| `DB_SSL` | `true` | The database is reached over the public internet |
| `DB_SSL_CA` | *(path)* | Upgrades that from encrypted to verified |
| `RUN_MIGRATIONS` | `true` | Applies pending migrations before Apache starts |

> `APP_TRUST_PROXY=true` is correct **only** because Render sets and sanitises
> `X-Forwarded-Proto` itself. Never set it on a server exposed directly to the internet:
> a client could then assert its own connection was secure, and mint itself a fresh
> login-throttle counter on every request.

### Runtime requirements

| Requirement | Provided by |
|---|---|
| PHP 8.1+ (image uses 8.3) | `php:8.3-apache` |
| `pdo_mysql` | `docker-php-ext-install pdo_mysql` |
| `session`, `mbstring`, `json`, `filter`, `hash`, `openssl`, `PDO` | Compiled into the base image |
| `opcache` | Bundled but disabled by default; enabled in the Dockerfile |
| Apache `mod_rewrite`, `mod_headers` | `a2enmod` in the Dockerfile |
| `AllowOverride All` | `docker/apache-vhost.conf` |
| MySQL 5.7+ / MariaDB | External managed provider |

No Composer, no Node, no build step: the build is a copy.

### Render-specific notes

- **The document root is not writable.** It is owned by `root` and mounted read-only to
  `www-data`, because a writable document root turns any file-write bug into a way to
  install a web shell. Only `storage/logs/` is writable, and only so `LOG_CHANNEL=file`
  still works if someone chooses it.
- **The free instance type spins down after inactivity.** The first request after that
  takes roughly a minute while the container starts. That is a plan characteristic, not
  something to work around in code — a paid instance type removes it.
- **Sessions are on the container's local filesystem.** Fine at one instance; see
  [Operational readiness](#backups-and-operational-readiness) before scaling out.
- **Apache binds `$PORT`.** `docker/entrypoint.sh` writes the assigned port into
  `ports.conf` and the vhost, then `exec`s Apache so it becomes PID 1 and receives
  Render's stop signal directly instead of waiting out the kill timeout on every deploy.

---

## Backups and operational readiness

### Database backups

Backups are the database provider's, not Render's — Render hosts no part of the data.

Whatever provider you chose, before going live confirm: **that automated backups are on,
what the retention window is, and that you have actually restored one.** A backup nobody
has restored is a belief, not a backup.

A manual dump needs no special access, since the database is reachable from anywhere:

```bash
mysqldump --host=... --port=... --user=... --password --ssl-mode=REQUIRED \
  --single-transaction --routines --triggers <database> > examhub-$(date +%F).sql
```

`--single-transaction` takes the dump from one consistent snapshot without locking
writers out, which matters if anyone is sitting an exam.

Restoring:

```bash
mysql --host=... --user=... --password --ssl-mode=REQUIRED <database> < examhub-2026-01-01.sql
php bin/migrate.php   # bring a restored older dump up to the current schema
```

### What is not backed up, and does not need to be

The container holds nothing that matters. The image is rebuilt from the repository, the
configuration lives in Render's environment, and the only state is the database. Losing
the container costs a cold start.

### Sessions

Sessions are PHP files on the container's local disk. Two consequences worth stating
plainly rather than discovering:

- **A deploy signs everyone out.** The new container has none of the old one's session
  files. For an application where the longest interaction is an exam sitting, deploy
  when nobody is mid-paper.
- **Scaling beyond one instance needs a shared session store.** Two instances would
  bounce a user between two different sets of session files. Render offers Key Value for
  this, and PHP has a Redis session handler; it is a real change, not a setting, and it
  is not implemented here.

### Logging and monitoring

`LOG_CHANNEL=stderr` sends every handled exception, with its trace, to Render's log
stream, alongside Apache's access and error logs. What a visitor sees is
`ErrorPage::render(500)` — no message, no path, no trace.

`/healthz.php` is polled by Render and returns 503 when the database does not answer, so
a failing deploy is rejected rather than promoted.

There is **no alerting, no error aggregation and no uptime monitoring**. Render will
email about failed deploys; nothing watches for a rise in 500s. An external uptime check
against `/healthz.php` is the cheapest way to close that gap and is not set up here.

### Failure recovery

| Failure | What happens | What to do |
|---|---|---|
| Database unreachable | Pages return the styled 500; `/healthz.php` returns 503; the trace is in the log | Check the provider's status and the `DB_*` values |
| Bad deploy | Health check fails, Render keeps the previous container | Fix forward, or roll back from the Render dashboard |
| Migration fails | The container exits before Apache; the previous one keeps serving | Read the log — the runner names the file and the statement |
| Locked out of the admin account | — | Re-run `bin/create-admin.php` against the production database to reset the password |
| Credentials leaked | — | Rotate at the provider, update the Render environment, redeploy |
| Migrations succeed on boot, then every request says `DB_SSL_CA ... cannot be read` | The CA is readable by root but not by `www-data` | The entrypoint stages it at mode `0444` to prevent exactly this. If you see it, the staging did not run — check the log for `entrypoint: database CA staged at ...`, and unblock immediately by clearing `DB_SSL_CA` (encrypted but unverified) while you investigate |

---

## Production deployment checklist

```
[x] Phase 5 branch created directly from latest main
[x] Production environment variables declared in render.yaml
[x] .env.example created and current
[x] No secrets in the repository (all DB_* are sync:false)
[x] .gitignore and .dockerignore reviewed
[x] PHP/runtime requirements verified (8.3 in the image, 8.1+ supported)
[x] Required PHP extensions verified (pdo_mysql added; the rest are built in)
[x] Database schema deployable by a single reproducible command
[x] Migration runner verified on a fresh and a legacy database
[x] Database connection supports TLS and a pinned CA
[x] HTTPS handled by Render; APP_TRUST_PROXY set so the app sees it
[x] Secure, HttpOnly, SameSite cookies
[x] Debug mode disabled; APP_ENV=production overrides APP_DEBUG regardless
[x] Error logging to stderr, collected by the platform
[x] 404/403/500 pages implemented and tested under Apache
[x] CSRF enforced centrally and verified by test
[x] Authorisation verified for every entry point
[x] Database indexes verified with EXPLAIN
[x] Backup and restore documented
[x] Student workflow tested end to end
[x] Admin workflow tested end to end
[x] Exam submission and grading verified against the stored answer key
[x] Expired exam tested (late paper scores zero, answers kept)
[x] Duplicate submission tested (one result row, one attempt)
[x] Render configuration created (Dockerfile, render.yaml, vhost, entrypoint)
[ ] Render deployment performed          <- requires Render account access
[ ] Production URL verified
[ ] Production assets verified
[ ] Production authentication verified
[ ] Production database operations verified
[x] README updated
[x] Deployment instructions documented
[x] Final security review completed
```

The unchecked items are the ones that can only be done from the Render account itself.
Everything they depend on has been prepared and verified locally against an Apache
instance configured exactly like the container.

---

## Interface

The whole of the front end is six stylesheets and two scripts — no framework,
no build step, no webfont request. Design decisions live as tokens in
`public/assets/css/tokens.css`; a colour or a rhythm step is changed there
once rather than hunted through the pages that use it.

- **A real component set.** Buttons, fields, cards, tables, badges, alerts,
  dialogs, menus, pagination and breadcrumbs are defined once in
  `components.css`. A page composes them; it does not restyle them.
- **Icons are SVG, not emoji.** `icon('trash')` renders from an inline sprite,
  inherits `currentColor`, and is hidden from screen readers unless it carries
  meaning no label repeats.
- **WCAG AA throughout.** Every text pair in the palette meets 4.5:1 and every
  operable control's outline meets 3:1, measured rather than eyeballed. Colour
  is never the only signal.
- **Reconsidered at every breakpoint.** Tables restack as labelled blocks on a
  phone, the admin sidebar becomes a proper drawer, and the exam's controls
  move to a fixed bar under the thumb.
- **Light, dark and match-system**, chosen from the top bar. The preference is
  stamped onto `<html>` server-side from a cookie, so a page arrives already in
  the right colours instead of flashing white first.
- **Degrades without JavaScript**, including the exam itself and the theme
  picker.

Full reference, including the remaining known gaps:
**[docs/design-system.md](docs/design-system.md)**.

---

## Security posture

| Area | Implementation |
|---|---|
| CSRF | Session-bound token on every state-changing request, verified centrally in the bootstrap with `hash_equals`; rotated on sign-in and sign-out |
| SQL injection | PDO prepared statements everywhere, emulation disabled |
| Password storage | bcrypt at a configurable cost, re-hashed on sign-in when policy changes; 8–72 byte policy |
| Brute force | Per-identifier and per-address lockout over a moving window, counted in the database |
| Output escaping | `e()` (`htmlspecialchars` with `ENT_QUOTES`) on all output |
| Content Security Policy | `script-src 'self'` — no inline script anywhere in the view layer |
| Authorisation | Server-side guards that terminate the request on failure |
| Object scoping | Every student query filters by session id; questions scope to their exam |
| Exam integrity | Server-owned attempts: pinned deadline, server-side grading, one attempt per exam |
| Duplicate submission | Status predicate on the attempt update, plus unique keys on `results` |
| Transactions | Attempt close, answer storage and result creation commit together or not at all |
| Session | Idle and absolute timeouts, periodic id rotation, `HttpOnly`, `SameSite=Lax`, strict mode, `Secure` on HTTPS |
| Destructive actions | POST-only, including sign-out |
| Error disclosure | The application's own 404/403/500 pages in production; message, path and trace only in the log. `X-Powered-By` and the server version are suppressed |
| Path disclosure | Non-public tree, dotfiles, SQL, logs, deployment configuration and the Dockerfile all refused; `php -S` enforces the same list through `bin/router.php` |
| Transport | TLS to the database, with the provider's CA pinned when one is published |
| Client identity | `X-Forwarded-For` honoured only where `APP_TRUST_PROXY` declares a proxy, and validated as an address before use |
| Search indexing | Everything behind a sign-in is `noindex, nofollow`, with `robots.txt` saying the same at the door |
| Secrets | Environment variables. No credential is committed, and no account ships with a default password |

### How exam integrity works

A sitting is a row in `exam_attempts`, created when the student opens the exam:

```text
open take_exam.php
  -> attempt created, expires_at = now + exam.duration   (pinned)
  -> page receives seconds_remaining, purely to draw a countdown

submit
  -> attempt must exist, belong to this student, and still be in progress
  -> now > expires_at + grace ?  recorded as expired, scores zero
  -> graded against the database answer key
  -> attempt closed, answers stored, result written   (one transaction)
```

The client-side countdown has no authority. Editing it, stopping it, or
blocking `exam.js` entirely buys no extra time, because the deadline is
re-checked on the server when the answers arrive. A short, configurable grace
window (`EXAM_SUBMIT_GRACE`) absorbs the flight time of the client's own
auto-submit so a slow connection does not cost a student their paper.

Only questions belonging to the exam are graded, and only a clean `1`–`4`
counts, so a crafted submission cannot introduce another exam's questions or
assert its own score. Per-question answers are stored, so a score can be
audited rather than taken on trust.

### Remaining limitations

- **Answers are only stored on submission.** If a student closes the browser
  mid-exam, the attempt expires and scores zero; there is no autosave. Adding
  one means an authenticated endpoint that writes answers as they are chosen.
- **One administrator role.** Any admin can do anything an admin can do; there
  are no granular permissions.
- **No email verification or password reset**, so an account is only as
  recoverable as its password.
- **`style-src` still allows inline**, because progress bars, the score ring
  and the pagination window set a numeric value with a style attribute, which
  a nonce cannot cover. Moving those into classes would let it be tightened.
- **No account lockout notification**, so a user is not told their account was
  targeted.
- **Throttle rows are purged opportunistically** on write rather than by a
  scheduled job.
- **Sessions live on the container's local disk**, so a deploy signs everyone
  out and running more than one instance would need a shared session store.
  See [Backups and operational readiness](#backups-and-operational-readiness).
- **Nothing watches for a rise in 500s.** Errors reach the platform's log
  stream and the health check gates deploys, but there is no aggregation and
  no alerting.
- **A misconfigured `DB_PORT` coerces to `0`** and MySQL then falls back to its
  default, so a typo can connect successfully to the wrong place rather than
  failing loudly.

---

*Built for educational purposes. © 2025 ExamHub*
