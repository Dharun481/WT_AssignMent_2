<?php
declare(strict_types=1);
require __DIR__ . '/config.php';try {
    $pdo = db();
} catch (PDOException $exception) {
    error_log($exception->getMessage());
    http_response_code(500);
    exit('Could not connect to the database. Check the connection settings and try again.');
}$id = isset($_GET['id']) ? filter_var($_GET['id'], FILTER_VALIDATE_INT) : null;
if ($id === false || ($id !== null && $id < 1)) {
    http_response_code(400);
    exit('A valid student ID is required.');
}
$student = [
    'name' => '', 'roll_number' => '', 'course' => '', 'email' => '',
    'phone' => '', 'date_of_birth' => '', 'address' => '',
];
$files = [];if ($id !== null && $id !== false) {
    try {
        $student = studentById($pdo, $id);
        if ($student !== null) {
            $files = filesForStudent($pdo, $id);
        }
    } catch (PDOException $exception) {
        error_log($exception->getMessage());
        http_response_code(500);
        exit('Could not load the student record. Check the database schema and try again.');
    }
    if ($student === null) {
        http_response_code(404);
        exit('Student not found.');
    }
}$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrfToken();
    $postedId = isset($_POST['id']) && $_POST['id'] !== ''
        ? filter_var($_POST['id'], FILTER_VALIDATE_INT)
        : null;
    if ($postedId === false || ($postedId !== null && $postedId < 1)) {
        http_response_code(400);
        exit('A valid student ID is required.');
    }
    $id = $postedId;
    if ($id !== null) {
        try {
            $student = studentById($pdo, $id);
        } catch (PDOException $exception) {
            error_log($exception->getMessage());
            http_response_code(500);
            exit('Could not load the student record. Check the database schema and try again.');
        }
        if ($student === null) {
            http_response_code(404);
            exit('Student not found.');
        }
    }    $student = [
        'name' => trim((string) ($_POST['name'] ?? '')),
        'roll_number' => trim((string) ($_POST['roll_number'] ?? '')),
        'course' => trim((string) ($_POST['course'] ?? '')),
        'email' => trim((string) ($_POST['email'] ?? '')),
        'phone' => trim((string) ($_POST['phone'] ?? '')),
        'date_of_birth' => trim((string) ($_POST['date_of_birth'] ?? '')),
        'address' => trim((string) ($_POST['address'] ?? '')),
    ];    if ($student['name'] === '' || mb_strlen($student['name']) > 150) {
        $errors[] = 'Name is required and must be 150 characters or fewer.';
    }
    if ($student['roll_number'] === '' || mb_strlen($student['roll_number']) > 50) {
        $errors[] = 'Roll number is required and must be 50 characters or fewer.';
    }
    if ($student['course'] === '' || mb_strlen($student['course']) > 120) {
        $errors[] = 'Course is required and must be 120 characters or fewer.';
    }
    if ($student['email'] !== '' && (!filter_var($student['email'], FILTER_VALIDATE_EMAIL) || mb_strlen($student['email']) > 190)) {
        $errors[] = 'Enter a valid email address of 190 characters or fewer.';
    }
    if (mb_strlen($student['phone']) > 30 || mb_strlen($student['address']) > 2000) {
        $errors[] = 'Phone must be 30 characters or fewer and address 2,000 characters or fewer.';
    }
    if ($student['date_of_birth'] !== '') {
        $date = DateTime::createFromFormat('Y-m-d', $student['date_of_birth']);
        if ($date === false || $date->format('Y-m-d') !== $student['date_of_birth']) {
            $errors[] = 'Enter a valid date of birth.';
        }
    }    if ($errors === []) {
        $storedFiles = [];
        try {
            $storedFiles = uploadFiles($_FILES['profile_image'] ?? [], 'profile_image');
            $storedFiles = array_merge(
                $storedFiles,
                uploadFiles($_FILES['documents'] ?? [], 'document')
            );            $pdo->beginTransaction();
            if ($id === null) {
                $statement = $pdo->prepare(
                    'INSERT INTO students (name, roll_number, course, email, phone, date_of_birth, address)
                     VALUES (:name, :roll_number, :course, :email, :phone, :date_of_birth, :address)'
                );
            } else {
                $statement = $pdo->prepare(
                    'UPDATE students SET name = :name, roll_number = :roll_number, course = :course,
                     email = :email, phone = :phone, date_of_birth = :date_of_birth, address = :address
                     WHERE id = :id'
                );
                $statement->bindValue(':id', $id, PDO::PARAM_INT);
            }            foreach (['name', 'roll_number', 'course', 'email', 'phone', 'date_of_birth', 'address'] as $field) {
                $value = $student[$field] === '' ? null : $student[$field];
                $statement->bindValue(
                    ':' . $field,
                    $value,
                    $value === null ? PDO::PARAM_NULL : PDO::PARAM_STR
                );
            }
            $statement->execute();
            $studentId = $id ?? (int) $pdo->lastInsertId();            if ($storedFiles !== []) {
                $fileStatement = $pdo->prepare(
                    'INSERT INTO student_files (student_id, category, original_name, file_path, file_type)
                     VALUES (:student_id, :category, :original_name, :file_path, :file_type)'
                );
                foreach ($storedFiles as $file) {
                    $fileStatement->execute([
                        'student_id' => $studentId,
                        'category' => $file['category'],
                        'original_name' => $file['original_name'],
                        'file_path' => $file['file_path'],
                        'file_type' => $file['file_type'],
                    ]);
                }
            }            $pdo->commit();
            setFlash($id === null ? 'Student added successfully.' : 'Student details updated successfully.');
            header('Location: student_view.php?id=' . $studentId);
            exit;
        } catch (RuntimeException $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            removeStoredFiles($storedFiles);
            $errors[] = $exception->getMessage();
        } catch (PDOException $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            removeStoredFiles($storedFiles);
            error_log($exception->getMessage());
            $errors[] = $exception->getCode() === '23000'
                ? 'That roll number is already in use. Choose a unique roll number.'
                : 'Could not save the student record. Check the database connection and try again.';
        }
    }
}if ($id !== null) {
    try {
        $files = filesForStudent($pdo, $id);
    } catch (PDOException $exception) {
        error_log($exception->getMessage());
        http_response_code(500);
        exit('Could not load uploaded files. Check the database schema and try again.');
    }
}
pageStart($id === null ? 'Add student' : 'Edit student');
?>
<div class="toolbar"><h1><?= $id === null ? 'Add student' : 'Edit student' ?></h1><a class="button secondary" href="index.php">Back to students</a></div>
<?php if ($errors !== []): ?>
    <div class="notice error"><strong>Please correct the following:</strong><ul><?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul></div>
