<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

$query = trim($_GET['q'] ?? '');
$genreId = isset($_GET['genre']) ? (int)$_GET['genre'] : null;

$filters = [
    'query' => $query !== '' ? $query : null,
    'genre_id' => $genreId ?: null,
];

$genres = fetch_genres($pdo);
$books = fetch_books($pdo, $filters);
$user = current_user($pdo);
$flash = flash_message();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Readlyst — книжный каталог</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/home/style.css">
    <link rel="stylesheet" href="/assets/css/app.css">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
</head>
<body>
<header class="nav-wrap">
    <div class="container py-3">
        <div class="d-flex align-items-center gap-3 flex-wrap">
            <div class="d-flex align-items-center gap-2">
                <img src="/home/product-icons1.svg" alt="Readlyst" width="36" height="36">
                <span class="brand-title">Readlyst</span>
            </div>
            <a href="#catalog" class="catalog-btn d-flex align-items-center">
                <img src="/home/frame-2131330034-10.svg" alt="Каталог" width="16" height="16">
                Каталог
            </a>
            <form class="search-pill flex-grow-1" method="get" action="/">
                <img src="/home/vector0.svg" alt="Поиск" width="16" height="16">
                <input type="search" name="q" value="<?= htmlspecialchars($query, ENT_QUOTES) ?>" class="form-control border-0 bg-transparent" placeholder="Поиск по названию, автору или жанру">
            </form>
            <?php if ($user): ?>
                <div class="ms-auto d-flex align-items-center gap-3">
                    <div class="rounded-circle bg-secondary text-white d-flex align-items-center justify-content-center" style="width:44px;height:44px;">
                        <?= strtoupper(mb_substr($user['first_name'], 0, 1)) ?>
                    </div>
                    <div class="d-flex flex-column">
                        <strong><?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name'], ENT_QUOTES) ?></strong>
                        <div class="d-flex gap-2">
                            <?php if (is_admin($pdo)): ?>
                                <a class="nav-link p-0" href="/admin/index.php">Админ-панель</a>
                            <?php endif; ?>
                            <a class="nav-link p-0 text-danger" href="/logout.php">Выйти</a>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <div class="ms-auto d-flex gap-2">
                    <a class="login-btn" href="/login.php">Войти</a>
                    <a class="login-btn" style="background:#0f172a" href="/register.php">Регистрация</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</header>

<main>
    <section class="py-5">
        <div class="container">
            <?php if ($flash): ?>
                <div class="flash-message mb-4"><?= htmlspecialchars($flash, ENT_QUOTES) ?></div>
            <?php endif; ?>

            <div class="hero p-4 p-lg-5 d-flex flex-column flex-lg-row gap-4 align-items-center">
                <div class="hero__left">
                    <div class="hero__chip text-uppercase">Онлайн каталог</div>
                    <h1 class="hero__title">Читайте и открывайте новое</h1>
                    <p class="hero__sub">Readlyst — ваш универсальный помощник по поиску, сортировке и изучению книг. Добавляйте новые издания, выбирайте любимых авторов и открывайте новые жанры.</p>
                    <div class="d-flex gap-3 flex-wrap">
                        <a href="#catalog" class="book__read text-decoration-none">К каталогу</a>
                        <span class="badge-muted">Сотни книг внутри</span>
                    </div>
                </div>
                <div class="position-relative">
                    <img src="/home/_30-300.png" class="hero-mask m0 d-none d-lg-block" alt="">
                    <img src="/home/div29.png" alt="Read more" class="img-fluid rounded-4" style="max-width:280px;">
                </div>
            </div>
        </div>
    </section>

    <section id="catalog" class="pb-5">
        <div class="container">
            <div class="d-flex flex-column flex-lg-row gap-3 justify-content-between align-items-lg-center mb-4">
                <h2 class="sec-title m-0">Каталог книг</h2>
                <form class="d-flex gap-3 flex-wrap align-items-center" method="get" action="/">
                    <input type="hidden" name="q" value="<?= htmlspecialchars($query, ENT_QUOTES) ?>">
                    <label class="text-muted small mb-0" for="genre">Сортировка по жанрам</label>
                    <select id="genre" class="form-select rounded-pill" name="genre" onchange="this.form.submit()" style="min-width:220px">
                        <option value="">Все жанры</option>
                        <?php foreach ($genres as $genre): ?>
                            <option value="<?= (int)$genre['id'] ?>" <?= $genreId === (int)$genre['id'] ? 'selected' : '' ?>><?= htmlspecialchars($genre['name'], ENT_QUOTES) ?></option>
                        <?php endforeach; ?>
                    </select>
                </form>
            </div>

            <?php if (empty($books)): ?>
                <div class="alert alert-light border">По заданным условиям ничего не найдено. Попробуйте изменить запрос.</div>
            <?php else: ?>
                <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 g-4">
                    <?php foreach ($books as $book): ?>
                        <div class="col">
                            <article class="book book-card h-100">
                                <div class="book__thumb bg-card-1">
                                    <div class="ratio ratio-2x3">
                                        <img src="<?= htmlspecialchars(book_cover_url($book), ENT_QUOTES) ?>" alt="<?= htmlspecialchars($book['title'], ENT_QUOTES) ?>" class="img-fluid">
                                    </div>
                                </div>
                                <div>
                                    <div class="book__title" title="<?= htmlspecialchars($book['title'], ENT_QUOTES) ?>"><?= htmlspecialchars($book['title'], ENT_QUOTES) ?></div>
                                    <div class="book__meta"><?= htmlspecialchars($book['author_name'], ENT_QUOTES) ?><br><?= htmlspecialchars($book['genre_name'], ENT_QUOTES) ?> · <?= htmlspecialchars(format_year($book['release_year'] !== null ? (int)$book['release_year'] : null), ENT_QUOTES) ?></div>
                                </div>
                                <div class="book__actions">
                                    <a class="book__read text-decoration-none" href="/book.php?id=<?= (int)$book['id'] ?>">Подробнее</a>
                                </div>
                            </article>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </section>
</main>

<footer class="py-5 bg-light mt-auto">
    <div class="container d-flex flex-column flex-md-row justify-content-between gap-3">
        <div>
            <strong class="brand-title">Readlyst</strong>
            <p class="text-muted small mb-0">Онлайн сервис книжного каталога с удобным управлением и доступом для администраторов и пользователей.</p>
        </div>
        <div class="text-muted small">
            <div><a href="/register.php" class="text-muted">Регистрация</a></div>
            <div><a href="/login.php" class="text-muted">Авторизация</a></div>
            <div><a href="/confident/index.html" class="text-muted">Политика конфиденциальности</a></div>
        </div>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
