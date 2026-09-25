<?php
require_once __DIR__ . '/../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, 'Invalid request method.', [], 405);
}

try {
    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0) {
        jsonResponse(false, 'Invalid course ID.', [], 422);
    }

    $pdo = db();

    $check = $pdo->prepare("SELECT COUNT(*) as cnt FROM students WHERE course_id = ?");
    $check->execute([$id]);
    if ((int)$check->fetch()['cnt'] > 0) {
        jsonResponse(false, 'Cannot delete this course because students are enrolled in it. Please reassign or remove those students first.', [], 422);
    }

    $stmt = $pdo->prepare("DELETE FROM courses WHERE id = ?");
    $stmt->execute([$id]);

    jsonResponse(true, 'Course deleted successfully.');
} catch (Exception $e) {
    jsonResponse(false, 'Failed to delete course: ' . $e->getMessage(), [], 500);
}
