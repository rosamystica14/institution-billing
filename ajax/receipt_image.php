<?php
require_once __DIR__ . '/../includes/functions.php';

$paymentId = (int)($_GET['payment_id'] ?? 0);
if ($paymentId <= 0) {
    http_response_code(400);
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
    http_response_code(404);
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

function ii($v) { return (int) round($v); }

$S = 3;
$W = ii(720 * $S);
// Monthly receipts have one extra detail row, so give the canvas a bit more height.
$H = ii(($isMonthly ? 600 : 560) * $S);

$im = imagecreatetruecolor($W, $H);
imageantialias($im, true);

$white     = imagecolorallocate($im, 255, 255, 255);
$lightBlue = imagecolorallocate($im, 195, 206, 225);
$headerC1  = [8, 47, 107];
$headerC2  = [13, 71, 161];
$gray      = imagecolorallocate($im, 108, 117, 125);
$black     = imagecolorallocate($im, 33, 37, 41);
$blue      = imagecolorallocate($im, 13, 110, 253);
$green     = imagecolorallocate($im, 25, 135, 84);
$red       = imagecolorallocate($im, 220, 53, 69);
$borderClr = imagecolorallocate($im, 222, 226, 230);

imagefill($im, 0, 0, $white);

$fontRegular = __DIR__ . '/../assets/fonts/DejaVuSans.ttf';
$fontBold    = __DIR__ . '/../assets/fonts/DejaVuSans-Bold.ttf';

function drawText($im, $font, $size, $color, $x, $y, $text) {
    imagettftext($im, ii($size), 0, ii($x), ii($y), $color, $font, $text);
}

function textWidth($font, $size, $text) {
    $box = imagettfbbox(ii($size), 0, $font, $text);
    return abs($box[2] - $box[0]);
}

// Shrinks font size until the text fits within maxWidth, down to a minimum size
function fittedFontSize($font, $startSize, $minSize, $text, $maxWidth) {
    $size = $startSize;
    while ($size > $minSize && textWidth($font, $size, $text) > $maxWidth) {
        $size -= 0.5;
    }
    return $size;
}

function roundedRect($im, $x1, $y1, $x2, $y2, $r, $color) {
    $x1 = ii($x1); $y1 = ii($y1); $x2 = ii($x2); $y2 = ii($y2); $r = ii($r);
    imageline($im, $x1 + $r, $y1, $x2 - $r, $y1, $color);
    imageline($im, $x1 + $r, $y2, $x2 - $r, $y2, $color);
    imageline($im, $x1, $y1 + $r, $x1, $y2 - $r, $color);
    imageline($im, $x2, $y1 + $r, $x2, $y2 - $r, $color);
    imagearc($im, $x1 + $r, $y1 + $r, $r * 2, $r * 2, 180, 270, $color);
    imagearc($im, $x2 - $r, $y1 + $r, $r * 2, $r * 2, 270, 360, $color);
    imagearc($im, $x2 - $r, $y2 - $r, $r * 2, $r * 2, 0, 90, $color);
    imagearc($im, $x1 + $r, $y2 - $r, $r * 2, $r * 2, 90, 180, $color);
}

// ---- Header gradient ----
$headerHeight = ii(150 * $S);
for ($x = 0; $x < $W; $x++) {
    $ratio = $x / $W;
    $r = ii($headerC1[0] + ($headerC2[0] - $headerC1[0]) * $ratio);
    $g = ii($headerC1[1] + ($headerC2[1] - $headerC1[1]) * $ratio);
    $b = ii($headerC1[2] + ($headerC2[2] - $headerC1[2]) * $ratio);
    imageline($im, $x, 0, $x, $headerHeight, imagecolorallocate($im, $r, $g, $b));
}

$padX = ii(32 * $S);
$logoSize = ii(48 * $S);
$logoX = $padX;
$logoY = ii(($headerHeight - $logoSize) / 2);

$logoPath = !empty($settings['logo']) ? __DIR__ . '/../assets/uploads/logo/' . $settings['logo'] : null;

if ($logoPath && file_exists($logoPath)) {
    $srcImg = @imagecreatefromstring(file_get_contents($logoPath));
    if ($srcImg) {
        imagecopyresampled($im, $srcImg, $logoX, $logoY, 0, 0, $logoSize, $logoSize, imagesx($srcImg), imagesy($srcImg));
        imagedestroy($srcImg);
    }
} else {
    $capCenterX = ii($logoX + $logoSize / 2);
    $capCenterY = ii($logoY + $logoSize * 0.42);
    $capW = ii($logoSize * 0.95);
    $capH = ii($logoSize * 0.4);

    $points = [
        $capCenterX, $capCenterY - intdiv($capH, 2),
        $capCenterX + intdiv($capW, 2), $capCenterY,
        $capCenterX, $capCenterY + intdiv($capH, 2),
        $capCenterX - intdiv($capW, 2), $capCenterY,
    ];
    imagefilledpolygon($im, $points, $white);
    imagefilledellipse($im, $capCenterX, ii($capCenterY + $capH * 0.35), ii($capW * 0.55), ii($capH * 0.55), $white);
    imageline($im, ii($capCenterX + $capW * 0.35), $capCenterY, ii($capCenterX + $capW * 0.35), ii($logoY + $logoSize), $white);
    imagefilledellipse($im, ii($capCenterX + $capW * 0.35), ii($logoY + $logoSize), ii(5 * $S), ii(5 * $S), $white);
}

$textX = ii($logoX + $logoSize + 18 * $S);
$maxNameWidth = $W - $textX - $padX;

$nameSize = fittedFontSize($fontBold, 20 * $S, 11 * $S, $institutionName, $maxNameWidth);

drawText($im, $fontBold, $nameSize, $white, $textX, $logoY + 24 * $S, $institutionName);
drawText($im, $fontRegular, 12 * $S, $lightBlue, $textX, $logoY + 44 * $S, $settings['address'] ?? '');
$contactLine = trim((!empty($settings['phone']) ? 'Ph: ' . $settings['phone'] : '') . (!empty($settings['email']) ? '  |  ' . $settings['email'] : ''));
drawText($im, $fontRegular, 12 * $S, $lightBlue, $textX, $logoY + 62 * $S, $contactLine);

// ---- Body ----
$bodyPad = ii(32 * $S);
$bodyY = ii($headerHeight + 45 * $S);

drawText($im, $fontBold, 18 * $S, $blue, $bodyPad, $bodyY, "FEE RECEIPT");

// ---- PAID stamp ----
$stampW = ii(150 * $S);
$stampH = ii(48 * $S);
$stampCanvas = imagecreatetruecolor($stampW, $stampH);
imagesavealpha($stampCanvas, true);
$transparent = imagecolorallocatealpha($stampCanvas, 0, 0, 0, 127);
imagefill($stampCanvas, 0, 0, $transparent);
$stampGreen = imagecolorallocate($stampCanvas, 46, 125, 50);

roundedRect($stampCanvas, 2, 2, $stampW - 3, $stampH - 3, 10 * $S, $stampGreen);
roundedRect($stampCanvas, 3, 3, $stampW - 4, $stampH - 4, 10 * $S, $stampGreen);

$circR = ii(9 * $S);
$circX = ii(24 * $S);
$circY = ii($stampH / 2);
imagefilledellipse($stampCanvas, $circX, $circY, $circR * 2, $circR * 2, $stampGreen);
imagesetthickness($stampCanvas, ii(2 * $S / 2));
imageline($stampCanvas, $circX - ii(4 * $S), $circY, $circX - ii(1 * $S), $circY + ii(3 * $S), $white);
imageline($stampCanvas, $circX - ii(1 * $S), $circY + ii(3 * $S), $circX + ii(5 * $S), $circY - ii(4 * $S), $white);
imagesetthickness($stampCanvas, 1);

drawText($stampCanvas, $fontBold, 13 * $S, $stampGreen, $circX + $circR + ii(7 * $S), $circY + ii(5 * $S), "PAID");

$rotated = imagerotate($stampCanvas, 6, $transparent);
imagesavealpha($rotated, true);
$rw = imagesx($rotated);
$rh = imagesy($rotated);
imagecopy($im, $rotated, ii($W - $bodyPad - $rw + (($rw - $stampW) / 2)), ii($bodyY - 32 * $S - (($rh - $stampH) / 2)), 0, 0, $rw, $rh);
imagedestroy($stampCanvas);
imagedestroy($rotated);

// ---- Detail rows ----
$rowY = ii($bodyY + 48 * $S);
$rowHeight = ii(52 * $S);
$col1X = $bodyPad; $col2X = ii(190 * $S); $col3X = ii(420 * $S); $col4X = ii(560 * $S);

if ($isMonthly) {
    $rows = [
        ["Receipt No.", $payment['receipt_no'], "Date", date('d-m-Y', strtotime($payment['payment_date']))],
        ["Student Name", $payment['student_name'], "Father's Name", $payment['father_name'] ?: '-'],
        ["Course", $payment['course_name'], "Mobile", $payment['mobile']],
        ["Billing Month", $billingMonthLabel ?: '-', "Payment Mode", $payment['payment_mode']],
        ["Remarks", $payment['remarks'] ?: '-', "", ""],
    ];
} else {
    $rows = [
        ["Receipt No.", $payment['receipt_no'], "Date", date('d-m-Y', strtotime($payment['payment_date']))],
        ["Student Name", $payment['student_name'], "Father's Name", $payment['father_name'] ?: '-'],
        ["Course", $payment['course_name'], "Mobile", $payment['mobile']],
        ["Payment Mode", $payment['payment_mode'], "Remarks", $payment['remarks'] ?: '-'],
    ];
}

foreach ($rows as $i => $row) {
    drawText($im, $fontRegular, 12 * $S, $gray, $col1X, $rowY, $row[0]);
    drawText($im, $fontBold, 12.5 * $S, $black, $col2X, $rowY, $row[1]);
    if ($row[2] !== '') {
        drawText($im, $fontRegular, 12 * $S, $gray, $col3X, $rowY, $row[2]);
        drawText($im, $fontBold, 12.5 * $S, $black, $col4X, $rowY, $row[3]);
    }
    if ($i < count($rows) - 1) {
        imageline($im, $col1X, ii($rowY + 14 * $S), ii($W - $bodyPad), ii($rowY + 14 * $S), $borderClr);
    }
    $rowY = ii($rowY + $rowHeight);
}

// ---- Amount boxes ----
$boxY = ii($rowY + 22 * $S);
$boxH = ii(78 * $S);
$gap = ii(14 * $S);
$boxW = ii(($W - $bodyPad * 2 - $gap * 2) / 3);

if ($isMonthly) {
    $boxes = [
        ["Monthly Fee", formatCurrency((float)$payment['course_rate']), $blue],
        ["Amount Paid", formatCurrency((float)$payment['amount']), $green],
        ["Status", "PAID", $green],
    ];
} else {
    $boxes = [
        ["Amount Paid Now", formatCurrency((float)$payment['amount']), $blue],
        ["Total Paid Till Date", formatCurrency($balance['total_paid']), $green],
        ["Remaining Balance", formatCurrency($balance['balance']), $red],
    ];
}

foreach ($boxes as $i => $box) {
    $x = ii($bodyPad + $i * ($boxW + $gap));
    roundedRect($im, $x, $boxY, $x + $boxW, $boxY + $boxH, 6 * $S, $borderClr);
    drawText($im, $fontRegular, 10.5 * $S, $gray, $x + 16 * $S, $boxY + 27 * $S, $box[0]);
    drawText($im, $fontBold, 16 * $S, $box[2], $x + 16 * $S, $boxY + 58 * $S, $box[1]);
}

// ---- Footer ----
$footerY = ii($boxY + $boxH + 55 * $S);
drawText($im, $fontRegular, 10.5 * $S, $gray, $bodyPad, $footerY, "This is a computer-generated receipt.");

// ---- Output ----
if (isset($_GET['download'])) {
    header('Content-Disposition: attachment; filename="Receipt-' . $payment['receipt_no'] . '.png"');
}
header('Content-Type: image/png');
imagepng($im);
imagedestroy($im);