<?php endif; ?>
<form class="card student-form" method="post" enctype="multipart/form-data">
    <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
    <?php if ($id !== null): ?><input type="hidden" name="id" value="<?= (int) $id ?>"><?php endif; ?>
    <div class="grid">
        <div><label for="name">Full name *</label><input id="name" name="name" maxlength="150" required value="<?= e($student['name']) ?>"></div>
        <div><label for="roll_number">Roll number *</label><input id="roll_number" name="roll_number" maxlength="50" required value="<?= e($student['roll_number']) ?>"></div>
        <div><label for="course">Course *</label><input id="course" name="course" maxlength="120" required value="<?= e($student['course']) ?>"></div>
        <div><label for="email">Email</label><input id="email" name="email" type="email" maxlength="190" value="<?= e($student['email']) ?>"></div>
        <div><label for="phone">Phone</label><input id="phone" name="phone" maxlength="30" value="<?= e($student['phone']) ?>"></div>
        <div><label for="date_of_birth">Date of birth</label><input id="date_of_birth" name="date_of_birth" type="date" value="<?= e($student['date_of_birth']) ?>"></div>
    </div>
    <label for="address">Address</label><textarea id="address" name="address" maxlength="2000"><?= e($student['address']) ?></textarea>
    <div class="upload-grid">
        <div>
            <label for="profile_image">Profile image</label>
            <input id="profile_image" name="profile_image[]" type="file" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp">
            <p class="muted">JPEG, PNG, or WebP; maximum 5 MB. A new image keeps previous files.</p>
        </div>
        <div>
            <label for="documents">ID proof, certificates, or other documents</label>
            <input id="documents" name="documents[]" type="file" multiple accept=".jpg,.jpeg,.png,.webp,.pdf,application/pdf,image/jpeg,image/png,image/webp">
            <p class="muted">JPEG, PNG, WebP, or PDF; maximum 5 MB per file. Existing files are never overwritten.</p>
        </div>
    </div>
    <?php if ($files !== []): ?>
        <h2>Existing files</h2>
        <ul class="existing-files"><?php foreach ($files as $file): ?><li><a href="<?= e($file['file_path']) ?>" target="_blank" rel="noopener"><?= e($file['original_name']) ?></a> (<?= e($file['category'] === 'profile_image' ? 'Profile image' : 'Document') ?>)</li><?php endforeach; ?></ul>
    <?php endif; ?>
    <p class="actions"><button type="submit">Save student</button><a class="button secondary" href="<?= $id === null ? 'index.php' : 'student_view.php?id=' . (int) $id ?>">Cancel</a></p>
</form>
<?php pageEnd(); ?>
