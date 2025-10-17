<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

session_destroy();
session_start();
$_SESSION['flash'] = 'Вы вышли из аккаунта.';

redirect('/');
