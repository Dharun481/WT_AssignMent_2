<?php
declare(strict_types=1);
require __DIR__ . '/config.php';$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if ($id === null || $id === false || $id < 1) {
    http_response_code(400);
    exit('A valid student ID is required.');
}try {
    $pdo = db();
    $student = studentById($pdo, $id);
    if ($student === null) {
        http_response_code(404);
        exit('Student not found.');
    }
    $files = filesForStudent($pdo, $id);
} catch (PDOException $exception) {
    error_log($exception->getMessage());
    http_response_code(500);
    exit('Could not load student details. Check the database connection and try again.');
}pageStart('Student details', 'student-details-page');
?>
<div class="toolbar">
    <h1><?= e($student['name']) ?></h1>
    <div class="actions"><a class="button secondary" href="index.php">Back to students</a><a class="button" href="student_form.php?id=<?= (int) $id ?>">Edit student</a></div>
</div>
<div class="student-details-layout">
    <section class="card">
        <h2>Details</h2>
        <div class="grid student-detail-grid">
            <p><strong>Roll number</strong><br><?= e($student['roll_number']) ?></p>
            <p><strong>Course</strong><br><?= e($student['course']) ?></p>
            <p><strong>Email</strong><br><?= e($student['email']) ?: '—' ?></p>
            <p><strong>Phone</strong><br><?= e($student['phone']) ?: '—' ?></p>
            <p><strong>Date of birth</strong><br><?= e($student['date_of_birth']) ?: '—' ?></p>
            <p><strong>Address</strong><br><?= nl2br(e($student['address'])) ?: '—' ?></p>
        </div>
    </section>
    <section class="card">
        <h2>Profile images and documents</h2>
        <?php if ($files === []): ?>
            <p class="muted">No files have been uploaded for this student.</p>
        <?php else: ?>
            <ul class="file-list">
                <?php foreach ($files as $file): ?>
                    <li>
                        <?php if ($file['category'] === 'profile_image' && str_starts_with($file['file_type'], 'image/')): ?>
                            <img class="preview" src="<?= e($file['file_path']) ?>" alt="Profile image for <?= e($student['name']) ?>">
                        <?php endif; ?>
                        <span>
                            <a href="<?= e($file['file_path']) ?>" target="_blank" rel="noopener"><?= e($file['original_name']) ?></a><br>
                            <span class="muted file-meta"><?= e($file['category'] === 'profile_image' ? 'Profile image' : 'Document') ?> · <?= e($file['file_type']) ?> · <?= e($file['uploaded_at']) ?></span>
                        </span>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
        <a class="button" href="student_form.php?id=<?= (int) $id ?>">Edit details / upload files</a>
    </section>
</div>
<?php pageEnd(); ?>
