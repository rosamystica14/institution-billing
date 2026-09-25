<?php
/**
 * Blank / Empty Student Application Form
 * Same professional style as the filled version
 * No database required
 */

require_once __DIR__ . '/lib/fpdf/fpdf.php';   // adjust path if needed

/* =========================================================
   INSTITUTION DETAILS (edit these)
   ========================================================= */

$institutionName    = 'YOUR INSTITUTION NAME';
$institutionAddress = 'Your Full Address, City, State - PIN';
$institutionContact = 'Ph: 00000 00000  |  email@example.com';


/* =========================================================
   PDF SAFE TEXT
   ========================================================= */

function pdfSafe(string $text): string
{
    $text = str_replace('₹', 'Rs. ', $text);
    return iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $text) ?: $text;
}


/* =========================================================
   PDF CLASS (same style as original)
   ========================================================= */

class ApplicationPDF extends FPDF
{
    public string $institutionName = '';
    public string $address = '';
    public string $contact = '';

    const LEFT = 10;
    const RIGHT = 200;
    const CONTENT_WIDTH = 190;

    const BLUE_R = 13;
    const BLUE_G = 71;
    const BLUE_B = 161;

    const PAGE_BOTTOM_MARGIN = 8;

    const VALUE_LINE_HEIGHT = 5.5;
    const VALUE_FONT_SIZE   = 12;
    const LABEL_FONT_SIZE   = 11;

    const BORDER_WIDTH = 0.6;
    const BORDER_R = 20;
    const BORDER_G = 20;
    const BORDER_B = 20;
    const CELL_PAD_Y = 1.6;

    function Header(): void
    {
        $this->SetFillColor(self::BLUE_R, self::BLUE_G, self::BLUE_B);
        $this->Rect(0, 0, 210, 28, 'F');

        $this->SetTextColor(255, 255, 255);

        $nameAreaWidth = 150;
        $nameSize = 16;
        $this->SetFont('Arial', 'B', $nameSize);

        while ($nameSize > 11 && $this->GetStringWidth($this->institutionName) > ($nameAreaWidth - 2)) {
            $nameSize -= 0.5;
            $this->SetFont('Arial', 'B', $nameSize);
        }

        $this->SetXY(10, 5);
        $this->Cell($nameAreaWidth, 7, $this->institutionName, 0, 1, 'L');

        $this->SetFont('Arial', 'B', 9.5);
        $this->SetXY(10, 13);
        $this->Cell(150, 5, $this->address, 0, 1, 'L');

        $this->SetXY(10, 19);
        $this->Cell(150, 5, $this->contact, 0, 1, 'L');

        // Seals (optional – keep if files exist)
        $sealDir = __DIR__ . '/../assets/img/';
        if (file_exists($sealDir . 'cert-tceds.png')) {
            $this->Image($sealDir . 'cert-tceds.png', 164, 4, 18, 18);
        }
        if (file_exists($sealDir . 'cert-bmqr.png')) {
            $this->Image($sealDir . 'cert-bmqr.png', 185, 4, 18, 18);
        }

        $this->SetTextColor(0, 0, 0);
        $this->SetY(30);
    }

    function PhotoPlaceholder(float $y): void
    {
        $x = 173;
        $w = 25;
        $h = 28;

        $this->SetFillColor(250, 250, 250);
        $this->SetDrawColor(150, 150, 150);
        $this->SetLineWidth(0.3);
        $this->Rect($x, $y, $w, $h, 'DF');

        $this->SetTextColor(120, 120, 120);
        $this->SetFont('Arial', '', 7.5);
        $this->SetXY($x, $y + 7);
        $this->MultiCell($w, 4, "AFFIX\nPASSPORT\nSIZE\nPHOTO", 0, 'C');
        $this->SetTextColor(0, 0, 0);
    }

    function CheckSpace(float $neededHeight): void
    {
        if ($this->GetY() + $neededHeight > (297 - self::PAGE_BOTTOM_MARGIN)) {
            $this->AddPage();
        }
    }

    function SectionTitle(string $title): void
    {
        $this->CheckSpace(11);
        $this->Ln(3.2);

        $this->SetFont('Arial', 'B', 11);
        $this->SetTextColor(self::BLUE_R, self::BLUE_G, self::BLUE_B);
        $this->Cell(0, 5, pdfSafe(strtoupper($title)), 0, 1, 'L');

        $this->SetDrawColor(self::BORDER_R, self::BORDER_G, self::BORDER_B);
        $this->SetLineWidth(0.45);
        $this->Line(self::LEFT, $this->GetY(), self::RIGHT, $this->GetY());
        $this->Ln(1.2);
        $this->SetTextColor(0, 0, 0);
    }

