<?php

declare(strict_types=1);

const DB_HOST = '127.0.0.1';
const DB_PORT = 3306;
const DB_NAME = 'readlyst';
const DB_USER = 'root';
const DB_PASSWORD = '';

function get_pdo(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', DB_HOST, DB_PORT, DB_NAME);

    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];

    $pdo = new PDO($dsn, DB_USER, DB_PASSWORD, $options);

    return $pdo;
}
