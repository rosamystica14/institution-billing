<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';

$paymentId = (int)($_GET['payment_id'] ?? 0);
if ($paymentId <= 0) {
    die('Invalid receipt.');
}

$pdo = db();
$stmt = $pdo->prepare("
    SELECT p.*, s.name as student_name, s.mobile, s.father_name,
           c.name as course_name, c.fee_type as course_fee_type, c.fee as course_rate
    FROM payments p
    JOIN students s ON s.id = p.student_id
    JOIN courses c ON c.id = s.course_id
    WHERE p.id = ?
");
$stmt->execute([$paymentId]);
$payment = $stmt->fetch();

if (!$payment) {
    die('Receipt not found.');
}

$isMonthly = ($payment['course_fee_type'] ?? 'fixed') === 'monthly';
$balance = calculateStudentBalance((int)$payment['student_id']);
$settings = getSettings();
$institutionName = $settings['institution_name'] ?? APP_NAME;

$billingMonthLabel = null;
if ($isMonthly && $payment['billing_month'] && $payment['billing_year']) {
    $billingMonthLabel = (new DateTime("{$payment['billing_year']}-{$payment['billing_month']}-01"))->format('F Y');
}

// Build the WhatsApp share message and link
$waMessage = buildPaymentMessage(
    $payment['student_name'],
    (float)$payment['amount'],
    $balance['total_paid'],
    $balance['balance'],
    $payment['receipt_no'],
    $institutionName,
    $billingMonthLabel
);

// Normalize phone number - remove non-digits, add country code if 10-digit local number
$waPhone = preg_replace('/[^0-9]/', '', $payment['mobile']);
if (strlen($waPhone) === 10) {
    $waPhone = '91' . $waPhone; // change default country code if needed
}

$waLink = 'https://wa.me/' . $waPhone . '?text=' . urlencode($waMessage);

// Build the detail rows table, branching on fee type
if ($isMonthly) {
    $rows = [
        ['Receipt No.', $payment['receipt_no'], 'Date', date('d-m-Y', strtotime($payment['payment_date']))],
        ['Student Name', $payment['student_name'], "Father's Name", $payment['father_name'] ?: '-'],
        ['Course', $payment['course_name'], 'Mobile', $payment['mobile']],
        ['Billing Month', $billingMonthLabel ?: '-', 'Payment Mode', $payment['payment_mode']],
        ['Remarks', $payment['remarks'] ?: '-', '', ''],
    ];
} else {
    $rows = [
        ['Receipt No.', $payment['receipt_no'], 'Date', date('d-m-Y', strtotime($payment['payment_date']))],
        ['Student Name', $payment['student_name'], "Father's Name", $payment['father_name'] ?: '-'],
        ['Course', $payment['course_name'], 'Mobile', $payment['mobile']],
        ['Payment Mode', $payment['payment_mode'], 'Remarks', $payment['remarks'] ?: '-'],
    ];
}

// Build the summary boxes, branching on fee type
if ($isMonthly) {
    $boxes = [
        ['label' => 'Monthly Fee', 'value' => formatCurrency((float)$payment['course_rate']), 'class' => 'text-primary'],
        ['label' => 'Amount Paid', 'value' => formatCurrency((float)$payment['amount']), 'class' => 'text-success'],
        ['label' => 'Status', 'value' => 'PAID', 'class' => 'text-success'],
    ];
} else {
    $boxes = [
        ['label' => 'Amount Paid Now', 'value' => formatCurrency((float)$payment['amount']), 'class' => 'text-primary'],
        ['label' => 'Total Paid Till Date', 'value' => formatCurrency($balance['total_paid']), 'class' => 'text-success'],
        ['label' => 'Remaining Balance', 'value' => formatCurrency($balance['balance']), 'class' => 'text-danger'],
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Receipt <?= e($payment['receipt_no']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <link href="<?= BASE_URL ?>/assets/css/style.css" rel="stylesheet">
    <style>
        body { background: #eef2f7; }
        .receipt-box {
            max-width: 720px;
            margin: 30px auto;
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 4px 18px rgba(0,0,0,0.08);
            overflow: hidden;
        }
        .receipt-header {
            background: linear-gradient(90deg, #082f6b, #0d47a1);
            color: #fff;
            padding: 28px 32px;
        }
        .receipt-body { padding: 32px; }
        .receipt-table td { padding: 10px 8px; }
        .receipt-table tr:not(:last-child) td { border-bottom: 1px solid #eef1f5; }
        .stamp {
            display: inline-block;
            border: 2px solid #2e7d32;
            color: #2e7d32;
            padding: 6px 16px;
            border-radius: 6px;
            font-weight: 700;
            transform: rotate(-6deg);
        }
    </style>
</head>
<body>

<div class="container no-print py-3 text-center">
    <button class="btn btn-primary" onclick="window.print()"><i class="bi bi-printer-fill"></i> Print Receipt</button>

    <a href="<?= e($waLink) ?>" class="btn btn-success" target="_blank">
        <i class="bi bi-whatsapp"></i> Share via WhatsApp
    </a>
    <button class="btn btn-success" id="shareWhatsAppBtn">
    <i class="bi bi-whatsapp"></i> Copy Receipt & Share via WhatsApp
</button>  
    <a href="<?= BASE_URL ?>/ajax/receipt_pdf.php?payment_id=<?= (int)$paymentId ?>" class="btn btn-success">
    <i class="bi bi-file-earmark-pdf-fill"></i> Download PDF
    </a>
    <a href="javascript:history.back()" class="btn btn-outline-secondary"><i class="bi bi-x-lg"></i> Close</a>
</div>

<div class="receipt-box receipt-page">
    <div class="receipt-header d-flex align-items-center gap-3">
        <?php if (!empty($settings['logo'])): ?>
            <img src="<?= BASE_URL ?>/assets/uploads/logo/<?= e($settings['logo']) ?>" alt="Logo" height="56" class="rounded bg-white p-1">
        <?php else: ?>
            <i class="bi bi-mortarboard-fill" style="font-size: 2.6rem;"></i>
        <?php endif; ?>
        <div>
            <h4 class="mb-0 fw-bold"><?= e($institutionName) ?></h4>
            <div class="small opacity-75"><?= e($settings['address'] ?? '') ?></div>
            <div class="small opacity-75">
                <?= !empty($settings['phone']) ? 'Ph: ' . e($settings['phone']) : '' ?>
                <?= !empty($settings['email']) ? ' | ' . e($settings['email']) : '' ?>
            </div>
        </div>
    </div>

    <div class="receipt-body">
        <div class="d-flex justify-content-between align-items-start mb-4">
            <h5 class="fw-bold text-primary mb-0">FEE RECEIPT</h5>
            <span class="stamp"><i class="bi bi-check-circle-fill"></i> PAID</span>
        </div>

        <table class="table receipt-table mb-4">
            <?php foreach ($rows as $row): ?>
            <tr>
                <td class="text-muted"><?= e($row[0]) ?></td>
                <td class="fw-bold"><?= e((string)$row[1]) ?></td>
                <td class="text-muted"><?= e($row[2]) ?></td>
                <td class="fw-bold"><?= e((string)$row[3]) ?></td>
            </tr>
            <?php endforeach; ?>
        </table>

        <div class="row g-3 mb-4">
            <?php foreach ($boxes as $box): ?>
            <div class="col-4">
                <div class="border rounded p-3 text-center">
                    <div class="text-muted small"><?= e($box['label']) ?></div>
                    <div class="fs-5 fw-bold <?= $box['class'] ?>"><?= e($box['value']) ?></div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <div class="d-flex justify-content-between mt-5 pt-4">
            <div class="text-muted small">This is a computer-generated receipt.</div>
            <div class="text-center">
                <div style="border-top: 1px solid #333; width: 180px;"></div>
                <div class="small text-muted">Authorized Signature</div>
            </div>
        </div>
    </div>
</div>
<script>
document.getElementById('shareWhatsAppBtn').addEventListener('click', async function () {
    const btn = this;
    const originalText = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<i class="bi bi-hourglass-split"></i> Copying...';

    try {
        const imageUrl = '<?= BASE_URL ?>/ajax/receipt_image.php?payment_id=<?= (int)$paymentId ?>';

        await navigator.clipboard.write([
            new ClipboardItem({
                'image/png': fetch(imageUrl).then(res => res.blob())
            })
        ]);

        window.open('<?= $waLink ?>', '_blank');

        btn.innerHTML = '<i class="bi bi-check-circle-fill"></i> Copied! Press Ctrl+V in WhatsApp';
        setTimeout(() => {
            btn.disabled = false;
            btn.innerHTML = originalText;
        }, 3000);

    } catch (err) {
        console.error('Clipboard error:', err);
        btn.disabled = false;
        btn.innerHTML = originalText;
        alert('Could not copy the image automatically. Your browser may not support this — try Chrome or Edge, and make sure the site is opened via https:// or localhost.');
    }
});
</script>
</body>
</html>