    function NbLines(float $width, string $text): int
    {
        $cw = &$this->CurrentFont['cw'];
        if ($width == 0) {
            $width = $this->w - $this->rMargin - $this->x;
        }
        $wmax = ($width - 2 * $this->cMargin) * 1000 / $this->FontSize;
        $s = str_replace("\r", '', (string)$text);
        $nb = strlen($s);
        if ($nb > 0 && $s[$nb - 1] == "\n") $nb--;
        $sep = -1; $i = 0; $j = 0; $l = 0; $nl = 1;

        while ($i < $nb) {
            $c = $s[$i];
            if ($c == "\n") {
                $i++; $sep = -1; $j = $i; $l = 0; $nl++; continue;
            }
            if ($c == ' ') $sep = $i;
            $l += $cw[$c] ?? 0;
            if ($l > $wmax) {
                if ($sep == -1) {
                    if ($i == $j) $i++;
                } else {
                    $i = $sep + 1;
                }
                $sep = -1; $j = $i; $l = 0; $nl++;
            } else {
                $i++;
            }
        }
        return $nl;
    }

    function Row(array $fields, array $labelWidths, array $valueWidths): void
    {
        $maxLines = 1;
        foreach ($fields as $i => [$label, $value]) {
            if ($value !== '') {
                $this->SetFont('Arial', 'B', self::VALUE_FONT_SIZE);
                $maxLines = max($maxLines, $this->NbLines($valueWidths[$i] - 3, $value));
            }
            if ($label !== '') {
                $this->SetFont('Arial', 'B', self::LABEL_FONT_SIZE);
                $maxLines = max($maxLines, $this->NbLines($labelWidths[$i] - 2, strtoupper($label)));
            }
        }

        $rowHeight = ($maxLines * self::VALUE_LINE_HEIGHT) + (self::CELL_PAD_Y * 2);
        $this->CheckSpace($rowHeight + 1.5);

        $startY = $this->GetY();
        $x = self::LEFT;

        $this->SetDrawColor(self::BORDER_R, self::BORDER_G, self::BORDER_B);
        $this->SetLineWidth(self::BORDER_WIDTH);

        foreach ($fields as $i => [$label, $value]) {
            if ($label === '' && $value === '') {
                $x += $labelWidths[$i] + $valueWidths[$i];
                continue;
            }
            $this->Rect($x, $startY, $labelWidths[$i], $rowHeight, 'D');
            $this->Rect($x + $labelWidths[$i], $startY, $valueWidths[$i], $rowHeight, 'D');
            $x += $labelWidths[$i] + $valueWidths[$i];
        }

        $x = self::LEFT;
        foreach ($fields as $i => [$label, $value]) {
            if ($label === '' && $value === '') {
                $x += $labelWidths[$i] + $valueWidths[$i];
                continue;
            }

            // Label
            $this->SetXY($x + 1.5, $startY + self::CELL_PAD_Y);
            $this->SetFont('Arial', 'B', self::LABEL_FONT_SIZE);
            $this->SetTextColor(0, 0, 0);
            $this->MultiCell($labelWidths[$i] - 2, self::VALUE_LINE_HEIGHT, pdfSafe(strtoupper($label)), 0, 'L');

            // Value (empty for blank form)
            $this->SetXY($x + $labelWidths[$i] + 1.5, $startY + self::CELL_PAD_Y);
            $this->SetFont('Arial', 'B', self::VALUE_FONT_SIZE);
            $this->SetTextColor(20, 20, 20);
            $this->MultiCell($valueWidths[$i] - 3, self::VALUE_LINE_HEIGHT, pdfSafe($value), 0, 'L');

            $x += $labelWidths[$i] + $valueWidths[$i];
        }

        $this->SetXY(self::LEFT, $startY + $rowHeight);
        $this->SetTextColor(0, 0, 0);
    }

    function TwoFieldRow(string $label1, string $value1, string $label2, string $value2): void
    {
        $this->Row(
            [[$label1, $value1], [$label2, $value2]],
            [36, 32],
            [55, 67]
        );
    }

    function FullField(string $label, string $value): void
    {
        $this->Row([[$label, $value]], [36], [154]);
    }

    function TermsAndConditions(array $terms): void
    {
        $this->CheckSpace(6 + count($terms) * 5.2);

        $this->SetFont('Arial', 'B', 11);
        $this->SetTextColor(30, 30, 30);
        $this->Cell(0, 5.5, 'TERMS & CONDITIONS', 0, 1, 'L');

        $this->SetFont('Arial', '', 9.5);
        $this->SetTextColor(40, 40, 40);

        foreach ($terms as $term) {
            $this->CheckSpace(5.2);
            $this->SetX(12);
            $this->Cell(4, 4.5, '-', 0, 0, 'L');
            $this->MultiCell(176, 4.5, pdfSafe($term), 0, 'L');
        }
        $this->SetTextColor(0, 0, 0);
    }

