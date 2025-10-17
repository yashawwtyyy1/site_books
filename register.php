<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

if (is_logged_in($pdo)) {
    redirect('/');
}

$errors = [];
$success = false;

if (is_post()) {
    $token = $_POST['csrf_token'] ?? '';
    if (!validate_csrf($token)) {
        $errors['csrf'] = 'Неверный токен безопасности, обновите страницу.';
    } else {
        $data = [
            'first_name' => $_POST['first_name'] ?? '',
            'last_name' => $_POST['last_name'] ?? '',
            'username' => $_POST['username'] ?? '',
            'email' => $_POST['email'] ?? '',
            'password' => $_POST['password'] ?? '',
        ];

        $agreement = isset($_POST['agreement']);
        $passwordConfirmation = $_POST['password_confirmation'] ?? '';

        if ($data['password'] !== $passwordConfirmation) {
            $errors['password_confirmation'] = 'Пароли должны совпадать.';
        }

        if (!$agreement) {
            $errors['agreement'] = 'Необходимо согласие на обработку персональных данных.';
        }

        if (!$errors) {
            $result = register_user($pdo, $data);
            $errors = $result['errors'];
            $success = $result['success'];
        }

        if ($success) {
            $_SESSION['flash'] = 'Регистрация завершена. Авторизуйтесь для входа.';
            redirect('/login.php');
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
    <title>Регистрация — Readlyst</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/app.css">
</head>
<body>
<div class="d-flex flex-wrap" style="min-height: 100vh; overflow: hidden;">
    <div class="col-12 col-md-6 p-0">
        <img src="/register/image-20.png" alt="background" class="img-fluid w-100 h-100" style="object-fit: cover;">
    </div>
    <div class="col-12 col-md-6 d-flex align-items-center justify-content-center bg-white p-5">
        <div class="w-100" style="max-width: 570px;">
            <div class="bg-light rounded-pill p-2 mb-5 d-flex justify-content-between">
                <div class="tab-btn active">Регистрация</div>
                <a class="tab-btn inactive" href="/login.php">Авторизация</a>
            </div>
            <?php if (!empty($errors['csrf'])): ?>
                <div class="alert alert-danger rounded-pill-xl text-center mb-4"><?= htmlspecialchars($errors['csrf'], ENT_QUOTES) ?></div>
            <?php endif; ?>
            <?php if ($flash): ?>
                <div class="flash-message mb-4"><?= htmlspecialchars($flash, ENT_QUOTES) ?></div>
            <?php endif; ?>
            <form method="post" id="register-form" novalidate>
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES) ?>">
                <div class="mb-4">
                    <label class="form-label text-secondary ms-3" for="last_name">Фамилия</label>
                    <div class="bg-form<?= !empty($errors['last_name']) ? ' is-invalid' : '' ?>">
                        <input id="last_name" name="last_name" class="fw-bold text-dark form-control bg-transparent border-0<?= !empty($errors['last_name']) ? ' is-invalid' : '' ?>" value="<?= htmlspecialchars($_POST['last_name'] ?? '', ENT_QUOTES) ?>" required>
                        <div class="invalid-feedback small text-danger"><?= htmlspecialchars($errors['last_name'] ?? '', ENT_QUOTES) ?></div>
                    </div>
                </div>
                <div class="mb-4">
                    <label class="form-label text-secondary ms-3" for="first_name">Имя</label>
                    <div class="bg-form<?= !empty($errors['first_name']) ? ' is-invalid' : '' ?>">
                        <input id="first_name" name="first_name" class="fw-bold text-dark form-control bg-transparent border-0<?= !empty($errors['first_name']) ? ' is-invalid' : '' ?>" value="<?= htmlspecialchars($_POST['first_name'] ?? '', ENT_QUOTES) ?>" required>
                        <div class="invalid-feedback small text-danger"><?= htmlspecialchars($errors['first_name'] ?? '', ENT_QUOTES) ?></div>
                    </div>
                </div>
                <div class="mb-4">
                    <label class="form-label text-secondary ms-3" for="username">Логин</label>
                    <div class="bg-form<?= !empty($errors['username']) ? ' is-invalid' : '' ?>">
                        <input id="username" name="username" class="fw-bold text-dark form-control bg-transparent border-0<?= !empty($errors['username']) ? ' is-invalid' : '' ?>" value="<?= htmlspecialchars($_POST['username'] ?? '', ENT_QUOTES) ?>" required>
                        <div id="username-feedback" class="small text-success mt-1"></div>
                        <div class="invalid-feedback small text-danger"><?= htmlspecialchars($errors['username'] ?? '', ENT_QUOTES) ?></div>
                    </div>
                </div>
                <div class="mb-4">
                    <label class="form-label text-secondary ms-3" for="email">Email-адрес</label>
                    <div class="bg-form<?= !empty($errors['email']) ? ' is-invalid' : '' ?>">
                        <input id="email" name="email" class="fw-bold text-dark form-control bg-transparent border-0<?= !empty($errors['email']) ? ' is-invalid' : '' ?>" value="<?= htmlspecialchars($_POST['email'] ?? '', ENT_QUOTES) ?>" required>
                        <div class="invalid-feedback small text-danger"><?= htmlspecialchars($errors['email'] ?? '', ENT_QUOTES) ?></div>
                    </div>
                </div>
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label class="form-label text-secondary ms-3" for="password">Пароль</label>
                        <div class="bg-form<?= !empty($errors['password']) ? ' is-invalid' : '' ?>">
                            <input type="password" id="password" name="password" class="fw-bold text-dark form-control bg-transparent border-0<?= !empty($errors['password']) ? ' is-invalid' : '' ?>" required>
                            <div class="invalid-feedback small text-danger"><?= htmlspecialchars($errors['password'] ?? '', ENT_QUOTES) ?></div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label text-secondary ms-3" for="password_confirmation">Повторите пароль</label>
                        <div class="bg-form<?= !empty($errors['password_confirmation']) ? ' is-invalid' : '' ?>">
                            <input type="password" id="password_confirmation" name="password_confirmation" class="fw-bold text-dark form-control bg-transparent border-0<?= !empty($errors['password_confirmation']) ? ' is-invalid' : '' ?>" required>
                            <div class="invalid-feedback small text-danger"><?= htmlspecialchars($errors['password_confirmation'] ?? '', ENT_QUOTES) ?></div>
                        </div>
                    </div>
                </div>
                <div class="d-grid mb-4">
                    <button type="submit" class="btn register-btn">Зарегистрироваться</button>
                </div>
                <div class="d-flex align-items-center gap-3">
                    <input class="form-check-input" type="checkbox" id="agreement" name="agreement" value="1" <?= isset($_POST['agreement']) ? 'checked' : '' ?>>
                    <div class="text-secondary" style="font-size: 13px; letter-spacing: -0.27px;">
                        Я ознакомился с <a href="/confident/index.html" class="text-decoration-none">политикой конфиденциальности</a> и даю согласие на обработку персональных данных
                    </div>
                </div>
                <?php if (!empty($errors['agreement'])): ?>
                    <div class="text-danger small mt-2"><?= htmlspecialchars($errors['agreement'], ENT_QUOTES) ?></div>
                <?php endif; ?>
            </form>
        </div>
    </div>
</div>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="/assets/js/register.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
