<?php
require_once __DIR__ . '/../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, 'Invalid request method.', [], 405);
}

try {
    $missing = validateRequired($_POST, ['student_id', 'amount', 'payment_date', 'payment_mode']);
    if (!empty($missing)) {
        jsonResponse(false, 'Missing required fields: ' . implode(', ', $missing), [], 422);
    }

    $studentId = (int)$_POST['student_id'];
    $amount = (float)$_POST['amount'];
    $paymentDate = sanitize($_POST['payment_date']);
    $paymentMode = sanitize($_POST['payment_mode']);
    $remarks = sanitize($_POST['remarks'] ?? '');
    $sendWhatsapp = isset($_POST['send_whatsapp']) && $_POST['send_whatsapp'] === '1';

    if ($amount <= 0) {
        jsonResponse(false, 'Payment amount must be greater than zero.', [], 422);
    }

    $pdo = db();

    $studentStmt = $pdo->prepare("
        SELECT s.*, c.name as course_name, c.fee as course_fee, c.fee_type
        FROM students s JOIN courses c ON c.id = s.course_id
        WHERE s.id = ?
    ");
    $studentStmt->execute([$studentId]);
    $student = $studentStmt->fetch();

    if (!$student) {
        jsonResponse(false, 'Student not found.', [], 404);
    }

    $feeType = $student['fee_type'] ?? 'fixed';
    $billingMonth = null;
    $billingYear = null;
    $billingMonthLabel = null;

    if ($feeType === 'monthly') {
        $missingMonthly = validateRequired($_POST, ['billing_month', 'billing_year']);
        if (!empty($missingMonthly)) {
            jsonResponse(false, 'Please select the billing month for this monthly course.', [], 422);
        }

        $billingMonth = (int)$_POST['billing_month'];
        $billingYear = (int)$_POST['billing_year'];

        if ($billingMonth < 1 || $billingMonth > 12) {
            jsonResponse(false, 'Invalid billing month.', [], 422);
        }

        $admissionMonthStart = new DateTime((new DateTime($student['admission_date']))->format('Y-m-01'));
        $billingMonthStart = new DateTime("{$billingYear}-{$billingMonth}-01");
        if ($billingMonthStart < $admissionMonthStart) {
            jsonResponse(false, 'Billing month cannot be before the admission date.', [], 422);
        }

        $billingMonthLabel = $billingMonthStart->format('F Y');
    }

    $balanceBefore = calculateStudentBalance($studentId);
    if ($amount > $balanceBefore['balance'] && $balanceBefore['balance'] > 0) {
        // Allow it but warn; some institutions accept advance payments. We won't hard block.
    }

    $receiptNo = generateReceiptNo();

    $pdo->beginTransaction();
    try {
        $insertStmt = $pdo->prepare("
            INSERT INTO payments (student_id, receipt_no, amount, payment_date, payment_mode, remarks, billing_month, billing_year, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, datetime('now','localtime'))
        ");
        $insertStmt->execute([$studentId, $receiptNo, $amount, $paymentDate, $paymentMode, $remarks, $billingMonth, $billingYear]);
        $paymentId = $pdo->lastInsertId();
        $pdo->commit();
    } catch (Exception $inner) {
        $pdo->rollBack();
        throw $inner;
    }

    $balanceAfter = calculateStudentBalance($studentId);
    $settings = getSettings();
    $institutionName = $settings['institution_name'] ?? APP_NAME;

    $whatsappResult = ['success' => false, 'message' => 'WhatsApp notification was not requested.'];
    if ($sendWhatsapp) {
        $phone = $student['whatsapp_number'] ?: $student['mobile'];
        $message = buildPaymentMessage(
            $student['name'],
            $amount,
            $balanceAfter['total_paid'],
            $balanceAfter['balance'],
            $receiptNo,
            $institutionName,
            $billingMonthLabel
        );
        $whatsappResult = sendWhatsAppMessage($phone, $message, $studentId, $paymentId);
    }

    jsonResponse(true, 'Payment received successfully.', [
        'payment_id'   => $paymentId,
        'receipt_no'   => $receiptNo,
        'total_paid'   => $balanceAfter['total_paid'],
        'balance'      => $balanceAfter['balance'],
        'billing_month'=> $billingMonth,
        'billing_year' => $billingYear,
        'whatsapp'     => $whatsappResult,
    ]);
} catch (Exception $e) {
    jsonResponse(false, 'Failed to record payment: ' . $e->getMessage(), [], 500);
}