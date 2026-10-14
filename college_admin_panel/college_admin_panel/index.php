<?php
declare(strict_types=1);
require __DIR__ . '/config.php';

try {
    $students = db()->query(
        "SELECT s.id, s.name, s.roll_number, s.course, s.email, s.phone,
                (SELECT sf.file_path FROM student_files sf
                 WHERE sf.student_id = s.id AND sf.category = 'profile_image'
                 ORDER BY sf.uploaded_at DESC, sf.id DESC LIMIT 1) AS profile_image
         FROM students s ORDER BY s.name, s.roll_number"
    )->fetchAll();
} catch (PDOException $exception) {
    error_log($exception->getMessage());
    http_response_code(500);
    pageStart('Students');
    echo '<div class="notice error">Could not load student records. Check the database connection and schema, then try again.</div>';
    pageEnd();
    exit;
}

pageStart('Students');
?>
<div class="toolbar">
    <div><h1>Student records</h1><p class="muted">Manage student details and their profile documents.</p></div>
    <a class="button" href="student_form.php">Add student</a>
</div>
<section class="card">
    <?php if ($students === []): ?>
        <p>No student records yet.</p>
    <?php else: ?>
        <table>
            <thead><tr><th>Student</th><th>Roll number</th><th>Course</th><th>Contact</th><th>Actions</th></tr></thead>
            <tbody>
            <?php foreach ($students as $student): ?>
                <tr>
                    <td>
                        <?php if ($student['profile_image'] !== null): ?>
                            <img class="preview" src="<?= e($student['profile_image']) ?>" alt="">
                        <?php endif; ?>
                        <a href="student_view.php?id=<?= (int) $student['id'] ?>"><?= e($student['name']) ?></a>
                    </td>
                    <td><?= e($student['roll_number']) ?></td>
                    <td><?= e($student['course']) ?></td>
                    <td><?= e($student['email']) ?><br><?= e($student['phone']) ?></td>
                    <td class="actions">
                        <a class="button secondary" href="student_view.php?id=<?= (int) $student['id'] ?>">View</a>
                        <a class="button" href="student_form.php?id=<?= (int) $student['id'] ?>">Edit</a>
                        <form method="post" action="student_delete.php" onsubmit="return confirm('Delete this student and all associated files?');">
                            <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                            <input type="hidden" name="id" value="<?= (int) $student['id'] ?>">
                            <button class="danger" type="submit">Delete</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</section>
<?php pageEnd(); ?>
