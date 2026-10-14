<?php
declare(strict_types=1);function db(): PDO
{
    static $pdo = null;    if ($pdo instanceof PDO) {
        return $pdo;
    }    $dsn = getenv('DB_DSN') ?: 'mysql:host=127.0.0.1;dbname=college_admin;charset=utf8mb4';
    $username = getenv('DB_USER') ?: 'root';
    $password = getenv('DB_PASSWORD') ?: '';    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);    return $pdo;
}const MAX_UPLOAD_BYTES = 5_242_880;
const UPLOAD_DIRECTORY = __DIR__ . DIRECTORY_SEPARATOR . 'uploads';function e(string|null $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}function csrfToken(): string
{
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }    return $_SESSION['csrf_token'];
}function verifyCsrfToken(): void
{
    $submitted = $_POST['csrf_token'] ?? '';
    if (!is_string($submitted) || !hash_equals(csrfToken(), $submitted)) {
        http_response_code(400);
        exit('Invalid form token. Return to the page and try again.');
    }
}function setFlash(string $message, string $type = 'success'): void
{
    $_SESSION['flash'] = ['message' => $message, 'type' => $type];
}function takeFlash(): ?array
{
    $flash = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);    return is_array($flash) ? $flash : null;
}function studentById(PDO $pdo, int $id): ?array
{
    $statement = $pdo->prepare('SELECT * FROM students WHERE id = ?');
    $statement->execute([$id]);
    $student = $statement->fetch();    return $student ?: null;
}function filesForStudent(PDO $pdo, int $studentId): array
{
    $statement = $pdo->prepare(
        'SELECT id, category, original_name, file_path, file_type, uploaded_at
         FROM student_files WHERE student_id = ? ORDER BY uploaded_at DESC, id DESC'
    );
    $statement->execute([$studentId]);    return $statement->fetchAll();
}function uploadFiles(array $fileInput, string $category): array
{
    if (!isset($fileInput['name']) || !is_array($fileInput['name'])) {
        return [];
    }    $allowedMimeTypes = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        'application/pdf' => 'pdf',
    ];
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $stored = [];    if (!is_dir(UPLOAD_DIRECTORY)
        && !mkdir(UPLOAD_DIRECTORY, 0750, true)
        && !is_dir(UPLOAD_DIRECTORY)) {
        throw new RuntimeException('The upload directory could not be created.');
    }    foreach ($fileInput['name'] as $index => $originalName) {
        $error = $fileInput['error'][$index] ?? UPLOAD_ERR_NO_FILE;
        if ($error === UPLOAD_ERR_NO_FILE) {
            continue;
        }
        if ($error !== UPLOAD_ERR_OK) {
            throw new RuntimeException('One of the selected files could not be uploaded (upload error ' . $error . ').');
        }        $temporaryPath = $fileInput['tmp_name'][$index] ?? '';
        if (!is_string($temporaryPath) || !is_uploaded_file($temporaryPath)) {
            throw new RuntimeException('An uploaded file failed server validation.');
        }
        $size = filesize($temporaryPath);
        if ($size === false) {
            throw new RuntimeException('The size of an uploaded file could not be verified.');
        }
        if ($size <= 0 || $size > MAX_UPLOAD_BYTES) {
            throw new RuntimeException('Each file must be larger than 0 bytes and no larger than 5 MB.');
        }        $mimeType = $finfo->file($temporaryPath);
        if (!is_string($mimeType) || !isset($allowedMimeTypes[$mimeType])) {
            throw new RuntimeException('Only JPEG, PNG, WebP, and PDF files are allowed.');
        }
        if ($category === 'profile_image' && !str_starts_with($mimeType, 'image/')) {
            throw new RuntimeException('The profile image must be a JPEG, PNG, or WebP image.');
        }        $source = fopen($temporaryPath, 'rb');
        if ($source === false) {
            throw new RuntimeException('An uploaded file could not be read.');
        }        $filename = '';
        $destination = '';
        $target = false;
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $filename = bin2hex(random_bytes(16)) . '.' . $allowedMimeTypes[$mimeType];
            $destination = UPLOAD_DIRECTORY . DIRECTORY_SEPARATOR . $filename;
            $target = @fopen($destination, 'xb');
            if ($target !== false) {
                break;
            }
            if (!file_exists($destination)) {
                break;
            }
        }        if ($target === false) {
            fclose($source);
            throw new RuntimeException('A unique file name could not be reserved. Check that the uploads directory is writable.');
        }        $bytesCopied = stream_copy_to_stream($source, $target);
        fclose($source);
        fclose($target);
        if ($bytesCopied !== $size) {
            unlink($destination);
            throw new RuntimeException('An uploaded file could not be saved completely.');
        }        $stored[] = [
            'category' => $category,
            'original_name' => basename(str_replace('\\', '/', (string) $originalName)),
            'file_path' => 'uploads/' . $filename,
            'file_type' => $mimeType,
        ];
    }    return $stored;
}function removeStoredFiles(array $files): array
{
    $failures = [];
    $uploadRoot = realpath(UPLOAD_DIRECTORY);    foreach ($files as $file) {
        $relativePath = $file['file_path'] ?? '';
        if (!is_string($relativePath) || !preg_match('/\Auploads\/[a-f0-9]{32}\.(?:jpg|png|webp|pdf)\z/', $relativePath)) {
            $failures[] = 'A stored file path was invalid and was not removed.';
            continue;
        }        $path = __DIR__ . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativePath);
        $realPath = realpath($path);
        if ($realPath === false) {
            continue;
        }
        if ($uploadRoot === false || !str_starts_with($realPath, $uploadRoot . DIRECTORY_SEPARATOR)) {
            $failures[] = 'A stored file was outside the upload directory and was not removed.';
            continue;
        }
        if (!unlink($realPath)) {
            $failures[] = 'Could not remove uploaded file: ' . basename($realPath);
        }
    }    return $failures;
}function pageStart(string $title, string $mainClass = ''): void
{
    $flash = takeFlash();
    ?>
    <!doctype html>
    <html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title><?= e($title) ?> | College Admin</title>
        <style>
            :root { color-scheme: light; font-family: system-ui, sans-serif; color: #172033; background: #f3f5f9; }
            body { margin: 0; }
            header { background: #172554; color: white; padding: 1rem max(1rem, calc((100% - 1000px) / 2)); }
            header a { color: white; text-decoration: none; font-weight: 700; }
            main { max-width: 1000px; margin: 2rem auto; padding: 0 1rem; }
            .card { background: white; padding: 1.25rem; border-radius: .65rem; box-shadow: 0 2px 12px #17203312; margin-bottom: 1rem; }
            .toolbar, .actions { display: flex; gap: .65rem; align-items: center; flex-wrap: wrap; }
            .toolbar { justify-content: space-between; margin-bottom: 1rem; }
            a.button, button { display: inline-block; border: 0; border-radius: .4rem; padding: .6rem .85rem; background: #1d4ed8; color: white; font: inherit; text-decoration: none; cursor: pointer; }
            a.secondary { background: #475569; } a.danger, button.danger { background: #b91c1c; }
            input, select, textarea { box-sizing: border-box; width: 100%; padding: .6rem; border: 1px solid #cbd5e1; border-radius: .35rem; font: inherit; }
            label { display: block; font-weight: 600; margin: .9rem 0 .3rem; }
            textarea { min-height: 6rem; } .grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 0 1rem; }
            .student-form { padding: 1rem; }
            .student-form .grid { grid-template-columns: repeat(3, minmax(0, 1fr)); gap: .15rem 1rem; }
            .student-form label { margin: .4rem 0 .2rem; }
            .student-form input { padding: .45rem; }
            .student-form textarea { min-height: 3.25rem; }
            .student-form .upload-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 0 1rem; }
            .student-form .muted { margin: .35rem 0 .5rem; font-size: .875rem; }
            .student-form h2 { margin: .75rem 0 .25rem; font-size: 1rem; }
            .student-form .existing-files { margin: 0; }
            .student-form .actions { margin: .7rem 0 0; }
            .student-details-page { max-width: 1200px; margin: 1rem auto; }
            .student-details-page .toolbar { margin-bottom: .75rem; }
            .student-details-page .toolbar h1 { margin: 0; }
            .student-details-layout { display: grid; grid-template-columns: 1fr; gap: 1rem; }
            .student-details-layout .card { min-width: 0; margin: 0; padding: 1rem; }
            .student-details-layout h2 { margin: 0 0 .65rem; font-size: 1.2rem; }
            .student-detail-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); gap: .25rem .75rem; }
            .student-detail-grid p { margin: .25rem 0; overflow-wrap: anywhere; }
            .student-details-layout .file-list { margin: 0 0 .75rem; }
            .student-details-layout .file-list li { gap: .5rem; margin: .5rem 0; overflow-wrap: anywhere; }
            .student-details-layout .preview { width: 40px; height: 40px; flex: 0 0 auto; }
            .student-details-layout .file-meta { font-size: .8rem; }
            .student-details-layout .button { padding: .5rem .7rem; }
            table { width: 100%; border-collapse: collapse; } th, td { text-align: left; padding: .7rem; border-bottom: 1px solid #e2e8f0; vertical-align: top; }
            .notice { padding: .8rem 1rem; border-radius: .4rem; margin-bottom: 1rem; background: #dcfce7; }
            .notice.error { background: #fee2e2; } .muted { color: #64748b; } .file-list { list-style: none; padding: 0; }
            .file-list li { display: flex; align-items: center; gap: .7rem; margin: .8rem 0; }
            .preview { width: 56px; height: 56px; object-fit: cover; border-radius: .35rem; }
            @media (max-width: 650px) {
                table, tbody, tr, td { display: block; } thead { display: none; } td { border-bottom: 0; padding: .35rem .7rem; } tr { border-bottom: 1px solid #e2e8f0; padding: .5rem 0; }
                .student-form .grid, .student-form .upload-grid { grid-template-columns: 1fr; }
                .student-details-layout, .student-detail-grid { grid-template-columns: 1fr; }
            }
        </style>
    </head>
    <body>
    <header><a href="index.php">College Admin · Students</a></header>
    <main class="<?= e($mainClass) ?>">
    <?php if ($flash !== null): ?>
        <div class="notice<?= ($flash['type'] ?? '') === 'error' ? ' error' : '' ?>"><?= e($flash['message'] ?? '') ?></div>
    <?php endif; ?>
    <?php
}function pageEnd(): void
{
    ?>
    </main>
    </body>
    </html>
    <?php
}session_start();
