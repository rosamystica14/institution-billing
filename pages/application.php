<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    die('Invalid student.');
}

$pdo = db();
$stmt = $pdo->prepare("
    SELECT s.*, c.name as course_name, c.fee as course_fee, c.duration as course_duration, c.fee_type as course_fee_type
    FROM students s
    JOIN courses c ON c.id = s.course_id
    WHERE s.id = ?
");
$stmt->execute([$id]);
$student = $stmt->fetch();

if (!$student) {
    die('Student not found.');
}

$settings = getSettings();
$institutionName = $settings['institution_name'] ?? APP_NAME;

$isMonthly = ($student['course_fee_type'] ?? 'fixed') === 'monthly';

$courseFee = (float)$student['course_fee'];
$feeMode = $student['fee_mode'] ?? 'default';
$discountPercent = (float)($student['discount_percent'] ?? 0);
$finalFee = ($feeMode !== 'default' && $student['final_fee'] !== null)
    ? (float)$student['final_fee']
    : $courseFee;

function val(?string $v): string
{
    return $v !== null && $v !== '' ? e($v) : '-';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Application Form - <?= e($student['name']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="<?= BASE_URL ?>/assets/css/style.css" rel="stylesheet">
    <style>
        body { background: #eef2f7; }
        .receipt-box {
            max-width: 800px;
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
        .receipt-table { table-layout: fixed; width: 100%; }
        .receipt-table col.label-col { width: 18%; }
        .receipt-table col.value-col { width: 32%; }
        .receipt-table td { padding: 8px 8px; word-wrap: break-word; }
        .receipt-table td.text-muted { color: #5a6472 !important; font-weight: 600 !important; }
        .receipt-table tr:not(:last-child) td { border-bottom: 1px solid #eef1f5; }
        .section-label {
            font-size: 0.8rem;
            font-weight: 700;
            color: #0d47a1;
            text-transform: uppercase;
            border-bottom: 1px solid #eef1f5;
            padding-bottom: 6px;
            margin: 24px 0 6px 0;
        }
        .student-photo {
            width: 90px;
            height: 100px;
            object-fit: cover;
            border-radius: 6px;
            border: 2px solid rgba(255,255,255,0.5);
        }
        .cert-seal {
            height: 48px;
            width: auto;
            background: #fff;
            border-radius: 4px;
            padding: 2px;
        }

        @media print {
            @page { margin: 10mm; size: A4; }
            html, body { font-size: 13.5px !important; background: #fff; }
            .no-print { display: none !important; }
            .receipt-box { box-shadow: none !important; margin: 0 !important; max-width: 100% !important; border-radius: 0 !important; }
            .receipt-header { padding: 16px 22px !important; }
            .receipt-header h4 { font-size: 1.3rem !important; }
            .receipt-header .institution-address,
            .receipt-header .institution-contact { font-weight: 700 !important; }
            .receipt-header .small { font-size: 0.82rem !important; line-height: 1.35 !important; }
            .cert-seal { height: 34px !important; }
            .receipt-body { padding: 16px 26px !important; }
            .receipt-body h5 { margin-bottom: 14px !important; font-size: 1.1rem !important; }
            table.receipt-table { margin-bottom: 6px !important; }
            .receipt-table td { padding: 6px 8px !important; font-size: 0.88rem !important; line-height: 1.3 !important; }
            .section-label { margin: 14px 0 4px 0 !important; padding-bottom: 4px !important; font-size: 0.82rem !important; }
            .row.g-3.mb-4 { margin-bottom: 12px !important; }
            .row.g-3.mb-4 .border { padding: 10px !important; }
            .row.g-3.mb-4 .text-muted.small { font-size: 0.75rem !important; }
            .row.g-3.mb-4 .fs-5 { font-size: 1.05rem !important; }
            ul.ps-3.mb-3 { margin-bottom: 8px !important; font-size: 0.8rem !important; padding-left: 18px !important; }
            ul.ps-3.mb-3 li { margin-bottom: 3px !important; line-height: 1.4 !important; }
            p.text-center.fw-bold { margin-bottom: 14px !important; font-size: 0.9rem !important; }
            .d-flex.justify-content-between.align-items-end { margin-top: 20px !important; padding-top: 0 !important; font-size: 0.85rem !important; }
            .d-flex.justify-content-between.align-items-end div[style*="border-top"] { width: 180px !important; margin-left: auto; }
            .text-muted.small.mt-4 { margin-top: 12px !important; font-size: 0.72rem !important; }
            .student-photo { width: 70px !important; height: 78px !important; }
        }
    </style>
</head>
<body>

<div class="container no-print py-3 text-center">
    <button class="btn btn-primary" onclick="window.print()"><i class="bi bi-printer-fill"></i> Print Application</button>
    <a href="<?= BASE_URL ?>/ajax/application_pdf.php?id=<?= (int)$id ?>" class="btn btn-success">
    <i class="bi bi-file-earmark-pdf-fill"></i> Download PDF
    </a>
    <a href="<?= BASE_URL ?>/index.php?page=students" class="btn btn-outline-secondary">
        <i class="bi bi-x-lg"></i> Close
    </a>
</div>

<div class="receipt-box receipt-page">
    <div class="receipt-header d-flex align-items-center justify-content-between gap-3">
        <div class="d-flex align-items-center gap-3">
            <?php if (!empty($settings['logo'])): ?>
                <img src="<?= BASE_URL ?>/assets/uploads/logo/<?= e($settings['logo']) ?>" alt="Logo" height="56" class="rounded bg-white p-1">
            <?php else: ?>
                <i class="bi bi-mortarboard-fill" style="font-size: 2.6rem;"></i>
            <?php endif; ?>
            <div>
                <h4 class="mb-0 fw-bold institution-name"><?= e($institutionName) ?></h4>
<div class="small fw-bold institution-address"><?= e($settings['address'] ?? '') ?></div>
<div class="small fw-bold institution-contact">
                    <?= !empty($settings['phone']) ? 'Ph: ' . e($settings['phone']) : '' ?>
                    <?= !empty($settings['email']) ? ' | ' . e($settings['email']) : '' ?>
                </div>
            </div>
            <div class="d-flex align-items-center gap-2 ms-2 cert-seals">
                <img src="<?= BASE_URL ?>/assets/img/cert-tceds.png" alt="TCEDS Certification" class="cert-seal">
                <img src="<?= BASE_URL ?>/assets/img/cert-bmqr.png" alt="ISO 9001 Certified" class="cert-seal">
            </div>
        </div>
        <?php if (!empty($student['photo'])): ?>
            <img src="<?= BASE_URL ?>/assets/uploads/photos/<?= e($student['photo']) ?>" class="student-photo" alt="Photo">
        <?php endif; ?>
    </div>

    <div class="receipt-body">
        <h5 class="fw-bold text-primary text-center mb-4">STUDENT APPLICATION FORM</h5>

        <div class="section-label">Applicant Details</div>
        <table class="table receipt-table mb-2">
            <colgroup><col class="label-col"><col class="value-col"><col class="label-col"><col class="value-col"></colgroup>
            <tr>
                <td class="text-muted">Full Name</td><td class="fw-bold"><?= val($student['name']) ?></td>
                <td class="text-muted">Father's / Guardian's Name</td><td class="fw-bold"><?= val($student['father_name']) ?></td>
            </tr>
            <tr>
                <td class="text-muted">Gender</td><td class="fw-bold"><?= val($student['gender']) ?></td>
                <td class="text-muted">Date of Birth</td><td class="fw-bold"><?= $student['dob'] ? date('d-m-Y', strtotime($student['dob'])) : '-' ?></td>
            </tr>
            <tr>
                <td class="text-muted">Admission Date</td><td class="fw-bold"><?= date('d-m-Y', strtotime($student['admission_date'])) ?></td>
                <td class="text-muted">Present Address</td><td class="fw-bold"><?= val($student['address']) ?></td>
            </tr>
        </table>

        <div class="section-label">Contact Details</div>
        <table class="table receipt-table mb-2">
            <colgroup><col class="label-col"><col class="value-col"><col class="label-col"><col class="value-col"></colgroup>
            <tr>
                <td class="text-muted">Mobile Number</td><td class="fw-bold"><?= val($student['mobile']) ?></td>
                <td class="text-muted">WhatsApp Number</td><td class="fw-bold"><?= val($student['whatsapp_number']) ?></td>
            </tr>
            <tr>
                <td class="text-muted">Parent Contact No</td><td class="fw-bold"><?= val($student['parent_contact']) ?></td>
                <td class="text-muted"></td><td></td>
            </tr>
        </table>

        <div class="section-label">Qualification</div>
        <table class="table receipt-table mb-2">
            <colgroup><col class="label-col"><col class="value-col"><col class="label-col"><col class="value-col"></colgroup>
            <tr>
                <td class="text-muted">Qualification</td><td class="fw-bold"><?= val($student['qualification']) ?></td>
                <td class="text-muted">School / College Name</td><td class="fw-bold"><?= val($student['school_college']) ?></td>
            </tr>
            <tr>
                <td class="text-muted">Year</td><td class="fw-bold"><?= val($student['qualification_year']) ?></td>
                <td class="text-muted">Reference</td><td class="fw-bold"><?= val($student['reference_source']) ?></td>
            </tr>
        </table>

        <div class="section-label">Course & Fees</div>
        <table class="table receipt-table mb-4">
            <colgroup><col class="label-col"><col class="value-col"><col class="label-col"><col class="value-col"></colgroup>
            <tr>
                <td class="text-muted">Course</td><td class="fw-bold"><?= val($student['course_name']) ?></td>
                <td class="text-muted">Timing</td><td class="fw-bold"><?= val($student['timing']) ?></td>
            </tr>
            <?php if ($isMonthly): ?>
            <tr>
                <td class="text-muted">Duration</td><td class="fw-bold">ONGOING</td>
                <td class="text-muted">Billing Type</td><td class="fw-bold">MONTHLY</td>
            </tr>
            <?php else: ?>
            <tr>
                <td class="text-muted">Duration</td><td class="fw-bold"><?= val($student['course_duration']) ?></td>
                <td class="text-muted">Discount Applied</td><td class="fw-bold"><?= $feeMode === 'discount' ? $discountPercent . '%' : 'No' ?></td>
            </tr>
            <?php endif; ?>
        </table>

        <div class="row g-3 mb-4">
            <?php if ($isMonthly): ?>
            <div class="col-6">
                <div class="border rounded p-3 text-center">
                    <div class="text-muted small">Monthly Fee</div>
                    <div class="fs-5 fw-bold text-success"><?= formatCurrency($courseFee) ?></div>
                </div>
            </div>
            <div class="col-6">
                <div class="border rounded p-3 text-center">
                    <div class="text-muted small">Billing Start Date</div>
                    <div class="fs-5 fw-bold text-primary"><?= date('d-m-Y', strtotime($student['admission_date'])) ?></div>
                </div>
            </div>
            <?php else: ?>
            <div class="col-6">
                <div class="border rounded p-3 text-center">
                    <div class="text-muted small">Default Course Fee</div>
                    <div class="fs-5 fw-bold text-primary"><?= formatCurrency($courseFee) ?></div>
                </div>
            </div>
            <div class="col-6">
                <div class="border rounded p-3 text-center">
                    <div class="text-muted small">Final Course Fee</div>
                    <div class="fs-5 fw-bold text-success"><?= formatCurrency($finalFee) ?></div>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <div class="section-label">Terms and Conditions</div>
        <ul class="ps-3 mb-3" style="font-size: 0.85rem;">
            <li>REGULAR ATTENDANCE AND TASK COMPLETION IS MUST FOR COURSE COMPLETION.</li>
            <li>COURSE JOINED PERSON WILL RECEIVE CC AND CERTIFICATES AFTER FULL PAYMENT.</li>
            <li>IN CASE OF DISCONTINUE YOUR FEES WILL NOT BE REFUNDABLE IN ANY COST.</li>
            <li>COURSE JOINED PERSON MISBEHAVE OR ACT AGAINST TERMS MAY BE CONSIDERED AS DROP OUT AND NOT REFUNDED FEES.</li>
        </ul>
        <p class="text-center fw-bold mb-4" style="font-size: 0.9rem;">I OBIDE RULES AND REGULATIONS OF THIS CENTER AND SCHEME</p>

        <div class="d-flex justify-content-between align-items-end mt-5 pt-3">
            <div style="font-size: 0.9rem;">
                <div>(DATE): <?= date('d-m-y') ?></div>
                <div>(PLACE) : <?= e($settings['address'] ?? '') ?></div>
            </div>
            <div class="text-center">
                <div style="border-top: 1px solid #333; width: 220px;"></div>
                <div class="small text-muted">(SIGNATURE OF APPLICANT)</div>
            </div>
        </div>

    </div>
</div>

</body>
</html>