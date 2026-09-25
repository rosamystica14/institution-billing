<?php
require_once __DIR__ . '/../includes/functions.php';
header('Content-Type: application/json');

try {
    $pdo = db();
    $stmt = $pdo->query("
        SELECT c.*,
            (SELECT COUNT(*) FROM students s WHERE s.course_id = c.id) as student_count
        FROM courses c
        ORDER BY c.id DESC
    ");
    $courses = $stmt->fetchAll();
    jsonResponse(true, '', $courses);
} catch (Exception $e) {
    jsonResponse(false, 'Failed to load courses: ' . $e->getMessage(), [], 500);
}
