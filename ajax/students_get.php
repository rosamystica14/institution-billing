<?php
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');

try {
    $id = (int)($_GET['id'] ?? 0);
    if ($id <= 0) {
        jsonResponse(false, 'Invalid student ID.', [], 422);
    }

    $pdo = db();

    $stmt = $pdo->prepare("
        SELECT s.*, c.name as course_name, c.fee as course_fee, c.fee_type as course_fee_type, c.duration as course_duration
        FROM students s
        JOIN courses c ON c.id = s.course_id
        WHERE s.id = ?
    ");
    $stmt->execute([$id]);
    $student = $stmt->fetch();

    if (!$student) {
        jsonResponse(false, 'Student not found.', [], 404);
    }

    $balance = calculateStudentBalance($id);
    $student['total_paid'] = $balance['total_paid'];
    $student['balance'] = $balance['balance'];

    $paymentsStmt = $pdo->prepare("SELECT * FROM payments WHERE student_id = ? ORDER BY payment_date DESC, id DESC");
    $paymentsStmt->execute([$id]);
    $payments = $paymentsStmt->fetchAll();

    $monthlyFeeStatus = $student['course_fee_type'] === 'monthly' ? getMonthlyFeeStatus($id) : [];

    jsonResponse(true, '', [
        'student' => $student,
        'payments' => $payments,
        'monthly_fee_status' => $monthlyFeeStatus,
    ]);
} catch (Exception $e) {
    jsonResponse(false, 'Failed to load student: ' . $e->getMessage(), [], 500);
}
