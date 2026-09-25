<?php
require_once __DIR__ . '/../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, 'Invalid request method.', [], 405);
}

try {
    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0) {
        jsonResponse(false, 'Invalid payment ID.', [], 422);
    }

    $pdo = db();

    $existing = $pdo->prepare("SELECT id FROM payments WHERE id = ?");
    $existing->execute([$id]);
    if (!$existing->fetch()) {
        jsonResponse(false, 'Payment not found.', [], 404);
    }

    $del = $pdo->prepare("DELETE FROM payments WHERE id = ?");
    $del->execute([$id]);

    jsonResponse(true, 'Payment deleted successfully.');
} catch (Exception $e) {
    jsonResponse(false, 'Failed to delete payment: ' . $e->getMessage(), [], 500);
}