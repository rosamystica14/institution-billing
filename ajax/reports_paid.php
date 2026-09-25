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

    $fixedPaid = [];
    $monthlyCurrent = [];
    $totalCollected = 0.0;

    foreach ($students as $s) {
        if ($s['fee_type'] === 'monthly') {
            $months = getMonthlyFeeStatus((int)$s['id']);
            $totalPaid = array_sum(array_column($months, 'paid_amount'));
            $hasDue = false;
            foreach ($months as $m) {
                if ($m['status'] === 'due') { $hasDue = true; break; }
            }
            // "Fully paid" for a monthly course means no outstanding due
            // months as of today (future/upcoming months don't count).
            if (!$hasDue && $totalPaid > 0) {
                $monthlyCurrent[] = [
                    'id'          => (int)$s['id'],
                    'name'        => $s['name'],
                    'mobile'      => $s['mobile'],
                    'course_name' => $s['course_name'],
                    'monthly_fee' => (float)$s['course_fee'],
                    'total_paid'  => $totalPaid,
                ];
                $totalCollected += $totalPaid;
            }
        } else {
            $bal = calculateStudentBalance((int)$s['id']);
            if ($bal['balance'] <= 0 && $bal['total_paid'] > 0) {
                $fixedPaid[] = [
                    'id'          => (int)$s['id'],
                    'name'        => $s['name'],
                    'mobile'      => $s['mobile'],
                    'course_name' => $s['course_name'],
                    'course_fee'  => $bal['total_fee'],
                    'total_paid'  => $bal['total_paid'],
                ];
                $totalCollected += $bal['total_paid'];
            }
        }
    }

    jsonResponse(true, '', [
        'fixed'      => $fixedPaid,
        'monthly'    => $monthlyCurrent,
        'total_paid' => $totalCollected,
    ]);
} catch (Exception $e) {
    jsonResponse(false, 'Failed to load report: ' . $e->getMessage(), [], 500);
}
//reports_paid.php