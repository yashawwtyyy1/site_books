<?php

declare(strict_types=1);

function current_user(PDO $pdo): ?array
{
    if (empty($_SESSION['user_id'])) {
        return null;
    }

    static $cached = null;
    if ($cached !== null) {
        return $cached;
    }

    $user = fetch_user($pdo, (int)$_SESSION['user_id']);
    $cached = $user ?: null;

    return $cached;
}

function is_logged_in(PDO $pdo): bool
{
    return current_user($pdo) !== null;
}

function is_admin(PDO $pdo): bool
{
    $user = current_user($pdo);

    return $user !== null && $user['role'] === 'admin';
}

function enforce_ban_if_needed(PDO $pdo): void
{
    $user = current_user($pdo);
    if ($user && (bool)$user['is_banned']) {
        session_destroy();
        session_start();
        $_SESSION['flash'] = 'Ваш аккаунт заблокирован администратором.';
    }
}

function require_login(PDO $pdo): void
{
    if (!is_logged_in($pdo)) {
        $_SESSION['flash'] = 'Необходимо авторизоваться для просмотра этой страницы.';
        redirect('/login.php');
    }
}

function require_admin(PDO $pdo): void
{
    require_login($pdo);

    if (!is_admin($pdo)) {
        $_SESSION['flash'] = 'Недостаточно прав для посещения этой страницы.';
        redirect('/');
    }
}

function attempt_login(PDO $pdo, string $username, string $password): bool
{
    $user = fetch_user_by_username($pdo, $username);

    if (!$user || !password_verify($password, $user['password_hash'])) {
        return false;
    }

    if ((bool)$user['is_banned']) {
        $_SESSION['flash'] = 'Ваш аккаунт заблокирован. Обратитесь к администратору.';
        return false;
    }

    $_SESSION['user_id'] = (int)$user['id'];

    return true;
}

/**
 * @param array<string, string> $data
 * @return array{success: bool, errors: array<string, string>}
 */
function register_user(PDO $pdo, array $data): array
{
    $errors = [];

    $firstName = trim($data['first_name'] ?? '');
    $lastName = trim($data['last_name'] ?? '');
    $username = trim($data['username'] ?? '');
    $email = trim($data['email'] ?? '');
    $password = $data['password'] ?? '';

    if ($firstName === '' || !preg_match('/^[\p{Cyrillic}A-Za-z\-\s]+$/u', $firstName)) {
        $errors['first_name'] = 'Имя может содержать только буквы, дефис и пробелы.';
    }

    if ($lastName === '' || !preg_match('/^[\p{Cyrillic}A-Za-z\-\s]+$/u', $lastName)) {
        $errors['last_name'] = 'Фамилия может содержать только буквы, дефис и пробелы.';
    }

    if ($username === '' || !preg_match('/^[A-Za-z0-9_]+$/', $username)) {
        $errors['username'] = 'Логин может содержать только латинские буквы, цифры и подчёркивания.';
    } elseif (fetch_user_by_username($pdo, $username)) {
        $errors['username'] = 'Пользователь с таким логином уже существует.';
    }

    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Введите корректный email.';
    } elseif (fetch_user_by_email($pdo, $email)) {
        $errors['email'] = 'Пользователь с таким email уже существует.';
    }

    if ($password === '' || strlen($password) < 6) {
        $errors['password'] = 'Пароль должен содержать не менее 6 символов.';
    }

    if ($errors) {
        return ['success' => false, 'errors' => $errors];
    }

    $stmt = $pdo->prepare('INSERT INTO users (first_name, last_name, username, email, password_hash, role) VALUES (:first_name, :last_name, :username, :email, :password_hash, :role)');
    $stmt->execute([
        'first_name' => $firstName,
        'last_name' => $lastName,
        'username' => $username,
        'email' => $email,
        'password_hash' => password_hash($password, PASSWORD_DEFAULT),
        'role' => 'user',
    ]);

    return ['success' => true, 'errors' => []];
}

function flash_message(): ?string
{
    if (empty($_SESSION['flash'])) {
        return null;
    }

    $message = $_SESSION['flash'];
    unset($_SESSION['flash']);

    return $message;
}
