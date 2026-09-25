<?php
require_once __DIR__ . '/../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, 'Invalid request method.', [], 405);
}

try {
    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0) {
        jsonResponse(false, 'Invalid student ID.', [], 422);
    }

    $pdo = db();

    $stmt = $pdo->prepare("SELECT photo FROM students WHERE id = ?");
    $stmt->execute([$id]);
    $student = $stmt->fetch();

    if (!$student) {
        jsonResponse(false, 'Student not found.', [], 404);
    }

    // Payments are removed automatically via ON DELETE CASCADE
    $del = $pdo->prepare("DELETE FROM students WHERE id = ?");
    $del->execute([$id]);

    if (!empty($student['photo']) && file_exists(UPLOAD_PHOTO_PATH . $student['photo'])) {
        @unlink(UPLOAD_PHOTO_PATH . $student['photo']);
    }

    jsonResponse(true, 'Student deleted successfully.');
} catch (Exception $e) {
    jsonResponse(false, 'Failed to delete student: ' . $e->getMessage(), [], 500);
}
