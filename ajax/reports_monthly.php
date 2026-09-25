<?php
require_once __DIR__ . '/../includes/functions.php';
header('Content-Type: application/json');

try {
    $month = sanitize($_GET['month'] ?? date('Y-m')); // format YYYY-MM
    $pdo = db();

    $stmt = $pdo->prepare("
        SELECT p.*, s.name as student_name, c.name as course_name, c.fee_type as course_fee_type
        FROM payments p
        JOIN students s ON s.id = p.student_id
        JOIN courses c ON c.id = s.course_id
        WHERE strftime('%Y-%m', p.payment_date) = ?
        ORDER BY p.payment_date DESC, p.id DESC
    ");
    $stmt->execute([$month]);
    $payments = $stmt->fetchAll();

    foreach ($payments as &$p) {
        $p['billing_month_label'] = ($p['course_fee_type'] === 'monthly' && $p['billing_month'] && $p['billing_year'])
            ? (new DateTime("{$p['billing_year']}-{$p['billing_month']}-01"))->format('M Y')
            : null;
    }
    unset($p);

    $total = array_sum(array_column($payments, 'amount'));

    // Day-wise breakdown for a small chart/table
    $dayWiseStmt = $pdo->prepare("
        SELECT payment_date, SUM(amount) as total
        FROM payments
        WHERE strftime('%Y-%m', payment_date) = ?
        GROUP BY payment_date
        ORDER BY payment_date ASC
    ");
    $dayWiseStmt->execute([$month]);
    $dayWise = $dayWiseStmt->fetchAll();

    // Course-wise breakdown (fixed + monthly payments both count toward
    // their course's collection total for the month).
    $courseWiseStmt = $pdo->prepare("
        SELECT c.name as course_name, c.fee_type as course_fee_type, SUM(p.amount) as total
        FROM payments p
        JOIN students s ON s.id = p.student_id
        JOIN courses c ON c.id = s.course_id
        WHERE strftime('%Y-%m', p.payment_date) = ?
        GROUP BY c.id
        ORDER BY total DESC
    ");
    $courseWiseStmt->execute([$month]);
    $courseWise = $courseWiseStmt->fetchAll();

    jsonResponse(true, '', [
        'payments'    => $payments,
        'total'       => $total,
        'day_wise'    => $dayWise,
        'course_wise' => $courseWise,
        'month'       => $month,
    ]);
} catch (Exception $e) {
    jsonResponse(false, 'Failed to load report: ' . $e->getMessage(), [], 500);
}