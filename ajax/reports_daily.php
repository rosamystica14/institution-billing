<?php
require_once __DIR__ . '/../includes/functions.php';
header('Content-Type: application/json');

try {
    $date = sanitize($_GET['date'] ?? date('Y-m-d'));
    $pdo = db();

    $stmt = $pdo->prepare("
        SELECT p.*, s.name as student_name, c.name as course_name, c.fee_type as course_fee_type
        FROM payments p
        JOIN students s ON s.id = p.student_id
        JOIN courses c ON c.id = s.course_id
        WHERE p.payment_date = ?
        ORDER BY p.id DESC
    ");
    $stmt->execute([$date]);
    $payments = $stmt->fetchAll();

    foreach ($payments as &$p) {
        $p['billing_month_label'] = ($p['course_fee_type'] === 'monthly' && $p['billing_month'] && $p['billing_year'])
            ? (new DateTime("{$p['billing_year']}-{$p['billing_month']}-01"))->format('M Y')
            : null;
    }
    unset($p);

    $total = array_sum(array_column($payments, 'amount'));

    jsonResponse(true, '', ['payments' => $payments, 'total' => $total, 'date' => $date]);
} catch (Exception $e) {
    jsonResponse(false, 'Failed to load report: ' . $e->getMessage(), [], 500);
}