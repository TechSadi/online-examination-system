<?php
define('ROOT', dirname(__DIR__));
require_once ROOT . '/includes/auth.php';
session_destroy();
header('Location: ' . BASE_URL . '/student/login.php');
exit;
