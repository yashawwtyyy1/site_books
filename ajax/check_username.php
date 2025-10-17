<?php

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../includes/bootstrap.php';

$username = trim($_GET['username'] ?? '');
$exists = false;

if ($username !== '' && preg_match('/^[A-Za-z0-9_]+$/', $username)) {
    $exists = fetch_user_by_username($pdo, $username) !== null;
}

echo json_encode(['exists' => $exists], JSON_UNESCAPED_UNICODE);
