<?php
/**
 * db.php – PDO database connection (singleton helper)
 * Edit DB_HOST, DB_NAME, DB_USER, DB_PASS to match your environment.
 */

define('DB_HOST', 'localhost');
define('DB_NAME', 'online_exam_db');
define('DB_USER', 'root');
define('DB_PASS', 'Quantum');       
define('DB_CHARSET', 'utf8mb4');

function getDB(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            // In production, log the error instead of displaying it.
            die('<p style="color:red;font-family:sans-serif">Database connection failed: '
                . htmlspecialchars($e->getMessage()) . '</p>');
        }
    }
    return $pdo;
}
