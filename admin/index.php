<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';

require_admin($pdo);

$adminFlash = $_SESSION['admin_flash'] ?? null;
unset($_SESSION['admin_flash']);

function admin_redirect(string $anchor = ''): void
{
    redirect('/admin/index.php' . ($anchor !== '' ? '#' . $anchor : ''));
}

if (is_post()) {
    $token = $_POST['csrf_token'] ?? '';
    if (!validate_csrf($token)) {
        $_SESSION['admin_flash'] = 'Неверный токен безопасности, попробуйте снова.';
        admin_redirect();
    }

    $action = $_POST['action'] ?? '';

    try {
        switch ($action) {
            case 'create_genre':
                $name = trim($_POST['name'] ?? '');
                if ($name === '') {
                    throw new RuntimeException('Название жанра не может быть пустым.');
                }
                $stmt = $pdo->prepare('INSERT INTO genres (name) VALUES (:name)');
                $stmt->execute(['name' => $name]);
                $_SESSION['admin_flash'] = 'Жанр успешно создан.';
                admin_redirect('genres');
                break;
            case 'update_genre':
                $id = (int)($_POST['id'] ?? 0);
                $name = trim($_POST['name'] ?? '');
                if ($id <= 0 || $name === '') {
                    throw new RuntimeException('Некорректные данные жанра.');
                }
                $stmt = $pdo->prepare('UPDATE genres SET name = :name WHERE id = :id');
                $stmt->execute(['id' => $id, 'name' => $name]);
                $_SESSION['admin_flash'] = 'Жанр обновлён.';
                admin_redirect('genres');
                break;
            case 'delete_genre':
                $id = (int)($_POST['id'] ?? 0);
                if ($id <= 0) {
                    throw new RuntimeException('Некорректный идентификатор жанра.');
                }
                $stmt = $pdo->prepare('DELETE FROM genres WHERE id = :id');
                $stmt->execute(['id' => $id]);
                $_SESSION['admin_flash'] = 'Жанр удалён.';
                admin_redirect('genres');
                break;
            case 'create_author':
                $name = trim($_POST['name'] ?? '');
                $biography = trim($_POST['biography'] ?? '');
                if ($name === '') {
                    throw new RuntimeException('Имя автора не может быть пустым.');
                }
                $stmt = $pdo->prepare('INSERT INTO authors (name, biography) VALUES (:name, :biography)');
                $stmt->execute(['name' => $name, 'biography' => $biography !== '' ? $biography : null]);
                $_SESSION['admin_flash'] = 'Автор добавлен.';
                admin_redirect('authors');
                break;
            case 'update_author':
                $id = (int)($_POST['id'] ?? 0);
                $name = trim($_POST['name'] ?? '');
                $biography = trim($_POST['biography'] ?? '');
                if ($id <= 0 || $name === '') {
                    throw new RuntimeException('Некорректные данные автора.');
                }
                $stmt = $pdo->prepare('UPDATE authors SET name = :name, biography = :biography WHERE id = :id');
                $stmt->execute([
                    'id' => $id,
                    'name' => $name,
                    'biography' => $biography !== '' ? $biography : null,
                ]);
                $_SESSION['admin_flash'] = 'Автор обновлён.';
                admin_redirect('authors');
                break;
            case 'delete_author':
                $id = (int)($_POST['id'] ?? 0);
                if ($id <= 0) {
                    throw new RuntimeException('Некорректный идентификатор автора.');
                }
                $stmt = $pdo->prepare('DELETE FROM authors WHERE id = :id');
                $stmt->execute(['id' => $id]);
                $_SESSION['admin_flash'] = 'Автор удалён.';
                admin_redirect('authors');
                break;
            case 'create_book':
                $title = trim($_POST['title'] ?? '');
                $description = trim($_POST['description'] ?? '');
                $releaseYear = trim($_POST['release_year'] ?? '');
                $authorId = (int)($_POST['author_id'] ?? 0);
                $genreId = (int)($_POST['genre_id'] ?? 0);

                if ($title === '' || $description === '' || $authorId <= 0 || $genreId <= 0) {
                    throw new RuntimeException('Заполните все обязательные поля книги.');
                }

                $cover = null;
                if (!empty($_FILES['cover_image']['tmp_name'])) {
                    $cover = handle_file_upload($_FILES['cover_image']);
                    if ($cover === null) {
                        throw new RuntimeException('Не удалось загрузить обложку. Используйте JPG, PNG или WEBP.');
                    }
                }

                $stmt = $pdo->prepare('INSERT INTO books (title, description, release_year, author_id, genre_id, cover_image) VALUES (:title, :description, :release_year, :author_id, :genre_id, :cover_image)');
                $stmt->execute([
                    'title' => $title,
                    'description' => $description,
                    'release_year' => $releaseYear !== '' ? (int)$releaseYear : null,
                    'author_id' => $authorId,
                    'genre_id' => $genreId,
                    'cover_image' => $cover,
                ]);
                $_SESSION['admin_flash'] = 'Книга добавлена.';
                admin_redirect('books');
                break;
            case 'update_book':
                $id = (int)($_POST['id'] ?? 0);
                $title = trim($_POST['title'] ?? '');
                $description = trim($_POST['description'] ?? '');
                $releaseYear = trim($_POST['release_year'] ?? '');
                $authorId = (int)($_POST['author_id'] ?? 0);
                $genreId = (int)($_POST['genre_id'] ?? 0);

                if ($id <= 0 || $title === '' || $description === '' || $authorId <= 0 || $genreId <= 0) {
                    throw new RuntimeException('Заполните все обязательные поля при редактировании книги.');
                }

                $stmt = $pdo->prepare('SELECT cover_image FROM books WHERE id = :id');
                $stmt->execute(['id' => $id]);
                $book = $stmt->fetch();
                if (!$book) {
                    throw new RuntimeException('Книга не найдена.');
                }

                $cover = $book['cover_image'];
                if (!empty($_FILES['cover_image']['tmp_name'])) {
                    $newCover = handle_file_upload($_FILES['cover_image']);
                    if ($newCover === null) {
                        throw new RuntimeException('Не удалось загрузить обложку.');
                    }
                    delete_file_if_exists($cover);
                    $cover = $newCover;
                }

                $stmt = $pdo->prepare('UPDATE books SET title = :title, description = :description, release_year = :release_year, author_id = :author_id, genre_id = :genre_id, cover_image = :cover_image WHERE id = :id');
                $stmt->execute([
                    'id' => $id,
                    'title' => $title,
                    'description' => $description,
                    'release_year' => $releaseYear !== '' ? (int)$releaseYear : null,
                    'author_id' => $authorId,
                    'genre_id' => $genreId,
                    'cover_image' => $cover,
                ]);
                $_SESSION['admin_flash'] = 'Книга обновлена.';
                admin_redirect('books');
                break;
            case 'delete_book':
                $id = (int)($_POST['id'] ?? 0);
                if ($id <= 0) {
                    throw new RuntimeException('Некорректный идентификатор книги.');
                }
                $stmt = $pdo->prepare('SELECT cover_image FROM books WHERE id = :id');
                $stmt->execute(['id' => $id]);
                $book = $stmt->fetch();
                if ($book) {
                    delete_file_if_exists($book['cover_image']);
                }
                $stmt = $pdo->prepare('DELETE FROM books WHERE id = :id');
                $stmt->execute(['id' => $id]);
                $_SESSION['admin_flash'] = 'Книга удалена.';
                admin_redirect('books');
                break;
            case 'toggle_ban':
                $id = (int)($_POST['id'] ?? 0);
                $value = (int)($_POST['value'] ?? 0) === 1 ? 1 : 0;
                if ($id <= 0) {
                    throw new RuntimeException('Некорректный пользователь.');
                }
                $stmt = $pdo->prepare('SELECT role FROM users WHERE id = :id');
                $stmt->execute(['id' => $id]);
                $user = $stmt->fetch();
                if (!$user || $user['role'] === 'admin') {
                    throw new RuntimeException('Нельзя изменить блокировку этого пользователя.');
                }
                $stmt = $pdo->prepare('UPDATE users SET is_banned = :ban WHERE id = :id');
                $stmt->execute(['ban' => $value, 'id' => $id]);
                $_SESSION['admin_flash'] = $value ? 'Пользователь заблокирован.' : 'Пользователь разблокирован.';
                admin_redirect('users');
                break;
            default:
                $_SESSION['admin_flash'] = 'Неизвестное действие.';
                admin_redirect();
        }
    } catch (Throwable $e) {
        $_SESSION['admin_flash'] = $e->getMessage();
        admin_redirect($action === 'create_book' || $action === 'update_book' || $action === 'delete_book' ? 'books' : ($action === 'create_author' || $action === 'update_author' || $action === 'delete_author' ? 'authors' : ($action === 'create_genre' || $action === 'update_genre' || $action === 'delete_genre' ? 'genres' : 'users')));
    }
}

