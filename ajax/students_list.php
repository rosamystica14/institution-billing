<?php
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');

try {
    $pdo = db();
    $search = trim($_GET['search'] ?? '');

    $sql = "
        SELECT s.*, c.name as course_name, c.fee as course_fee, c.fee_type as fee_type
        FROM students s
        JOIN courses c ON c.id = s.course_id
    ";
    $params = [];

    if ($search !== '') {
        $sql .= " WHERE s.name LIKE ? OR s.mobile LIKE ? OR s.whatsapp_number LIKE ? OR c.name LIKE ?";
        $like = "%{$search}%";
        $params = [$like, $like, $like, $like];
    }

    $sql .= " ORDER BY s.id DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $students = $stmt->fetchAll();

    foreach ($students as &$student) {
        $balanceInfo = calculateStudentBalance((int)$student['id']);
        $student['total_paid'] = $balanceInfo['total_paid'];
        $student['balance'] = $balanceInfo['balance'];
    }

    jsonResponse(true, '', $students);
} catch (Exception $e) {
    jsonResponse(false, 'Failed to load students: ' . $e->getMessage(), [], 500);
}
