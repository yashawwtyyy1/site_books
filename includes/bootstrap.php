<?php

declare(strict_types=1);

session_start();

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/auth.php';

$pdo = get_pdo();

ensure_database_migrated($pdo);

enforce_ban_if_needed($pdo);
