<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

if (is_logged_in($pdo)) {
    redirect('/');
}

$error = null;

if (is_post()) {
    $token = $_POST['csrf_token'] ?? '';
    if (!validate_csrf($token)) {
        $error = 'Неверный токен безопасности, обновите страницу и попробуйте снова.';
    } else {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        if ($username === '' || $password === '') {
            $error = 'Введите логин и пароль.';
        } elseif (!attempt_login($pdo, $username, $password)) {
            $error = $_SESSION['flash'] ?? 'Неверный логин или пароль.';
            unset($_SESSION['flash']);
        } else {
            redirect('/');
        }
    }
}

$flash = flash_message();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Авторизация — Readlyst</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/app.css">
</head>
<body>
<div class="d-flex flex-wrap" style="min-height: 100vh; overflow: hidden;">
    <div class="col-12 col-md-6 p-0">
        <img src="/authorization/image-30.png" alt="background" class="img-fluid w-100 h-100" style="object-fit: cover;">
    </div>
    <div class="col-12 col-md-6 d-flex align-items-center justify-content-center bg-white p-5">
        <div class="w-100" style="max-width: 570px;">
            <div class="bg-light rounded-pill p-2 mb-5 d-flex justify-content-between">
                <a class="tab-btn inactive" href="/register.php">Регистрация</a>
                <div class="tab-btn active">Авторизация</div>
            </div>
            <?php if ($flash): ?>
                <div class="flash-message mb-4"><?= htmlspecialchars($flash, ENT_QUOTES) ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert alert-danger rounded-pill-xl text-center mb-4"><?= htmlspecialchars($error, ENT_QUOTES) ?></div>
            <?php endif; ?>
            <form method="post" novalidate>
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES) ?>">
                <div class="mb-4">
                    <label class="form-label text-secondary ms-3">Логин</label>
                    <div class="bg-form">
                        <input class="fw-bold text-dark form-control bg-transparent border-0" name="username" value="<?= htmlspecialchars($_POST['username'] ?? '', ENT_QUOTES) ?>" required>
                    </div>
                </div>
                <div class="mb-4">
                    <label class="form-label text-secondary ms-3">Пароль</label>
                    <div class="bg-form">
                        <input type="password" class="fw-bold text-dark form-control bg-transparent border-0" name="password" required>
                    </div>
                </div>
                <div class="d-grid mb-4">
                    <button type="submit" class="btn register-btn">Войти в аккаунт</button>
                </div>
                <div class="text-center text-secondary" style="font-size: 13px; letter-spacing: -0.27px;">
                    Нет аккаунта? <a href="/register.php" class="text-decoration-none">Зарегистрируйтесь</a>
                </div>
            </form>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