    function SignatureArea(): void
    {
        $this->CheckSpace(22);
        $startY = $this->GetY() + 9;

        $this->SetXY(10, $startY);
        $this->SetFont('Arial', 'B', 9.5);
        $this->SetTextColor(45, 45, 45);

        $this->Cell(90, 5, 'DATE : ________________', 0, 1, 'L');
        $this->SetX(10);
        $this->Cell(90, 5, 'PLACE : ________________', 0, 1, 'L');

        $signatureX = 130;
        $this->SetXY($signatureX, $startY + 6);
        $this->SetFont('Arial', 'B', 10);
        $this->Cell(65, 5, '____________________________', 0, 1, 'C');

        $this->SetX($signatureX);
        $this->SetFont('Arial', 'B', 9);
        $this->Cell(65, 5.5, 'SIGNATURE OF APPLICANT', 0, 1, 'C');
    }

    function Footer(): void {}
}


/* =========================================================
   CREATE PDF
   ========================================================= */

$pdf = new ApplicationPDF('P', 'mm', 'A4');
$pdf->SetMargins(10, 10, 10);
$pdf->SetAutoPageBreak(false);

$pdf->institutionName = pdfSafe($institutionName);
$pdf->address         = pdfSafe($institutionAddress);
$pdf->contact         = pdfSafe($institutionContact);

$pdf->AddPage();


/* =========================================================
   TOP AREA
   ========================================================= */

$photoY = $pdf->GetY();
$pdf->PhotoPlaceholder($photoY);

$pdf->SetXY(10, $photoY);
$pdf->SetFont('Arial', 'B', 16);
$pdf->SetTextColor(13, 71, 161);
$pdf->Cell(190, 8, 'STUDENT APPLICATION FORM', 0, 1, 'C');
$pdf->SetTextColor(0, 0, 0);


/* =========================================================
   APPLICANT DETAILS
   ========================================================= */

$pdf->SectionTitle('Applicant Details');

$pdf->TwoFieldRow('Full Name', '', 'Date of Birth', '');
$pdf->TwoFieldRow("Father's Name", '', 'Gender', '');

$pdf->Row(
    [
        ['Admission Date', ''],
        ['Address', ''],
    ],
    [38, 26],
    [34, 92]
);


/* =========================================================
   CONTACT DETAILS
   ========================================================= */

$pdf->SectionTitle('Contact Details');

$pdf->TwoFieldRow('Mobile Number', '', 'WhatsApp No', '');
$pdf->FullField('Parent Contact', '');


/* =========================================================
   QUALIFICATION
   ========================================================= */

$pdf->SectionTitle('Qualification');

$pdf->Row(
    [
        ['Qualification', ''],
        ['Year', ''],
    ],
    [42, 28],
    [49, 71]
);

$pdf->FullField('School / College', '');


/* =========================================================
   ADDITIONAL INFORMATION
   ========================================================= */

$pdf->SectionTitle('Additional Information');
$pdf->FullField('Reference', '');


/* =========================================================
   COURSE & FEES
   ========================================================= */

$pdf->SectionTitle('Course & Fees');

$pdf->TwoFieldRow('Course', '', 'Timing', '');
$pdf->TwoFieldRow('Duration', '', 'Course Fee', '');


/* =========================================================
   TERMS & CONDITIONS
   ========================================================= */

$pdf->Ln(4);

$terms = [
    'Regular attendance and task completion is required for course completion.',
    'Course joined person will receive CC and certificates after full payment.',
    'In case of discontinuation, course fees will not be refundable.',
    'Course joined person who misbehaves or acts against the terms may be considered as dropped out and fees will not be refunded.'
];

$pdf->TermsAndConditions($terms);


/* =========================================================
   DECLARATION
   ========================================================= */

$pdf->CheckSpace(7);
$pdf->Ln(1.5);

$pdf->SetFont('Arial', 'B', 9.5);
$pdf->SetTextColor(20, 20, 20);
$pdf->Cell(0, 5, 'I AGREE TO ABIDE BY THE RULES AND REGULATIONS OF THIS CENTRE AND SCHEME', 0, 1, 'C');


/* =========================================================
   SIGNATURE
   ========================================================= */

$pdf->SignatureArea();


/* =========================================================
   OUTPUT
   ========================================================= */

$pdf->Output('I', 'Blank-Student-Application-Form.pdf');