<?php
declare(strict_types=1);
require __DIR__ . '/config.php';if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    exit('Method not allowed.');
}
verifyCsrfToken();
$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
if ($id === null || $id === false || $id < 1) {
    http_response_code(400);
    exit('A valid student ID is required.');
}try {
    $pdo = db();
    $files = filesForStudent($pdo, $id);
    $pdo->beginTransaction();
    $statement = $pdo->prepare('DELETE FROM students WHERE id = ?');
    $statement->execute([$id]);
    if ($statement->rowCount() === 0) {
        $pdo->rollBack();
        http_response_code(404);
        exit('Student not found.');
    }
    $pdo->commit();    $failures = removeStoredFiles($files);
    if ($failures !== []) {
        error_log(implode(' ', $failures));
        setFlash('Student deleted, but one or more uploaded files could not be removed. Contact the administrator.', 'error');
    } else {
        setFlash('Student and associated files deleted successfully.');
    }
    header('Location: index.php');
    exit;
} catch (PDOException $exception) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log($exception->getMessage());
    http_response_code(500);
    exit('Could not delete the student. Check the database connection and try again.');
}
