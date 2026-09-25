<?php
require_once __DIR__ . '/../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, 'Invalid request method.', [], 405);
}

/**
 * Best-effort extraction of a whole-number month count from free text
 * like "3 Months". Returns null if it can't be parsed (fine — it's
 * only used for reporting/duration_months, never for billing math).
 */
function parseDurationMonths(string $duration): ?int
{
    if (preg_match('/(\d+)\s*Month/i', $duration, $m)) {
        return (int)$m[1];
    }
    return null;
}

try {
    $missing = validateRequired($_POST, ['name', 'fee', 'fee_type']);
    if (!empty($missing)) {
        jsonResponse(false, 'Missing required fields: ' . implode(', ', $missing), [], 422);
    }

    $name = sanitize($_POST['name']);
    $fee = (float)$_POST['fee'];
    $feeType = $_POST['fee_type'] === 'monthly' ? 'monthly' : 'fixed';

    if ($fee < 0) {
        jsonResponse(false, 'Fee cannot be negative.', [], 422);
    }

    if ($feeType === 'monthly') {
        // Monthly courses always run indefinitely.
        $duration = 'Ongoing';
        $durationType = 'ongoing';
        $durationMonths = null;
    } else {
        $duration = sanitize($_POST['duration'] ?? '');
        $durationType = 'fixed';
        $durationMonths = parseDurationMonths($duration);
    }

    $pdo = db();
    $stmt = $pdo->prepare("
        INSERT INTO courses (name, fee, fee_type, duration, duration_type, duration_months, created_at, updated_at)
        VALUES (?, ?, ?, ?, ?, ?, datetime('now','localtime'), datetime('now','localtime'))
    ");
    $stmt->execute([$name, $fee, $feeType, $duration, $durationType, $durationMonths]);

    jsonResponse(true, 'Course added successfully.', ['id' => $pdo->lastInsertId()]);
} catch (Exception $e) {
    jsonResponse(false, 'Failed to add course: ' . $e->getMessage(), [], 500);
}