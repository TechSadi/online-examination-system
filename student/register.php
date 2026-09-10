<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/app/bootstrap.php';

use App\Controllers\Student\AuthController;
use App\Core\Request;

$controller = new AuthController();

Request::isPost() ? $controller->register() : $controller->showRegister();
