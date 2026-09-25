<?php
require_once __DIR__ . '/../includes/functions.php';
header('Content-Type: application/json');

try {
    $pdo = db();
    $stmt = $pdo->query("
        SELECT s.id, s.name, s.mobile, s.admission_date, c.name as course_name, c.fee as course_fee, c.fee_type
        FROM students s
        JOIN courses c ON c.id = s.course_id
        ORDER BY s.name ASC
    ");
    $students = $stmt->fetchAll();

    $fixedPending = [];
    $monthlyDue = [];
    $totalPending = 0.0;

    foreach ($students as $s) {
        if ($s['fee_type'] === 'monthly') {
            $months = getMonthlyFeeStatus((int)$s['id']);
            foreach ($months as $m) {
                if ($m['status'] !== 'due') {
                    continue;
                }
                $dueAmount = max(0.0, $m['monthly_fee'] - $m['paid_amount']);
                if ($dueAmount <= 0) {
                    continue;
                }
                $monthlyDue[] = [
                    'id'          => (int)$s['id'],
                    'name'        => $s['name'],
                    'mobile'      => $s['mobile'],
                    'course_name' => $s['course_name'],
                    'month_label' => $m['label'],
                    'due_amount'  => $dueAmount,
                ];
                $totalPending += $dueAmount;
            }
        } else {
            $bal = calculateStudentBalance((int)$s['id']);
            if ($bal['balance'] > 0) {
                $fixedPending[] = [
                    'id'          => (int)$s['id'],
                    'name'        => $s['name'],
                    'mobile'      => $s['mobile'],
                    'course_name' => $s['course_name'],
                    'course_fee'  => $bal['total_fee'],
                    'total_paid'  => $bal['total_paid'],
                    'balance'     => $bal['balance'],
                ];
                $totalPending += $bal['balance'];
            }
        }
    }

    jsonResponse(true, '', [
        'fixed'         => $fixedPending,
        'monthly'       => $monthlyDue,
        'total_pending' => $totalPending,
    ]);
} catch (Exception $e) {
    jsonResponse(false, 'Failed to load report: ' . $e->getMessage(), [], 500);
}
//reports_pending.php