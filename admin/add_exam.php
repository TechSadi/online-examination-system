<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/app/bootstrap.php';

use App\Controllers\Admin\ExamController;
use App\Core\Request;

$controller = new ExamController();

Request::isPost() ? $controller->store() : $controller->create();
