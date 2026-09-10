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

## Database

`database/schema.sql` creates a fresh database. For an existing one, apply the
migrations in order:

```bash
mysql -u root -p online_exam_db < database/migrations/001_phase2_integrity.sql
```

Migrations are additive and safe to run against live data.

### Tables

| Table | Notes |
|---|---|
| `admins` | `username` and `email` unique |
| `students` | `email` unique |
| `exams` | `duration` in minutes |
| `questions` | 4 options; `correct_answer` constrained to 1–4; cascades from `exams` |
| `results` | one row per (student, exam); cascades from `students` and `exams` |

`results` carries `UNIQUE (student_id, exam_id)`, which is what makes a duplicate
submission a harmless no-op rather than a second score.

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
7. Change the seeded `admin` password.

---

## Security posture

Implemented:

| Area | Implementation |
|---|---|
| SQL injection | PDO prepared statements everywhere, emulation disabled |
| Password storage | `password_hash()` / `password_verify()` (bcrypt) |
| Output escaping | `e()` (`htmlspecialchars` with `ENT_QUOTES`) on all output |
| Authorisation | Server-side guards that terminate the request on failure |
| Object scoping | Every student query filters by session id; questions scope to their exam |
| Grading | Server-authoritative, from the database answer key |
| Session | Regenerated on login, `HttpOnly`, `SameSite=Lax`, strict mode, `Secure` on HTTPS |
| Destructive actions | POST-only |
| Error disclosure | Generic page in production; detail only in the log |
| Secrets | Environment file, git-ignored |

Known gaps, scheduled for the next phase:

- **No CSRF tokens yet.** State-changing POSTs are not yet token-protected.
- **Exam timing is still client-side.** The server does not record when an attempt
  started, so it cannot reject a late submission.
- No rate limiting on login.
- No per-question answer storage, so results cannot show a per-question review.

---

*Built for educational purposes. © 2025 ExamHub*
