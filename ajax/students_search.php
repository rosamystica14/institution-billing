<?php
/**
 * Global search across students, by name / phone / course / receipt number.
 */
require_once __DIR__ . '/../includes/functions.php';
header('Content-Type: application/json');

try {
    $pdo = db();
    $q = trim($_GET['q'] ?? '');

    if ($q === '') {
        jsonResponse(true, '', []);
    }

    $like = "%{$q}%";

    $stmt = $pdo->prepare("
        SELECT DISTINCT s.id, s.name, s.mobile, c.name as course_name
        FROM students s
        JOIN courses c ON c.id = s.course_id
        LEFT JOIN payments p ON p.student_id = s.id
        WHERE s.name LIKE ? OR s.mobile LIKE ? OR c.name LIKE ? OR p.receipt_no LIKE ?
        ORDER BY s.name ASC
        LIMIT 20
    ");
    $stmt->execute([$like, $like, $like, $like]);
    $results = $stmt->fetchAll();

    foreach ($results as &$r) {
        $balance = calculateStudentBalance((int)$r['id']);
        $r['balance'] = $balance['balance'];
    }

    jsonResponse(true, '', $results);
} catch (Exception $e) {
    jsonResponse(false, 'Search failed: ' . $e->getMessage(), [], 500);
}
