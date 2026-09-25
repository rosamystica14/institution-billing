<?php
require_once __DIR__ . '/../includes/functions.php';
header('Content-Type: application/json');

try {
    $pdo = db();
    $stmt = $pdo->query("
        SELECT w.*, s.name as student_name
        FROM whatsapp_logs w
        LEFT JOIN students s ON s.id = w.student_id
        ORDER BY w.id DESC
        LIMIT 30
    ");
    $logs = $stmt->fetchAll();
    jsonResponse(true, '', $logs);
} catch (Exception $e) {
    jsonResponse(false, 'Failed to load WhatsApp logs: ' . $e->getMessage(), [], 500);
}
