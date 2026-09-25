<?php
require_once __DIR__ . '/../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, 'Invalid request method.', [], 405);
}

try {
    $missing = validateRequired($_POST, ['id', 'amount', 'payment_date', 'payment_mode']);
    if (!empty($missing)) {
        jsonResponse(false, 'Missing required fields: ' . implode(', ', $missing), [], 422);
    }

    $id = (int)$_POST['id'];
    $amount = (float)$_POST['amount'];
    $paymentDate = sanitize($_POST['payment_date']);
    $paymentMode = sanitize($_POST['payment_mode']);
    $remarks = sanitize($_POST['remarks'] ?? '');

    if ($amount <= 0) {
        jsonResponse(false, 'Payment amount must be greater than zero.', [], 422);
    }

    $allowedModes = ['Cash', 'UPI', 'Card', 'Bank Transfer', 'Cheque'];
    if (!in_array($paymentMode, $allowedModes, true)) {
        jsonResponse(false, 'Invalid payment mode.', [], 422);
    }

    $pdo = db();

    $existing = $pdo->prepare("SELECT * FROM payments WHERE id = ?");
    $existing->execute([$id]);
    $payment = $existing->fetch();
    if (!$payment) {
        jsonResponse(false, 'Payment not found.', [], 404);
    }

    $stmt = $pdo->prepare("
        UPDATE payments
        SET amount = ?, payment_date = ?, payment_mode = ?, remarks = ?
        WHERE id = ?
    ");
    $stmt->execute([$amount, $paymentDate, $paymentMode, $remarks, $id]);

    jsonResponse(true, 'Payment updated successfully.');
} catch (Exception $e) {
    jsonResponse(false, 'Failed to update payment: ' . $e->getMessage(), [], 500);
}