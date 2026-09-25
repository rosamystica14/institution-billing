<?php
/**
 * Reusable Helper Functions
 * Institution Billing & Fee Management System
 */

require_once __DIR__ . '/../config/database.php';

/**
 * Shortcut to get the PDO connection.
 */
function db(): PDO
{
    return Database::getConnection();
}

/**
 * Sanitize a string input (trim + strip tags) to help prevent XSS.
 */
function sanitize(?string $value): string
{
    return htmlspecialchars(trim((string)$value), ENT_QUOTES, 'UTF-8');
}

/**
 * Escape output safely for HTML display.
 */
function e(?string $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

/**
 * Send a standard JSON response and stop execution.
 */
function jsonResponse(bool $success, string $message = '', array $data = [], int $httpCode = 200): void
{
    http_response_code($httpCode);
    header('Content-Type: application/json');
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data'    => $data,
    ]);
    exit;
}

/**
 * Format a number as currency for display.
 */
function formatCurrency(float $amount): string
{
    return CURRENCY . number_format($amount, 2);
}

/**
 * Generate a unique receipt number in the format RCPT-YYYY-00001
 */
function generateReceiptNo(): string
{
    $pdo = db();
    $year = date('Y');

    $stmt = $pdo->prepare("SELECT COUNT(*) as cnt FROM payments WHERE receipt_no LIKE ?");
    $stmt->execute(["RCPT-{$year}-%"]);
    $count = (int)$stmt->fetch()['cnt'];

    $nextNumber = $count + 1;

    // Keep generating until we find a truly unique number (handles edge cases after deletions)
    do {
        $receiptNo = sprintf('RCPT-%s-%05d', $year, $nextNumber);
        $check = $pdo->prepare("SELECT COUNT(*) as cnt FROM payments WHERE receipt_no = ?");
        $check->execute([$receiptNo]);
        $exists = (int)$check->fetch()['cnt'] > 0;
        $nextNumber++;
    } while ($exists);

    return $receiptNo;
}

/**
 * Work out the final fee a student should be billed, based on their fee mode.
 *
 * - 'default'  -> returns null, meaning "no override" (caller should fall back to the course's fee)
 * - 'discount' -> returns the course fee minus the given percentage
 * - 'custom'   -> returns the given flat custom amount as-is
 *
 * This is always computed/verified on the server, never trusted from the browser.
 */
