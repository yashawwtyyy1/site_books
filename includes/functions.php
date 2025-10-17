<?php

declare(strict_types=1);

/** @return array<int, array<string, mixed>> */
function fetch_genres(PDO $pdo): array
{
    $stmt = $pdo->query('SELECT id, name FROM genres ORDER BY name');

    return $stmt->fetchAll();
}

/**
 * @param array{genre_id?: int|null, query?: string|null} $filters
 * @return array<int, array<string, mixed>>
 */
function fetch_books(PDO $pdo, array $filters = []): array
{
    $where = [];
    $params = [];

    if (!empty($filters['genre_id'])) {
        $where[] = 'b.genre_id = :genre_id';
        $params['genre_id'] = $filters['genre_id'];
    }

    if (!empty($filters['query'])) {
        $where[] = '(
            b.title LIKE :query OR
            a.name LIKE :query OR
            g.name LIKE :query
        )';
        $params['query'] = '%' . $filters['query'] . '%';
    }

    $sql = 'SELECT b.*, a.name AS author_name, g.name AS genre_name
            FROM books b
            JOIN authors a ON a.id = b.author_id
            JOIN genres g ON g.id = b.genre_id';

    if ($where) {
        $sql .= ' WHERE ' . implode(' AND ', $where);
    }

    $sql .= ' ORDER BY b.created_at DESC';

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    return $stmt->fetchAll();
}

function fetch_book(PDO $pdo, int $bookId): ?array
{
    $stmt = $pdo->prepare('SELECT b.*, a.name AS author_name, g.name AS genre_name
        FROM books b
        JOIN authors a ON a.id = b.author_id
        JOIN genres g ON g.id = b.genre_id
        WHERE b.id = :id');
    $stmt->execute(['id' => $bookId]);

    $book = $stmt->fetch();

    return $book ?: null;
}

/** @return array<int, array<string, mixed>> */
function fetch_authors(PDO $pdo): array
{
    $stmt = $pdo->query('SELECT id, name, biography FROM authors ORDER BY name');

    return $stmt->fetchAll();
}

function fetch_users(PDO $pdo): array
{
    $stmt = $pdo->query('SELECT id, first_name, last_name, username, email, role, is_banned, created_at FROM users ORDER BY created_at DESC');

    return $stmt->fetchAll();
}

function fetch_user(PDO $pdo, int $userId): ?array
{
    $stmt = $pdo->prepare('SELECT * FROM users WHERE id = :id');
    $stmt->execute(['id' => $userId]);
    $user = $stmt->fetch();

    return $user ?: null;
}

function fetch_user_by_username(PDO $pdo, string $username): ?array
{
    $stmt = $pdo->prepare('SELECT * FROM users WHERE username = :username');
    $stmt->execute(['username' => $username]);
    $user = $stmt->fetch();

    return $user ?: null;
}

function fetch_user_by_email(PDO $pdo, string $email): ?array
{
    $stmt = $pdo->prepare('SELECT * FROM users WHERE email = :email');
    $stmt->execute(['email' => $email]);
    $user = $stmt->fetch();

    return $user ?: null;
}

function is_post(): bool
{
    return $_SERVER['REQUEST_METHOD'] === 'POST';
}

function redirect(string $location): void
{
    header('Location: ' . $location);
    exit;
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function validate_csrf(string $token): bool
{
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

function format_year(?int $year): string
{
    return $year ? (string)$year : '—';
}

function book_cover_url(array $book): string
{
    if (!empty($book['cover_image']) && file_exists(__DIR__ . '/../uploads/' . $book['cover_image'])) {
        return '/uploads/' . rawurlencode($book['cover_image']);
    }

    return '/assets/img/placeholder-book.svg';
}

function ensure_database_migrated(PDO $pdo): void
{
    $schemaFile = __DIR__ . '/../database/schema.sql';
    if (!file_exists($schemaFile)) {
        return;
    }

    $sql = file_get_contents($schemaFile);
    if ($sql === false) {
        return;
    }

    foreach (array_filter(array_map('trim', explode(';', $sql))) as $statement) {
        if ($statement === '') {
            continue;
        }
        $pdo->exec($statement);
    }

    seed_database($pdo);
}

function seed_database(PDO $pdo): void
{
    $count = (int)$pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
    if ($count === 0) {
        $stmt = $pdo->prepare('INSERT INTO users (first_name, last_name, username, email, password_hash, role) VALUES (:first_name, :last_name, :username, :email, :password_hash, :role)');
        $stmt->execute([
            'first_name' => 'Администратор',
            'last_name' => 'Readlyst',
            'username' => 'admin',
            'email' => 'admin@example.com',
            'password_hash' => password_hash('admin123', PASSWORD_DEFAULT),
            'role' => 'admin',
        ]);
    }

    $genreCount = (int)$pdo->query('SELECT COUNT(*) FROM genres')->fetchColumn();
    if ($genreCount === 0) {
        $genres = ['Детектив', 'Фэнтези', 'Научная литература', 'Классика'];
        $stmt = $pdo->prepare('INSERT INTO genres (name) VALUES (:name)');
        foreach ($genres as $genre) {
            $stmt->execute(['name' => $genre]);
        }
    }

    $authorCount = (int)$pdo->query('SELECT COUNT(*) FROM authors')->fetchColumn();
    if ($authorCount === 0) {
        $authors = ['Агата Кристи', 'Дж. Р. Р. Толкин', 'Айзек Азимов', 'Фёдор Достоевский'];
        $stmt = $pdo->prepare('INSERT INTO authors (name) VALUES (:name)');
        foreach ($authors as $author) {
            $stmt->execute(['name' => $author]);
        }
    }

    $bookCount = (int)$pdo->query('SELECT COUNT(*) FROM books')->fetchColumn();
    if ($bookCount === 0) {
        $stmt = $pdo->prepare('INSERT INTO books (title, description, release_year, author_id, genre_id) VALUES (:title, :description, :release_year, :author_id, :genre_id)');
        $stmt->execute([
            'title' => 'Убийство в Восточном экспрессе',
            'description' => 'Захватывающий детектив Агаты Кристи с Эркюлем Пуаро в главной роли.',
            'release_year' => 1934,
            'author_id' => 1,
            'genre_id' => 1,
        ]);
        $stmt->execute([
            'title' => 'Властелин колец',
            'description' => 'Эпическая история о борьбе добра и зла в мире Средиземья.',
            'release_year' => 1954,
            'author_id' => 2,
            'genre_id' => 2,
        ]);
        $stmt->execute([
            'title' => 'Я, робот',
            'description' => 'Сборник научно-фантастических рассказов об истории развития робототехники.',
            'release_year' => 1950,
            'author_id' => 3,
            'genre_id' => 3,
        ]);
        $stmt->execute([
            'title' => 'Преступление и наказание',
            'description' => 'Классический роман о нравственных испытаниях и поиске истины.',
            'release_year' => 1866,
            'author_id' => 4,
            'genre_id' => 4,
        ]);
    }
}

function handle_file_upload(array $file): ?string
{
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return null;
    }

    $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    $mime = mime_content_type($file['tmp_name']);

    if (!isset($allowed[$mime])) {
        return null;
    }

    $extension = $allowed[$mime];
    $filename = bin2hex(random_bytes(16)) . '.' . $extension;
    $destination = __DIR__ . '/../uploads/' . $filename;

    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        return null;
    }

    return $filename;
}

function delete_file_if_exists(?string $filename): void
{
    if (!$filename) {
        return;
    }

    $path = __DIR__ . '/../uploads/' . $filename;
    if (is_file($path)) {
        @unlink($path);
    }
}
