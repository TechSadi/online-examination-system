<?php
/**
 * auth.php – Session helpers for both admin and student roles.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/* ── Admin helpers ─────────────────────────────────────── */

function isAdminLoggedIn(): bool {
    return isset($_SESSION['admin_id']);
}

/** Redirect to admin login if not authenticated. */
function requireAdmin(): void {
    if (!isAdminLoggedIn()) {
        header('Location: ' . BASE_URL . '/admin/login.php');
        exit;
    }
}

/* ── Student helpers ────────────────────────────────────── */

function isStudentLoggedIn(): bool {
    return isset($_SESSION['student_id']);
}

/** Redirect to student login if not authenticated. */
function requireStudent(): void {
    if (!isStudentLoggedIn()) {
        header('Location: ' . BASE_URL . '/student/login.php');
        exit;
    }
}

/* ── Base URL helper ────────────────────────────────────── */

if (!defined('BASE_URL')) {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host     = $_SERVER['HTTP_HOST'] ?? 'localhost';
    // Strip everything after /online-exam-system  (works in XAMPP htdocs)
    $script   = dirname($_SERVER['SCRIPT_NAME']);
    $base     = '';
    if (preg_match('#(.*online-exam-system)#i', $script, $m)) {
        $base = $m[1];
    }
    define('BASE_URL', $protocol . '://' . $host . $base);
}