$genres = fetch_genres($pdo);
$authors = fetch_authors($pdo);
$books = fetch_books($pdo);
$users = fetch_users($pdo);
$currentUser = current_user($pdo);
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Административная панель — Readlyst</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/app.css">
</head>
<body>
<header class="nav-wrap">
    <div class="container py-3">
        <div class="d-flex align-items-center gap-3 flex-wrap">
            <a href="/" class="d-flex align-items-center gap-2 text-decoration-none">
                <img src="/home/product-icons1.svg" alt="Readlyst" width="36" height="36">
                <span class="brand-title">Readlyst</span>
            </a>
            <span class="catalog-btn">Админ-панель</span>
            <div class="ms-auto d-flex align-items-center gap-3">
                <span><?= htmlspecialchars($currentUser['first_name'] . ' ' . $currentUser['last_name'], ENT_QUOTES) ?></span>
                <a href="/logout.php" class="login-btn">Выйти</a>
            </div>
        </div>
    </div>
</header>

<main class="py-5">
    <div class="container">
        <?php if ($adminFlash): ?>
            <div class="flash-message mb-4"><?= htmlspecialchars($adminFlash, ENT_QUOTES) ?></div>
        <?php endif; ?>

        <div class="row g-4">
            <div class="col-12">
                <div class="card shadow-sm" id="books">
                    <div class="card-body">
                        <h2 class="card-title h4 mb-3">Книги</h2>
                        <form method="post" enctype="multipart/form-data" class="row g-3 mb-4">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES) ?>">
                            <input type="hidden" name="action" value="create_book">
                            <div class="col-md-4">
                                <label class="form-label">Название *</label>
                                <input name="title" class="form-control" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Автор *</label>
                                <select name="author_id" class="form-select" required>
                                    <option value="">Выберите автора</option>
                                    <?php foreach ($authors as $author): ?>
                                        <option value="<?= (int)$author['id'] ?>"><?= htmlspecialchars($author['name'], ENT_QUOTES) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Жанр *</label>
                                <select name="genre_id" class="form-select" required>
                                    <option value="">Выберите жанр</option>
                                    <?php foreach ($genres as $genre): ?>
                                        <option value="<?= (int)$genre['id'] ?>"><?= htmlspecialchars($genre['name'], ENT_QUOTES) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Год выпуска</label>
                                <input name="release_year" class="form-control" type="number" min="0" max="3000">
                            </div>
                            <div class="col-md-9">
                                <label class="form-label">Описание *</label>
                                <textarea name="description" class="form-control" rows="3" required></textarea>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Обложка</label>
                                <input name="cover_image" type="file" class="form-control" accept="image/jpeg,image/png,image/webp">
                            </div>
                            <div class="col-12">
                                <button class="btn btn-primary" type="submit">Добавить книгу</button>
                            </div>
                        </form>
                        <div class="accordion" id="booksAccordion">
                            <?php foreach ($books as $book): ?>
                                <div class="accordion-item mb-2">
                                    <h2 class="accordion-header" id="heading-book-<?= (int)$book['id'] ?>">
                                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#book-<?= (int)$book['id'] ?>" aria-expanded="false">
                                            <?= htmlspecialchars($book['title'], ENT_QUOTES) ?> — <?= htmlspecialchars($book['author_name'], ENT_QUOTES) ?>
                                        </button>
                                    </h2>
                                    <div id="book-<?= (int)$book['id'] ?>" class="accordion-collapse collapse" data-bs-parent="#booksAccordion">
                                        <div class="accordion-body">
                                            <form method="post" enctype="multipart/form-data" class="row g-3">
                                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES) ?>">
                                                <input type="hidden" name="action" value="update_book">
                                                <input type="hidden" name="id" value="<?= (int)$book['id'] ?>">
                                                <div class="col-md-6">
                                                    <label class="form-label">Название *</label>
                                                    <input name="title" class="form-control" value="<?= htmlspecialchars($book['title'], ENT_QUOTES) ?>" required>
                                                </div>
                                                <div class="col-md-3">
                                                    <label class="form-label">Автор *</label>
                                                    <select name="author_id" class="form-select" required>
                                                        <?php foreach ($authors as $author): ?>
                                                            <option value="<?= (int)$author['id'] ?>" <?= (int)$author['id'] === (int)$book['author_id'] ? 'selected' : '' ?>><?= htmlspecialchars($author['name'], ENT_QUOTES) ?></option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                                <div class="col-md-3">
                                                    <label class="form-label">Жанр *</label>
                                                    <select name="genre_id" class="form-select" required>
                                                        <?php foreach ($genres as $genre): ?>
                                                            <option value="<?= (int)$genre['id'] ?>" <?= (int)$genre['id'] === (int)$book['genre_id'] ? 'selected' : '' ?>><?= htmlspecialchars($genre['name'], ENT_QUOTES) ?></option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                                <div class="col-md-3">
                                                    <label class="form-label">Год выпуска</label>
                                                    <input name="release_year" class="form-control" type="number" min="0" max="3000" value="<?= htmlspecialchars((string)$book['release_year'], ENT_QUOTES) ?>">
                                                </div>
                                                <div class="col-md-9">
                                                    <label class="form-label">Описание *</label>
                                                    <textarea name="description" class="form-control" rows="3" required><?= htmlspecialchars($book['description'], ENT_QUOTES) ?></textarea>
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label">Новая обложка</label>
                                                    <input name="cover_image" type="file" class="form-control" accept="image/jpeg,image/png,image/webp">
                                                    <?php if (!empty($book['cover_image'])): ?>
                                                        <div class="small text-muted mt-1">Текущий файл: <?= htmlspecialchars($book['cover_image'], ENT_QUOTES) ?></div>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="col-12 d-flex gap-3">
                                                    <button class="btn btn-primary" type="submit">Сохранить изменения</button>
                                                </div>
                                            </form>
                                            <form method="post" class="mt-3">
                                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES) ?>">
                                                <input type="hidden" name="action" value="delete_book">
                                                <input type="hidden" name="id" value="<?= (int)$book['id'] ?>">
                                                <button class="btn btn-outline-danger" type="submit" onclick="return confirm('Удалить книгу \"<?= htmlspecialchars($book['title'], ENT_QUOTES) ?>\"?')">Удалить книгу</button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-lg-6">
                <div class="card shadow-sm" id="authors">
                    <div class="card-body">
                        <h2 class="card-title h4 mb-3">Авторы</h2>
                        <form method="post" class="row g-3 mb-4">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES) ?>">
                            <input type="hidden" name="action" value="create_author">
                            <div class="col-12">
                                <label class="form-label">Имя автора *</label>
                                <input name="name" class="form-control" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Биография</label>
                                <textarea name="biography" class="form-control" rows="2"></textarea>
                            </div>
                            <div class="col-12">
                                <button class="btn btn-primary" type="submit">Добавить автора</button>
                            </div>
                        </form>
                        <div class="list-group">
                            <?php foreach ($authors as $author): ?>
                                <div class="list-group-item">
                                    <form method="post" class="row g-2 align-items-center mb-2">
                                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES) ?>">
                                        <input type="hidden" name="action" value="update_author">
                                        <input type="hidden" name="id" value="<?= (int)$author['id'] ?>">
                                        <div class="col-12">
                                            <input name="name" class="form-control" value="<?= htmlspecialchars($author['name'], ENT_QUOTES) ?>" required>
                                        </div>
                                        <div class="col-12">
                                            <textarea name="biography" class="form-control" rows="2"><?= htmlspecialchars($author['biography'] ?? '', ENT_QUOTES) ?></textarea>
                                        </div>
                                        <div class="col d-flex gap-2">
                                            <button class="btn btn-primary" type="submit">Сохранить</button>
                                        </div>
                                    </form>
                                    <form method="post" class="d-inline-block mt-2">
                                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES) ?>">
                                        <input type="hidden" name="action" value="delete_author">
                                        <input type="hidden" name="id" value="<?= (int)$author['id'] ?>">
                                        <button class="btn btn-outline-danger btn-sm" type="submit" onclick="return confirm('Удалить автора?')">Удалить автора</button>
                                    </form>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-lg-6">
                <div class="card shadow-sm" id="genres">
                    <div class="card-body">
                        <h2 class="card-title h4 mb-3">Жанры</h2>
                        <form method="post" class="row g-3 mb-4">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES) ?>">
                            <input type="hidden" name="action" value="create_genre">
                            <div class="col-12">
                                <label class="form-label">Название жанра *</label>
                                <input name="name" class="form-control" required>
                            </div>
                            <div class="col-12">
                                <button class="btn btn-primary" type="submit">Добавить жанр</button>
                            </div>
                        </form>
                        <div class="list-group">
                            <?php foreach ($genres as $genre): ?>
                                <div class="list-group-item">
                                    <form method="post" class="row g-2 align-items-center mb-2">
                                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES) ?>">
                                        <input type="hidden" name="action" value="update_genre">
                                        <input type="hidden" name="id" value="<?= (int)$genre['id'] ?>">
                                        <div class="col">
                                            <input name="name" class="form-control" value="<?= htmlspecialchars($genre['name'], ENT_QUOTES) ?>" required>
                                        </div>
                                        <div class="col-auto d-flex gap-2">
                                            <button class="btn btn-primary" type="submit">Сохранить</button>
                                        </div>
                                    </form>
                                    <form method="post" class="d-inline-block mt-2">
                                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES) ?>">
                                        <input type="hidden" name="action" value="delete_genre">
                                        <input type="hidden" name="id" value="<?= (int)$genre['id'] ?>">
                                        <button class="btn btn-outline-danger btn-sm" type="submit" onclick="return confirm('Удалить жанр?')">Удалить жанр</button>
                                    </form>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12">
                <div class="card shadow-sm" id="users">
                    <div class="card-body">
                        <h2 class="card-title h4 mb-3">Пользователи</h2>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Имя</th>
                                        <th>Логин</th>
                                        <th>Email</th>
                                        <th>Роль</th>
                                        <th>Статус</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($users as $user): ?>
                                        <tr>
                                            <td><?= (int)$user['id'] ?></td>
                                            <td><?= htmlspecialchars($user['last_name'] . ' ' . $user['first_name'], ENT_QUOTES) ?></td>
                                            <td><?= htmlspecialchars($user['username'], ENT_QUOTES) ?></td>
                                            <td><?= htmlspecialchars($user['email'], ENT_QUOTES) ?></td>
                                            <td><?= htmlspecialchars($user['role'], ENT_QUOTES) ?></td>
                                            <td>
                                                <?php if ($user['role'] === 'admin'): ?>
                                                    <span class="badge bg-success">Активен</span>
                                                <?php else: ?>
                                                    <span class="badge <?= $user['is_banned'] ? 'bg-danger' : 'bg-success' ?>">
                                                        <?= $user['is_banned'] ? 'Заблокирован' : 'Активен' ?>
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($user['role'] !== 'admin'): ?>
                                                    <form method="post" class="d-inline">
                                                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES) ?>">
                                                        <input type="hidden" name="action" value="toggle_ban">
                                                        <input type="hidden" name="id" value="<?= (int)$user['id'] ?>">
                                                        <input type="hidden" name="value" value="<?= $user['is_banned'] ? 0 : 1 ?>">
                                                        <button class="btn btn-sm <?= $user['is_banned'] ? 'btn-success' : 'btn-outline-danger' ?>" type="submit">
                                                            <?= $user['is_banned'] ? 'Разблокировать' : 'Заблокировать' ?>
                                                        </button>
                                                    </form>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
