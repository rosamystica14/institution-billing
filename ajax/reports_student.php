<?php
require_once __DIR__ . '/../includes/functions.php';
header('Content-Type: application/json');

try {
    $studentId = (int)($_GET['student_id'] ?? 0);
    if ($studentId <= 0) {
        jsonResponse(false, 'Please select a student.', [], 422);
    }

    $pdo = db();

    // Look up the selected row's identity first. Until a real
    // enrollments table exists (see item 12 of the original plan), a
    // person is identified by matching BOTH name and mobile number
    // together — mobile alone isn't enough, since siblings/family
    // members can legitimately share one contact number but are
    // different students and shouldn't have their fees merged.
    $stmt = $pdo->prepare("SELECT name, mobile FROM students WHERE id = ?");
    $stmt->execute([$studentId]);
    $row = $stmt->fetch();
    if (!$row) {
        jsonResponse(false, 'Student not found.', [], 404);
    }
    $name = $row['name'];
    $mobile = $row['mobile'];

    $enrollStmt = $pdo->prepare("
        SELECT s.*, c.name as course_name, c.fee as course_fee,
               c.fee_type as course_fee_type, c.duration as course_duration
        FROM students s
        JOIN courses c ON c.id = s.course_id
        WHERE s.mobile = ? AND LOWER(TRIM(s.name)) = LOWER(TRIM(?))
        ORDER BY s.admission_date ASC, s.id ASC
    ");
    $enrollStmt->execute([$mobile, $name]);
    $enrollmentRows = $enrollStmt->fetchAll();

    if (!$enrollmentRows) {
        jsonResponse(false, 'Student not found.', [], 404);
    }

    $person = [
        'name'        => $enrollmentRows[0]['name'],
        'mobile'      => $mobile,
        'father_name' => $enrollmentRows[0]['father_name'],
    ];

    $enrollments = [];
    $allPayments = [];

    foreach ($enrollmentRows as $s) {
        $sid = (int)$s['id'];
        $isMonthly = $s['course_fee_type'] === 'monthly';
        $balance = calculateStudentBalance($sid);
        $monthlyStatus = $isMonthly ? getMonthlyFeeStatus($sid) : [];

        $enrollments[] = [
            'student_id'         => $sid,
            'course_name'        => $s['course_name'],
            'fee_type'           => $s['course_fee_type'],
            'course_fee'         => (float)$s['course_fee'],
            'total_fee'          => $balance['total_fee'],
            'total_paid'         => $balance['total_paid'],
            'balance'            => $balance['balance'],
            'admission_date'     => $s['admission_date'],
            'monthly_fee_status' => $monthlyStatus,
        ];

        $paymentsStmt = $pdo->prepare("SELECT * FROM payments WHERE student_id = ? ORDER BY payment_date ASC, id ASC");
        $paymentsStmt->execute([$sid]);
        $payments = $paymentsStmt->fetchAll();

        foreach ($payments as $p) {
            $p['course_name'] = $s['course_name'];
            $p['billing_month_label'] = ($isMonthly && $p['billing_month'] && $p['billing_year'])
                ? (new DateTime("{$p['billing_year']}-{$p['billing_month']}-01"))->format('M Y')
                : null;
            $allPayments[] = $p;
        }
    }

    usort($allPayments, function ($a, $b) {
        $cmp = strcmp($b['payment_date'], $a['payment_date']);
        return $cmp !== 0 ? $cmp : ($b['id'] <=> $a['id']);
    });

    jsonResponse(true, '', [
        'person'      => $person,
        'enrollments' => $enrollments,
        'payments'    => $allPayments,
    ]);
} catch (Exception $e) {
    jsonResponse(false, 'Failed to load report: ' . $e->getMessage(), [], 500);
}
//reports_student.php