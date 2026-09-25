<?php
require_once __DIR__ . '/../includes/functions.php';
header('Content-Type: application/json');

try {
    $pdo = db();
    $studentId = (int)($_GET['student_id'] ?? 0);

    $sql = "
        SELECT p.*, s.name as student_name, s.mobile, c.name as course_name
        FROM payments p
        JOIN students s ON s.id = p.student_id
        JOIN courses c ON c.id = s.course_id
    ";
    $params = [];

    if ($studentId > 0) {
        $sql .= " WHERE p.student_id = ?";
        $params[] = $studentId;
    }

    $sql .= " ORDER BY p.payment_date DESC, p.id DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $payments = $stmt->fetchAll();

    jsonResponse(true, '', $payments);
} catch (Exception $e) {
    jsonResponse(false, 'Failed to load payments: ' . $e->getMessage(), [], 500);
}
