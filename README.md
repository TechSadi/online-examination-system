# ExamHub — Online Examination System

A full-stack online examination system built with **PHP 8.1+ and MySQL**, with no
framework and no Composer dependencies. Students register, take timed multiple-choice
exams and see their results; administrators manage exams, questions, students and other
administrators.

---

## Quick start (XAMPP)

### 1. Place the project

```
htdocs/
└── online-exam-system/     <- the whole folder goes here
```

The application no longer hardcodes its folder name, so any directory name works, as
does serving it from a virtual-host root.

### 2. Create the database

Import `database/schema.sql` via phpMyAdmin, or from the command line:

```bash
mysql -u root -p < database/schema.sql
```

If you already have a database from an earlier version, apply the migrations instead of
re-importing — see [Database](#database) below.

### 3. Configure the environment

```bash
cp .env.example .env
```

Then edit `.env` and set at least `DB_USER` and `DB_PASSWORD`.
**`.env` is git-ignored and must never be committed.**

### 4. Start Apache and MySQL, then open

```
http://localhost/online-exam-system/
```

### 5. Sign in

The schema seeds one administrator: username `admin`, password `admin123`.
**Change it immediately.** New administrators are created from inside the admin panel
(Manage Admins → Add New Admin); there is no public admin registration.

---

## Project structure

```
online-exam-system/
├── index.php                   Public home page (entry point)
├── .env.example                Environment template — copy to .env
├── .htaccess                   Denies web access to app/, database/, storage/, .env
│
├── app/
│   ├── bootstrap.php           Autoloader, config, error handling, session
│   ├── Config/config.php       Builds the config array from environment variables
│   ├── Core/                   Framework-ish plumbing
│   │   ├── Config.php          Dot-notation config reader
│   │   ├── Database.php        PDO connection + query helpers
│   │   ├── Env.php             .env parser
│   │   ├── ErrorHandler.php    Central error/exception handling and logging
│   │   ├── Flash.php           One-request messages, old input, validation errors
│   │   ├── Request.php         Typed request input
│   │   ├── Response.php        Redirect helpers
│   │   ├── Session.php         Session lifecycle and cookie hardening
│   │   ├── Url.php             URL generation
│   │   └── View.php            Template rendering with layouts
│   ├── Controllers/            One class per area; page files just dispatch here
│   │   ├── HomeController.php
│   │   ├── Admin/
│   │   └── Student/
│   ├── Middleware/Auth.php     requireStudent / requireAdmin / requireGuest
│   ├── Repositories/           All SQL lives here, one class per table
│   ├── Services/               Business rules (grading, authentication)
│   ├── Validation/Validator.php
│   ├── Helpers/functions.php   View helpers: e(), url(), asset(), percentage()
│   └── Views/
│       ├── layouts/            app.php (public/student), admin.php
│       ├── partials/           head, navbar, sidebar, footer, alerts
│       ├── admin/
│       └── student/
│
├── admin/                      Admin entry points (thin dispatchers)
├── student/                    Student entry points (thin dispatchers)
│
├── public/assets/
│   ├── css/style.css
│   └── js/{app.js, exam.js}
│
├── database/
│   ├── schema.sql              Full schema + seed data for a fresh install
│   └── migrations/             Incremental, re-runnable upgrades
│
└── storage/logs/               Application error log (git-ignored)
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
| `APP_URL` | Absolute base URL. Leave empty to auto-detect (recommended for XAMPP). |
| `APP_NAME` | Application name shown in page titles. |
| `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, `DB_PASSWORD`, `DB_CHARSET` | Database connection. |
| `SESSION_NAME` | Session cookie name. |
| `SESSION_SECRET` | Per-environment random string. Generate with `php -r "echo bin2hex(random_bytes(32));"`. |
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

`database/schema.sql` creates a fresh database. For an existing one, apply the
migrations in order:

```bash
mysql -u root -p online_exam_db < database/migrations/001_phase2_integrity.sql
mysql -u root -p online_exam_db < database/migrations/002_phase3_security.sql
```

Migrations are additive and safe to run against live data.

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
# Serve without Apache
php -S localhost:8000 -t .

# Syntax-check everything
find . -name "*.php" -not -path "./.git/*" -exec php -l {} ;
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

## Production configuration

Before deploying:

1. Set `APP_ENV=production` and `APP_DEBUG=false`.
2. Set a unique `SESSION_SECRET` and a real `DB_PASSWORD`.
3. Set `APP_URL` to the public HTTPS URL.
4. Serve over HTTPS; `Secure` cookies then switch on automatically.
5. Confirm `.htaccess` is honoured (`AllowOverride All`), so `app/`, `database/`,
   `storage/` and `.env` are not downloadable.
6. Ensure `storage/logs/` is writable by the web server.
7. **Change the seeded `admin` password** — `admin` / `admin123` is published in
   `schema.sql` and is a development convenience only.
8. Leave `APP_TRUST_PROXY=false` unless a reverse proxy sets `X-Forwarded-Proto`
   *and* strips any copy the client sent; otherwise a client can claim its own
   connection was secure.
9. Consider raising `BCRYPT_COST` if the server can afford it.

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
| Error disclosure | Generic page in production; detail only in the log |
| Secrets | Environment file, git-ignored |

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
- **The seeded `admin` / `admin123` account is documented in `schema.sql`.** It
  is a development convenience and must be changed before deployment.
- **`style-src` still allows inline**, because progress bars set their width
  with a style attribute, which a nonce cannot cover. Moving those widths into
  classes would let it be tightened.
- **No account lockout notification**, so a user is not told their account was
  targeted.
- **Throttle rows are purged opportunistically** on write rather than by a
  scheduled job.

---

*Built for educational purposes. © 2025 ExamHub*
