<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

require_login($pdo);

$bookId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$book = $bookId > 0 ? fetch_book($pdo, $bookId) : null;

if (!$book) {
    http_response_code(404);
    $message = 'Книга не найдена.';
}

$user = current_user($pdo);
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($book) ? htmlspecialchars($book['title'], ENT_QUOTES) . ' — Readlyst' : 'Книга не найдена' ?></title>
    <script src="https://cdn.tailwindcss.com/3.2.0"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        ink: '#0f172a',
                        ink75: 'rgba(15,23,42,.75)',
                        muted: '#5a617c'
                    }
                }
            }
        };
    </script>
    <link rel="stylesheet" href="/assets/css/app.css">
</head>
<body class="min-h-screen bg-white">
<header class="sticky top-0 z-50 bg-white shadow-[0_0_1.875rem_rgba(0,0,0,.05)]">
    <div class="container max-w-screen-2xl mx-auto px-4">
        <div class="flex items-center gap-3 py-4">
            <a href="/" class="flex items-center gap-2">
                <img src="/home/product-icons1.svg" alt="Readlyst" class="w-10 h-10">
                <span class="text-xl font-bold tracking-tight">Readlyst</span>
            </a>
            <a href="/#catalog" class="ml-2 bg-ink text-white rounded-full h-11 px-4 font-bold text-sm flex items-center gap-2">
                <img src="/home/frame-2131330034-10.svg" class="w-4 h-4" alt="">
                Каталог
            </a>
            <div class="hidden md:flex flex-1 items-center bg-[#f2f3f2] rounded-full h-11 px-4 gap-2">
                <img src="/home/vector0.svg" class="w-4 h-4" alt="">
                <form action="/" method="get" class="w-full">
                    <input type="search" name="q" placeholder="Поиск в каталоге" class="bg-transparent w-full outline-none">
                </form>
            </div>
            <div class="ml-auto flex items-center gap-3">
                <div class="w-11 h-11 rounded-full bg-[#c5c5c5] grid place-items-center text-white font-medium">
                    <?= strtoupper(mb_substr($user['first_name'], 0, 1)) ?>
                </div>
                <div class="leading-tight">
                    <div class="font-bold text-sm text-ink"><?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name'], ENT_QUOTES) ?></div>
                    <div class="flex gap-2 text-sm">
                        <?php if (is_admin($pdo)): ?>
                            <a href="/admin/index.php" class="text-[#4e95ff]">Админ-панель</a>
                        <?php endif; ?>
                        <a href="/logout.php" class="text-[#ff6b86] font-semibold">Выйти</a>
                    </div>
                </div>
            </div>
        </div>
        <div class="md:hidden pb-3">
            <form action="/" method="get">
                <div class="flex items-center bg-[#f2f3f2] rounded-full h-11 px-4 gap-2">
                    <img src="/home/vector0.svg" class="w-4 h-4" alt="">
                    <input type="search" name="q" placeholder="Поиск…" class="bg-transparent flex-1 outline-none">
                </div>
            </form>
        </div>
    </div>
</header>

<main class="pb-16">
    <div class="container max-w-screen-2xl mx-auto px-4">
        <?php if (isset($message)): ?>
            <div class="mt-12 bg-red-50 border border-red-200 text-red-700 rounded-2xl p-6 text-lg font-semibold"><?= htmlspecialchars($message, ENT_QUOTES) ?></div>
        <?php else: ?>
            <section class="mt-10 grid lg:grid-cols-12 gap-8">
                <div class="lg:col-span-4">
                    <div class="rounded-2xl overflow-hidden shadow-soft bg-white">
                        <div class="[aspect-ratio:2/3] relative">
                            <img src="<?= htmlspecialchars(book_cover_url($book), ENT_QUOTES) ?>" alt="<?= htmlspecialchars($book['title'], ENT_QUOTES) ?>" class="absolute inset-0 w-full h-full object-cover">
                        </div>
                    </div>
                </div>
                <div class="lg:col-span-8 flex flex-col gap-6">
                    <div class="flex flex-col gap-4">
                        <div class="flex flex-wrap items-center gap-3 text-sm text-ink75">
                            <span class="font-medium text-ink">Жанр: <?= htmlspecialchars($book['genre_name'], ENT_QUOTES) ?></span>
                            <span class="font-medium text-ink">Год издания: <?= htmlspecialchars(format_year($book['release_year'] !== null ? (int)$book['release_year'] : null), ENT_QUOTES) ?></span>
                        </div>
                        <div>
                            <h1 class="text-fluid-hero font-bold tracking-tight"><?= htmlspecialchars($book['title'], ENT_QUOTES) ?></h1>
                            <div class="mt-2 flex flex-wrap items-center gap-2 text-base">
                                <span class="text-ink75">Автор</span>
                                <span class="text-[#4e95ff] font-semibold"><?= htmlspecialchars($book['author_name'], ENT_QUOTES) ?></span>
                            </div>
                        </div>
                    </div>
                    <div class="flex flex-col gap-3">
                        <div class="font-bold text-lg">О книге</div>
                        <p class="text-ink75 text-base leading-relaxed whitespace-pre-line"><?= nl2br(htmlspecialchars($book['description'], ENT_QUOTES)) ?></p>
                        <div class="flex gap-4">
                            <a href="/" class="inline-flex items-center gap-2 text-ink75">
                                <img src="/about_book/mask-group1.svg" class="w-4 h-4" alt="">
                                Вернуться в каталог
                            </a>
                            <?php if (is_admin($pdo)): ?>
                                <a href="/admin/index.php#books" class="inline-flex items-center gap-2 text-[#4e95ff]">Редактировать книгу</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </section>
        <?php endif; ?>
    </div>
</main>

<footer class="py-8 bg-[#f2f3f2] mt-auto">
    <div class="container max-w-screen-2xl mx-auto px-4 flex flex-col md:flex-row gap-4 justify-between text-sm text-ink75">
        <div>
            <div class="font-bold text-lg text-ink">Readlyst</div>
            <p class="mt-1">Цифровая библиотека для читателей и администраторов.</p>
        </div>
        <div class="flex gap-4">
            <a href="/register.php" class="hover:text-ink">Регистрация</a>
            <a href="/login.php" class="hover:text-ink">Авторизация</a>
        </div>
    </div>
</footer>
</body>
</html>