function computeFinalFee(float $courseFee, string $feeMode, float $discountPercent = 0.0, ?float $customAmount = null): ?float
{
    if ($feeMode === 'discount') {
        $discountPercent = max(0.0, min(100.0, $discountPercent));
        $final = $courseFee - ($courseFee * $discountPercent / 100);
        return round(max(0.0, $final), 2);
    }

    if ($feeMode === 'custom') {
        $final = $customAmount ?? 0.0;
        return round(max(0.0, $final), 2);
    }

    // 'default' (or anything unrecognized) -> no override
    return null;
}
function getMonthlyFeeStatus(int $studentId): array
{
    $pdo = db();
    $stmt = $pdo->prepare("
        SELECT s.admission_date, c.fee as monthly_fee, c.fee_type
        FROM students s JOIN courses c ON c.id = s.course_id
        WHERE s.id = ?
    ");
    $stmt->execute([$studentId]);
    $row = $stmt->fetch();

    if (!$row || ($row['fee_type'] ?? 'fixed') !== 'monthly') {
        return [];
    }

    $monthlyFee = (float)$row['monthly_fee'];

    $paidStmt = $pdo->prepare("
        SELECT billing_year, billing_month, COALESCE(SUM(amount), 0) as paid
        FROM payments
        WHERE student_id = ? AND billing_year IS NOT NULL AND billing_month IS NOT NULL
        GROUP BY billing_year, billing_month
    ");
    $paidStmt->execute([$studentId]);
    $paidMap = [];
    foreach ($paidStmt->fetchAll() as $p) {
        $paidMap[$p['billing_year'] . '-' . $p['billing_month']] = (float)$p['paid'];
    }

    $admissionDate = new DateTime($row['admission_date']);
    $cursor = new DateTime($admissionDate->format('Y-m-01'));
    $currentMonthStart = new DateTime(date('Y-m-01'));
    $upcomingLimit = (clone $currentMonthStart)->modify('+1 month');

    $months = [];
    while ($cursor <= $upcomingLimit) {
        $y = (int)$cursor->format('Y');
        $m = (int)$cursor->format('n');
        $paidAmt = $paidMap[$y . '-' . $m] ?? 0.0;

        if ($paidAmt >= $monthlyFee) {
            $status = 'paid';
        } elseif ($cursor > $currentMonthStart) {
            $status = 'upcoming';
        } else {
            $status = 'due';
        }

        $months[] = [
            'year'         => $y,
            'month'        => $m,
            'label'        => $cursor->format('F Y'),
            'monthly_fee'  => $monthlyFee,
            'paid_amount'  => $paidAmt,
            'status'       => $status,
        ];
        $cursor->modify('+1 month');
    }

    return $months;
}
/**
 * Calculate total paid and balance for a given student.
 * Uses the student's overridden fee (discount/custom) when set,
 * otherwise falls back to the course's default fee.
 * Returns ['total_fee' => float, 'total_paid' => float, 'balance' => float]
 */
function calculateStudentBalance(int $studentId): array
{
    $pdo = db();

    $stmt = $pdo->prepare("
        SELECT c.fee as course_fee, c.fee_type, s.fee_mode, s.final_fee
        FROM students s
        JOIN courses c ON c.id = s.course_id
        WHERE s.id = ?
    ");
    $stmt->execute([$studentId]);
    $row = $stmt->fetch();

    if (!$row) {
        return ['total_fee' => 0.0, 'total_paid' => 0.0, 'balance' => 0.0, 'fee_type' => 'fixed'];
    }

    $feeType = $row['fee_type'] ?? 'fixed';

    if ($feeType === 'monthly') {
        $months = getMonthlyFeeStatus($studentId);
        $totalPaid = 0.0;
        $arrears = 0.0;
        foreach ($months as $m) {
            $totalPaid += $m['paid_amount'];
            if ($m['status'] === 'due') {
                $arrears += max(0.0, $m['monthly_fee'] - $m['paid_amount']);
            }
        }
        return [
            'total_fee'  => null,
            'total_paid' => $totalPaid,
            'balance'    => $arrears,
            'fee_type'   => 'monthly',
        ];
    }

    $courseFee = (float)$row['course_fee'];
    $feeMode = $row['fee_mode'] ?? 'default';
    $finalFee = isset($row['final_fee']) && $row['final_fee'] !== null ? (float)$row['final_fee'] : null;
    $totalFee = ($feeMode !== 'default' && $finalFee !== null) ? $finalFee : $courseFee;

    $stmt = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) as paid FROM payments WHERE student_id = ?");
    $stmt->execute([$studentId]);
    $totalPaid = (float)$stmt->fetch()['paid'];

    return [
        'total_fee'  => $totalFee,
        'total_paid' => $totalPaid,
        'balance'    => $totalFee - $totalPaid,
        'fee_type'   => 'fixed',
    ];
}

/**
 * Get application settings as an associative array.
 */
function getSettings(): array
{
    $pdo = db();
    $stmt = $pdo->query("SELECT * FROM settings WHERE id = 1");
    $settings = $stmt->fetch();
    return $settings ?: [];
}

/**
 * Send a WhatsApp message via WhatsApp Cloud API.
 * Logs every attempt (success or failure) into whatsapp_logs table.
 *
 * @return array ['success' => bool, 'message' => string]
 */
function sendWhatsAppMessage(string $phone, string $message, ?int $studentId = null, ?int $paymentId = null): array
{
    $settings = getSettings();
    $token = $settings['whatsapp_token'] ?? '';
    $phoneNumberId = $settings['whatsapp_phone_number_id'] ?? '';
    $apiVersion = $settings['whatsapp_api_version'] ?? 'v19.0';

    $pdo = db();
    $logStmt = $pdo->prepare("
        INSERT INTO whatsapp_logs (student_id, payment_id, phone, message, status, response, created_at)
        VALUES (?, ?, ?, ?, ?, ?, datetime('now', 'localtime'))
    ");

    if (empty($token) || empty($phoneNumberId)) {
        $errorMsg = 'WhatsApp API is not configured. Please set Token and Phone Number ID in Settings.';
        $logStmt->execute([$studentId, $paymentId, $phone, $message, 'failed', $errorMsg]);
        return ['success' => false, 'message' => $errorMsg];
    }

    // Normalize phone number - remove spaces, dashes, and leading zeros; ensure country code
    $cleanPhone = preg_replace('/[^0-9]/', '', $phone);
    if (strlen($cleanPhone) === 10) {
        $cleanPhone = '91' . $cleanPhone; // default India country code, change if needed
    }

    $url = "https://graph.facebook.com/{$apiVersion}/{$phoneNumberId}/messages";

    $payload = [
        'messaging_product' => 'whatsapp',
        'to'                => $cleanPhone,
        'type'              => 'text',
        'text'              => ['body' => $message],
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $token,
        'Content-Type: application/json',
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($curlError) {
        $logStmt->execute([$studentId, $paymentId, $phone, $message, 'failed', 'cURL Error: ' . $curlError]);
        return ['success' => false, 'message' => 'Failed to reach WhatsApp API: ' . $curlError];
    }

    $decoded = json_decode($response, true);

    if ($httpCode >= 200 && $httpCode < 300 && isset($decoded['messages'])) {
        $logStmt->execute([$studentId, $paymentId, $phone, $message, 'sent', $response]);
        return ['success' => true, 'message' => 'WhatsApp message sent successfully.'];
    }

    $errorDetail = $decoded['error']['message'] ?? ('HTTP ' . $httpCode);
    $logStmt->execute([$studentId, $paymentId, $phone, $message, 'failed', $response ?: $errorDetail]);
    return ['success' => false, 'message' => 'WhatsApp send failed: ' . $errorDetail];
}

/**
 * Build the WhatsApp payment confirmation message text.
 */

function buildPaymentMessage(string $studentName, float $amountPaid, float $totalPaid, float $balance, string $receiptNo, string $institutionName, ?string $billingMonthLabel = null): string
{
    $date = date('d-m-Y');
    $body = "Hello {$studentName},\n\nPayment Received Successfully at {$institutionName}.\n\n";

    if ($billingMonthLabel !== null) {
        $body .= "Billing Month: {$billingMonthLabel}\n"
            . "Monthly Fee Paid: " . formatCurrency($amountPaid) . "\n"
            . "Status: PAID\n";
    } else {
        $body .= "Amount Paid: " . formatCurrency($amountPaid) . "\n"
            . "Total Paid: " . formatCurrency($totalPaid) . "\n"
            . "Remaining Balance: " . formatCurrency($balance) . "\n";
    }

    $body .= "Receipt No: {$receiptNo}\nDate: {$date}\n\nThank you.";
    return $body;
}

/**
 * Validate required fields are present and non-empty in an array.
 * Returns an array of missing field names (empty array = all present).
 */
function validateRequired(array $data, array $requiredFields): array
{
    $missing = [];
    foreach ($requiredFields as $field) {
        if (!isset($data[$field]) || trim((string)$data[$field]) === '') {
            $missing[] = $field;
        }
    }
    return $missing;
}

/**
 * Handle a student photo upload. Returns the stored filename or null.
 */
function handlePhotoUpload(array $file): ?string
{
    if (empty($file['name']) || $file['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }

    $allowedTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!in_array($mime, $allowedTypes, true)) {
        return null;
    }

    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = 'student_' . uniqid() . '.' . $ext;
    $destination = UPLOAD_PHOTO_PATH . $filename;

    if (!is_dir(UPLOAD_PHOTO_PATH)) {
        mkdir(UPLOAD_PHOTO_PATH, 0755, true);
    }

    if (move_uploaded_file($file['tmp_name'], $destination)) {
        return $filename;
    }

    return null;
}