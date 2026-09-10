# 📝 ExamHub – Online Examination System

A full-stack Online Examination System built with **PHP + MySQL** (backend) and
**HTML5 / CSS3 / JavaScript** (frontend). Designed for college projects and
compatible with **XAMPP / WAMP**.

---

## 🚀 Quick Setup (XAMPP)

### 1. Copy project
```
htdocs/
└── online-exam-system/   ← place the whole folder here
```

### 2. Import the database
1. Open **phpMyAdmin** → `http://localhost/phpmyadmin`
2. Click **Import** → choose `database.sql`
3. Click **Go**

### 3. Configure DB connection (if needed)
Edit `includes/db.php`:
```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'online_exam_db');
define('DB_USER', 'root');
define('DB_PASS', '');      // XAMPP default has no password
```

### 4. Start XAMPP
Start **Apache** and **MySQL** from the XAMPP control panel.

### 5. Open in browser
```
http://localhost/online-exam-system/
```

---

## 🔐 Default Credentials

| Role    | Username / Email | Password  |
|---------|-----------------|-----------|
| Admin   | admin           | admin123  |
| Student | Register via UI | —         |

> ⚠️ Change the admin password after your first login!

---

## 📁 Project Structure

```
online-exam-system/
├── index.php                   ← Public home page
├── database.sql                ← Database schema + seed data
│
├── includes/
│   ├── db.php                  ← PDO connection helper
│   ├── auth.php                ← Session & role helpers
│   ├── header.php              ← Shared HTML head + navbar
│   └── footer.php              ← Shared footer + JS includes
│
├── css/
│   └── style.css               ← Global stylesheet
│
├── js/
│   ├── app.js                  ← General UI helpers
│   └── exam.js                 ← Exam timer & question navigation
│
├── student/
│   ├── register.php            ← Student self-registration
│   ├── login.php               ← Student login
│   ├── logout.php              ← Student logout
│   ├── dashboard.php           ← Student dashboard + stats
│   ├── exams.php               ← Browse available exams
│   ├── take_exam.php           ← Live exam interface
│   ├── submit_exam.php         ← Grade + save result (POST handler)
│   ├── result.php              ← Single exam result view
│   └── results.php             ← Full results history
│
└── admin/
    ├── login.php               ← Admin login
    ├── logout.php              ← Admin logout
    ├── dashboard.php           ← Admin dashboard + stats
    ├── admin_sidebar.php       ← Reusable sidebar nav
    ├── exams.php               ← List / delete exams
    ├── add_exam.php            ← Create new exam
    ├── edit_exam.php           ← Edit existing exam
    ├── questions.php           ← Add / edit / delete questions
    ├── students.php            ← View / delete students
    └── results.php             ← View all results (filterable)
```

---

## ✨ Features

### Student
- Register & login with hashed passwords
- Browse available exams with question count & duration
- Take exam with per-question navigation
- Countdown timer with colour warnings (yellow < 5 min, red < 1 min)
- Auto-submit on timer expiry
- Instant score on submission
- View full results history

### Admin
- Secure admin login
- Dashboard with system-wide stats
- Create / Edit / Delete exams
- Add / Edit / Delete MCQ questions with 4 options and a marked correct answer
- View all registered students, their attempts and average scores
- View all results (filterable by student or exam)
- Delete student accounts

---

## 🔒 Security

| Feature | Implementation |
|---------|---------------|
| Password hashing | `password_hash()` / `password_verify()` (bcrypt) |
| SQL injection prevention | PDO prepared statements throughout |
| Session security | `session_regenerate_id()` on login |
| Route protection | `requireAdmin()` / `requireStudent()` guards |
| XSS prevention | `htmlspecialchars()` on all output |
| CSRF | Form re-submission prevented via POST-Redirect-GET |

---

## 🛠️ Tech Stack

| Layer      | Technology                    |
|-----------|-------------------------------|
| Frontend  | HTML5, CSS3, Vanilla JS        |
| Backend   | PHP 7.4+                       |
| Database  | MySQL 5.7+ / MariaDB           |
| Auth      | PHP Sessions                   |
| Fonts     | DM Serif Display + DM Sans (Google Fonts) |

---

## 📋 Database Schema

```
admins      (admin_id, username, password)
students    (student_id, name, email, password, created_at)
exams       (exam_id, title, description, duration, created_at)
questions   (question_id, exam_id, question_text, option1–4, correct_answer)
results     (result_id, student_id, exam_id, score, total, date_taken)
```

---

*Built for educational purposes. © 2025 ExamHub*
