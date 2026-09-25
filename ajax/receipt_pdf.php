<?php
/**
 * Generates a downloadable PDF receipt using the FPDF library.
 */
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../lib/fpdf/fpdf.php';

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

// FPDF only supports single-byte encoding natively; convert UTF-8 text to a safe format.
function pdfSafe(string $text): string
{
    // Replace the rupee symbol (not supported by core FPDF fonts) with "Rs."
    $text = str_replace('₹', 'Rs. ', $text);
    return iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $text) ?: $text;
}

class ReceiptPDF extends FPDF
{
    public string $institutionName = '';
    public string $address = '';
    public string $contact = '';

    function Header(): void
    {
        $this->SetFillColor(13, 71, 161);
        $this->Rect(0, 0, 210, 28, 'F');
        $this->SetTextColor(255, 255, 255);
        $this->SetFont('Arial', 'B', 16);
        $this->SetXY(10, 8);
        $this->Cell(0, 8, $this->institutionName, 0, 1);
        $this->SetFont('Arial', '', 9);
        $this->SetX(10);
        $this->Cell(0, 6, $this->address, 0, 1);
        $this->SetX(10);
        $this->Cell(0, 6, $this->contact, 0, 1);
        $this->SetTextColor(0, 0, 0);
        $this->Ln(12);
    }

    function Footer(): void
    {
        $this->SetY(-20);
        $this->SetFont('Arial', 'I', 8);
        $this->SetTextColor(120, 120, 120);
        $this->Cell(0, 6, 'This is a computer-generated receipt.', 0, 0, 'C');
    }
}

$pdf = new ReceiptPDF();
$pdf->institutionName = pdfSafe($institutionName);
$pdf->address = pdfSafe($settings['address'] ?? '');
$contactParts = [];
if (!empty($settings['phone'])) $contactParts[] = 'Ph: ' . $settings['phone'];
if (!empty($settings['email'])) $contactParts[] = $settings['email'];
$pdf->contact = pdfSafe(implode('  |  ', $contactParts));

$pdf->AddPage();
$pdf->SetFont('Arial', 'B', 14);
$pdf->SetTextColor(13, 71, 161);
$pdf->Cell(0, 10, 'FEE RECEIPT', 0, 1, 'C');
$pdf->Ln(2);

$pdf->SetTextColor(0, 0, 0);
$pdf->SetFont('Arial', '', 11);

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

foreach ($rows as $row) {
    $pdf->SetFont('Arial', '', 10);
    $pdf->SetTextColor(100, 100, 100);
    $pdf->Cell(35, 9, pdfSafe($row[0]), 0, 0);
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->Cell(60, 9, pdfSafe((string)$row[1]), 0, 0);

    if ($row[2] !== '') {
        $pdf->SetFont('Arial', '', 10);
        $pdf->SetTextColor(100, 100, 100);
        $pdf->Cell(35, 9, pdfSafe($row[2]), 0, 0);
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->Cell(60, 9, pdfSafe((string)$row[3]), 0, 1);
    } else {
        $pdf->Ln();
    }

    $pdf->SetDrawColor(230, 230, 230);
    $pdf->Line(10, $pdf->GetY(), 200, $pdf->GetY());
}

$pdf->Ln(8);

$boxY = $pdf->GetY();
$boxWidth = 60;

if ($isMonthly) {
    $labels = ['Monthly Fee', 'Amount Paid', 'Status'];
    $values = [
        formatCurrency((float)$payment['course_rate']),
        formatCurrency((float)$payment['amount']),
        'PAID',
    ];
    $colors = [[13, 71, 161], [46, 125, 50], [46, 125, 50]];
} else {
    $labels = ['Amount Paid Now', 'Total Paid Till Date', 'Remaining Balance'];
    $values = [
        formatCurrency((float)$payment['amount']),
        formatCurrency($balance['total_paid']),
        formatCurrency($balance['balance']),
    ];
    $colors = [[13, 71, 161], [46, 125, 50], [198, 40, 40]];
}

for ($i = 0; $i < 3; $i++) {
    $x = 10 + ($i * ($boxWidth + 5));
    $pdf->SetXY($x, $boxY);
    $pdf->SetDrawColor(220, 220, 220);
    $pdf->Rect($x, $boxY, $boxWidth, 22);
    $pdf->SetXY($x, $boxY + 3);
    $pdf->SetFont('Arial', '', 8);
    $pdf->SetTextColor(120, 120, 120);
    $pdf->Cell($boxWidth, 5, pdfSafe($labels[$i]), 0, 2, 'C');
    $pdf->SetX($x);
    $pdf->SetFont('Arial', 'B', 12);
    [$r, $g, $b] = $colors[$i];
    $pdf->SetTextColor($r, $g, $b);
    $pdf->Cell($boxWidth, 8, pdfSafe($values[$i]), 0, 0, 'C');
}

$pdf->Ln(40);
$pdf->SetTextColor(0, 0, 0);
$pdf->SetFont('Arial', '', 9);
$pdf->Cell(0, 6, '', 0, 1);
$pdf->SetX(140);
$pdf->Cell(60, 6, '_______________________', 0, 1, 'C');
$pdf->SetX(140);
$pdf->Cell(60, 6, 'Authorized Signature', 0, 1, 'C');

$pdf->Output('I', 'Receipt-' . $payment['receipt_no'] . '.pdf');