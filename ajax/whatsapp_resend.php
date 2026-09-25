<?php
/**
 * Resend a WhatsApp payment confirmation for a given payment (e.g. if it failed previously).
 */
require_once __DIR__ . '/../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, 'Invalid request method.', [], 405);
}

try {
    $paymentId = (int)($_POST['payment_id'] ?? 0);
    if ($paymentId <= 0) {
        jsonResponse(false, 'Invalid payment ID.', [], 422);
    }

    $pdo = db();
    $stmt = $pdo->prepare("
        SELECT p.*, s.name as student_name, s.mobile, s.whatsapp_number, s.id as student_id
        FROM payments p JOIN students s ON s.id = p.student_id
        WHERE p.id = ?
    ");
    $stmt->execute([$paymentId]);
    $payment = $stmt->fetch();

    if (!$payment) {
        jsonResponse(false, 'Payment not found.', [], 404);
    }

    $balance = calculateStudentBalance((int)$payment['student_id']);
    $settings = getSettings();
    $institutionName = $settings['institution_name'] ?? APP_NAME;

    $message = buildPaymentMessage(
        $payment['student_name'],
        (float)$payment['amount'],
        $balance['total_paid'],
        $balance['balance'],
        $payment['receipt_no'],
        $institutionName
    );

    $phone = $payment['whatsapp_number'] ?: $payment['mobile'];
    $result = sendWhatsAppMessage($phone, $message, (int)$payment['student_id'], $paymentId);

    jsonResponse($result['success'], $result['message']);
} catch (Exception $e) {
    jsonResponse(false, 'Failed to resend WhatsApp message: ' . $e->getMessage(), [], 500);
}
