<?php
require_once __DIR__ . '/../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, 'Invalid request method.', [], 405);
}

try {
    $missing = validateRequired($_POST, ['id', 'name', 'mobile', 'whatsapp_number', 'course_id', 'admission_date']);
    if (!empty($missing)) {
        jsonResponse(false, 'Missing required fields: ' . implode(', ', $missing), [], 422);
    }

    $id = (int)$_POST['id'];
    $name = sanitize($_POST['name']);
    $fatherName = sanitize($_POST['father_name'] ?? '');
    $mobile = sanitize($_POST['mobile']);
    $whatsapp = sanitize($_POST['whatsapp_number']);
    $parentContact = sanitize($_POST['parent_contact'] ?? '');
    $gender = sanitize($_POST['gender'] ?? '');
    $dob = sanitize($_POST['dob'] ?? '');
    $courseId = (int)$_POST['course_id'];
    $admissionDate = sanitize($_POST['admission_date']);
    $address = sanitize($_POST['address'] ?? '');
    $qualification = sanitize($_POST['qualification'] ?? '');
    $schoolCollege = sanitize($_POST['school_college'] ?? '');
    $qualificationYear = sanitize($_POST['qualification_year'] ?? '');
    $timing = sanitize($_POST['timing'] ?? '');
    $referenceSource = sanitize($_POST['reference_source'] ?? '');

    // ---- Name fields: letters and spaces only ----
    if (!preg_match('/^[A-Za-z\s]+$/', $name)) {
        jsonResponse(false, 'Full name can only contain letters and spaces.', [], 422);
    }
    if ($fatherName !== '' && !preg_match('/^[A-Za-z\s]+$/', $fatherName)) {
        jsonResponse(false, "Father's/Guardian's name can only contain letters and spaces.", [], 422);
    }

    // ---- Mobile / WhatsApp / Parent contact: exactly 10 digits ----
    if (!preg_match('/^[0-9]{10}$/', $mobile)) {
        jsonResponse(false, 'Please enter a valid 10-digit mobile number.', [], 422);
    }
    if (!preg_match('/^[0-9]{10}$/', $whatsapp)) {
        jsonResponse(false, 'Please enter a valid 10-digit WhatsApp number.', [], 422);
    }
    if ($parentContact !== '' && !preg_match('/^[0-9]{10}$/', $parentContact)) {
        jsonResponse(false, 'Please enter a valid 10-digit parent contact number.', [], 422);
    }

    // ---- Qualification: letters and spaces only ----
    if ($qualification !== '' && !preg_match('/^[A-Za-z\s.]+$/', $qualification)) {
        jsonResponse(false, 'Qualification can only contain letters and spaces.', [], 422);
    }

    // ---- Qualification year: exactly 4 digits ----
    if ($qualificationYear !== '' && !preg_match('/^[0-9]{4}$/', $qualificationYear)) {
        jsonResponse(false, 'Qualification year must be exactly 4 digits (e.g. 2023).', [], 422);
    }

    // ---- Fee mode: discount / custom amount / default (mutually exclusive) ----
    // NOTE: these are only meaningful for fixed-fee courses. Whether they
    // actually apply is re-checked below once we know the course's fee_type.
    $discountEnabled = isset($_POST['discount_enabled']) && $_POST['discount_enabled'] === '1';
    $customEnabled = isset($_POST['custom_amount_enabled']) && $_POST['custom_amount_enabled'] === '1';

    if ($discountEnabled && $customEnabled) {
        jsonResponse(false, 'Choose either Apply Discount or Custom Amount, not both.', [], 422);
    }

    $discountPercent = $discountEnabled ? (float)($_POST['discount_percent'] ?? 0) : 0.0;
    if ($discountEnabled && ($discountPercent < 0 || $discountPercent > 100)) {
        jsonResponse(false, 'Discount percent must be between 0 and 100.', [], 422);
    }

    $customAmount = null;
    if ($customEnabled) {
        $customAmount = (float)($_POST['custom_amount'] ?? 0);
        if ($customAmount <= 0) {
            jsonResponse(false, 'Custom amount must be greater than 0.', [], 422);
        }
    }

    $pdo = db();

    $existing = $pdo->prepare("SELECT * FROM students WHERE id = ?");
    $existing->execute([$id]);
    $studentRow = $existing->fetch();
    if (!$studentRow) {
        jsonResponse(false, 'Student not found.', [], 404);
    }

    $courseCheck = $pdo->prepare("SELECT id, fee, fee_type FROM courses WHERE id = ?");
    $courseCheck->execute([$courseId]);
    $course = $courseCheck->fetch();
    if (!$course) {
        jsonResponse(false, 'Selected course does not exist.', [], 422);
    }

    $courseFeeType = $course['fee_type'] ?? 'fixed';

    if ($courseFeeType === 'monthly') {
        // Monthly (Typewriting-style) courses don't support discount/custom
        // fee overrides. Force default regardless of what the client sent,
        // in case the fixed-fee section was tampered with or bypassed.
        $feeMode = 'default';
        $discountPercent = 0.0;
        $customAmount = null;
    } elseif ($customEnabled) {
        $feeMode = 'custom';
    } elseif ($discountEnabled) {
        $feeMode = 'discount';
    } else {
        $feeMode = 'default';
    }
    $finalFee = computeFinalFee((float)$course['fee'], $feeMode, $discountPercent, $customAmount);

    $photo = $studentRow['photo'];
    if (!empty($_FILES['photo']['name'])) {
        $uploaded = handlePhotoUpload($_FILES['photo']);
        if ($uploaded !== null) {
            // remove old photo file if it exists
            if (!empty($photo) && file_exists(UPLOAD_PHOTO_PATH . $photo)) {
                @unlink(UPLOAD_PHOTO_PATH . $photo);
            }
            $photo = $uploaded;
        }
    }

    $stmt = $pdo->prepare("
        UPDATE students
        SET name = ?, father_name = ?, mobile = ?, whatsapp_number = ?, parent_contact = ?, gender = ?, dob = ?,
            course_id = ?, admission_date = ?, address = ?, photo = ?,
            qualification = ?, school_college = ?, qualification_year = ?, timing = ?, reference_source = ?,
            fee_mode = ?, discount_percent = ?, final_fee = ?, updated_at = datetime('now','localtime')
        WHERE id = ?
    ");
    $stmt->execute([
        $name, $fatherName, $mobile, $whatsapp, $parentContact, $gender, $dob,
        $courseId, $admissionDate, $address, $photo,
        $qualification, $schoolCollege, $qualificationYear, $timing, $referenceSource,
        $feeMode, $discountPercent, $finalFee, $id,
    ]);

    jsonResponse(true, 'Student updated successfully.');
} catch (Exception $e) {
    jsonResponse(false, 'Failed to update student: ' . $e->getMessage(), [], 500);
}