<?php

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../lib/fpdf/fpdf.php';

$id = (int)($_GET['id'] ?? 0);

if ($id <= 0) {
    die('Invalid student.');
}

/* =========================================================
   FETCH STUDENT
   ========================================================= */

$pdo = db();

$stmt = $pdo->prepare("
    SELECT 
        s.*, 
        c.name AS course_name, 
        c.fee AS course_fee, 
        c.duration AS course_duration,
        c.fee_type AS course_fee_type
    FROM students s
    JOIN courses c ON c.id = s.course_id
    WHERE s.id = ?
");

$stmt->execute([$id]);
$student = $stmt->fetch();

if (!$student) {
    die('Student not found.');
}


/* =========================================================
   SETTINGS
   ========================================================= */

$settings = getSettings();

$institutionName = $settings['institution_name'] ?? APP_NAME;
$institutionAddress = $settings['address'] ?? '';

$contactParts = [];

if (!empty($settings['phone'])) {
    $contactParts[] = 'Ph: ' . $settings['phone'];
}

if (!empty($settings['email'])) {
    $contactParts[] = $settings['email'];
}

$institutionContact = implode('  |  ', $contactParts);


/* =========================================================
   FEES
   ========================================================= */

$isMonthly = ($student['course_fee_type'] ?? 'fixed') === 'monthly';

$courseFee = (float)($student['course_fee'] ?? 0);

$feeMode = $student['fee_mode'] ?? 'default';

$discountPercent = (float)($student['discount_percent'] ?? 0);

$finalFee = (
    $feeMode !== 'default' &&
    isset($student['final_fee']) &&
    $student['final_fee'] !== null
)
    ? (float)$student['final_fee']
    : $courseFee;


/* =========================================================
   PDF SAFE TEXT
   ========================================================= */

function pdfSafe(string $text): string
{
    // FPDF's standard fonts do not support ₹.
    $text = str_replace('₹', 'Rs. ', $text);

    return iconv(
        'UTF-8',
        'ISO-8859-1//TRANSLIT',
        $text
    ) ?: $text;
}


function pdfVal($value): string
{
    if ($value === null || $value === '') {
        return '-';
    }

    /*
     * Uppercase every field value so values match the
     * all-caps style already used for labels.
     */
    return strtoupper(pdfSafe((string)$value));
}


function pdfDate($date): string
{
    if (empty($date)) {
        return '-';
    }

    $timestamp = strtotime($date);

    if (!$timestamp) {
        return '-';
    }

    return date('d-m-Y', $timestamp);
}


/* =========================================================
   PDF CLASS
   ========================================================= */

class ApplicationPDF extends FPDF
{
    public string $institutionName = '';
    public string $address = '';
    public string $contact = '';

    const LEFT = 10;
    const RIGHT = 200;
    const CONTENT_WIDTH = 190; // RIGHT - LEFT

    const BLUE_R = 13;
    const BLUE_G = 71;
    const BLUE_B = 161;

    const PAGE_BOTTOM_MARGIN = 8;

    // Slightly more generous line height & padding for a cleaner look
    const VALUE_LINE_HEIGHT = 5.5;
    const VALUE_FONT_SIZE = 12;
    const LABEL_FONT_SIZE = 11;

    const BORDER_WIDTH = 0.6;
    const BORDER_R = 20;
    const BORDER_G = 20;
    const BORDER_B = 20;
    const CELL_PAD_Y = 1.6; // more internal breathing room


    function Header(): void
    {
        $this->SetFillColor(
            self::BLUE_R,
            self::BLUE_G,
            self::BLUE_B
        );

        $this->Rect(0, 0, 210, 28, 'F');

        $this->SetTextColor(255, 255, 255);

        $nameAreaWidth = 150;

        $nameSize = 16;

        $this->SetFont('Arial', 'B', $nameSize);

        while (
            $nameSize > 11 &&
            $this->GetStringWidth($this->institutionName) > ($nameAreaWidth - 2)
        ) {
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

        // More space before each new section
        $this->Ln(3.2);

        $this->SetFont('Arial', 'B', 11);
        $this->SetTextColor(self::BLUE_R, self::BLUE_G, self::BLUE_B);

        $this->Cell(0, 5, pdfSafe(strtoupper($title)), 0, 1, 'L');

        $this->SetDrawColor(self::BORDER_R, self::BORDER_G, self::BORDER_B);
        $this->SetLineWidth(0.45);

        $this->Line(self::LEFT, $this->GetY(), self::RIGHT, $this->GetY());

        $this->Ln(1.2);   // space after the underline

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

        if ($nb > 0 && $s[$nb - 1] == "\n") {
            $nb--;
        }

        $sep = -1;
        $i = 0;
        $j = 0;
        $l = 0;
        $nl = 1;

        while ($i < $nb) {
            $c = $s[$i];

            if ($c == "\n") {
                $i++;
                $sep = -1;
                $j = $i;
                $l = 0;
                $nl++;
                continue;
            }

            if ($c == ' ') {
                $sep = $i;
            }

            $l += $cw[$c] ?? 0;

            if ($l > $wmax) {
                if ($sep == -1) {
                    if ($i == $j) {
                        $i++;
                    }
                } else {
                    $i = $sep + 1;
                }

                $sep = -1;
                $j = $i;
                $l = 0;
                $nl++;
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

            $this->MultiCell(
                $labelWidths[$i] - 2,
                self::VALUE_LINE_HEIGHT,
                pdfSafe(strtoupper($label)),
                0,
                'L'
            );

            // Value
            $this->SetXY($x + $labelWidths[$i] + 1.5, $startY + self::CELL_PAD_Y);
            $this->SetFont('Arial', 'B', self::VALUE_FONT_SIZE);
            $this->SetTextColor(20, 20, 20);

            $this->MultiCell(
                $valueWidths[$i] - 3,
                self::VALUE_LINE_HEIGHT,
                pdfSafe($value),
                0,
                'L'
            );

            $x += $labelWidths[$i] + $valueWidths[$i];
        }

        $this->SetXY(self::LEFT, $startY + $rowHeight);
        $this->SetTextColor(0, 0, 0);
    }


    function TwoFieldRow(
        string $label1,
        string $value1,
        string $label2,
        string $value2
    ): void {
        $this->Row(
            [[$label1, $value1], [$label2, $value2]],
            [36, 32],
            [55, 67]
        );
    }


    function FullField(string $label, string $value): void
    {
        $this->Row(
            [[$label, $value]],
            [36],
            [154]
        );
    }


    function WideFieldWithSideField(
        string $mainLabel,
        string $mainValue,
        string $sideLabel,
        string $sideValue
    ): void {
        $this->Row(
            [[$mainLabel, $mainValue], [$sideLabel, $sideValue]],
            [36, 22],
            [98, 34]
        );
    }


    function FeeBox(
        float $x,
        float $y,
        float $width,
        float $height,
        string $label,
        string $value,
        bool $highlight = false
    ): void {
        $this->SetFillColor(252, 252, 252);
        $this->SetDrawColor(210, 210, 210);
        $this->SetLineWidth(0.3);

        $this->Rect($x, $y, $width, $height, 'DF');

        $this->SetXY($x, $y + 2.5);
        $this->SetFont('Arial', 'B', 9);
        $this->SetTextColor(100, 100, 100);

        $this->Cell($width, 5, pdfSafe($label), 0, 1, 'C');

        $this->SetXY($x, $y + 8.5);
        $this->SetFont('Arial', 'B', 14);

        if ($highlight) {
            $this->SetTextColor(46, 125, 50);
        } else {
            $this->SetTextColor(self::BLUE_R, self::BLUE_G, self::BLUE_B);
        }

        $this->Cell($width, 7, pdfSafe($value), 0, 0, 'C');

        $this->SetTextColor(0, 0, 0);
    }


    function TermsAndConditions(array $terms): void
    {
        $this->CheckSpace(6 + count($terms) * 5.2);

        // Larger heading
        $this->SetFont('Arial', 'B', 11);
        $this->SetTextColor(30, 30, 30);

        $this->Cell(0, 5.5, 'TERMS & CONDITIONS', 0, 1, 'L');

        // Larger content
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


    function SignatureArea(string $date, string $place): void
    {
        $this->CheckSpace(22);

        
        $startY = $this->GetY() + 9;

        $this->SetXY(10, $startY);

        $this->SetFont('Arial', 'B', 9.5);
        $this->SetTextColor(45, 45, 45);

        $this->Cell(90, 5, 'DATE : ' . pdfSafe($date), 0, 1, 'L');

        $this->SetX(10);
        $this->Cell(90, 5, 'PLACE : ' . pdfSafe($place), 0, 1, 'L');

        $signatureX = 130;

        // Bold underline
        $this->SetXY($signatureX, $startY + 6);
        $this->SetFont('Arial', 'B', 10);
        $this->Cell(65, 5, '____________________________', 0, 1, 'C');

        // Bold signature label
        $this->SetX($signatureX);
        $this->SetFont('Arial', 'B', 9);
        $this->Cell(65, 5.5, 'SIGNATURE OF APPLICANT', 0, 1, 'C');
    }


    function Footer(): void
    {
        // empty
    }
}


/* =========================================================
   CREATE PDF
   ========================================================= */

$pdf = new ApplicationPDF('P', 'mm', 'A4');

$pdf->SetMargins(10, 10, 10);
$pdf->SetAutoPageBreak(false);

$pdf->institutionName = pdfSafe($institutionName);
$pdf->address = pdfSafe($institutionAddress);
$pdf->contact = pdfSafe($institutionContact);

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

$pdf->TwoFieldRow(
    'Full Name',
    pdfVal($student['name'] ?? null),
    'DOB',
    pdfDate($student['dob'] ?? null)
);

$pdf->TwoFieldRow(
    "Father's Name",
    pdfVal($student['father_name'] ?? null),
    'Gender',
    pdfVal($student['gender'] ?? null)
);

$pdf->TwoFieldRow(
    'Admission Date',
    pdfDate($student['admission_date'] ?? null),
    'Present Address',
    pdfVal($student['address'] ?? null)
);


/* =========================================================
   CONTACT DETAILS
   ========================================================= */

$pdf->SectionTitle('Contact Details');

$pdf->TwoFieldRow(
    'Mobile Number',
    pdfVal($student['mobile'] ?? null),
    'WhatsApp Number',
    pdfVal($student['whatsapp_number'] ?? null)
);

$pdf->FullField(
    'Parent Contact',
    pdfVal($student['parent_contact'] ?? null)
);


/* =========================================================
   QUALIFICATION
   ========================================================= */

$pdf->SectionTitle('Qualification');

$pdf->TwoFieldRow(
    'Qualification',
    pdfVal($student['qualification'] ?? null),
    'Year',
    pdfVal($student['qualification_year'] ?? null)
);

$pdf->Row(
    [
        ['School / College', pdfVal($student['school_college'] ?? null)],
    ],
    [36],          
    [154]          
);
/* =========================================================
   ADDITIONAL INFORMATION
   ========================================================= */

$pdf->SectionTitle('Additional Information');

$pdf->FullField(
    'Reference',
    pdfVal($student['reference_source'] ?? null)
);


/* =========================================================
   COURSE & FEES
   ========================================================= */

$pdf->SectionTitle('Course & Fees');

$pdf->TwoFieldRow(
    'Course',
    pdfVal($student['course_name'] ?? null),
    'Timing',
    pdfVal($student['timing'] ?? null)
);

if ($isMonthly) {

    $pdf->TwoFieldRow(
        'Duration',
        'ONGOING',
        'Billing Type',
        'MONTHLY'
    );

    $pdf->TwoFieldRow(
        'Monthly Fee',
        formatCurrency($courseFee),
        'Billing Start Date',
        pdfDate($student['admission_date'] ?? null)
    );

} else {

    $discountApplied = ($feeMode === 'discount' || $feeMode === 'custom');

    if ($discountApplied) {

        $pdf->TwoFieldRow(
            'Duration',
            pdfVal($student['course_duration'] ?? null),
            'Discount Applied',
            'YES'
        );

        $pdf->TwoFieldRow(
            'Default Course Fee',
            formatCurrency($courseFee),
            'Final Course Fee',
            formatCurrency($finalFee)
        );

    } else {

        $pdf->TwoFieldRow(
            'Duration',
            pdfVal($student['course_duration'] ?? null),
            'Course Fee',
            formatCurrency($courseFee)
        );
    }
}


/* =========================================================
   TERMS & CONDITIONS
   ========================================================= */

$pdf->Ln(4);   // clear space before Terms

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

$pdf->Cell(
    0,
    5,
    'I AGREE TO ABIDE BY THE RULES AND REGULATIONS OF THIS CENTRE AND SCHEME',
    0,
    1,
    'C'
);


/* =========================================================
   SIGNATURE
   ========================================================= */

$pdf->SignatureArea(date('d-m-Y'), $institutionAddress);


/* =========================================================
   OUTPUT
   ========================================================= */

$safeName = preg_replace('/[^a-zA-Z0-9]+/', '_', $student['name'] ?? 'Student');

$pdf->Output('I', 'Application-' . $safeName . '.pdf');