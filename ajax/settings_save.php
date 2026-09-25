<?php
require_once __DIR__ . '/../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, 'Invalid request method.', [], 405);
}

try {
    $institutionName = sanitize($_POST['institution_name'] ?? '');
    $address = sanitize($_POST['address'] ?? '');
    $phone = sanitize($_POST['phone'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $removeLogo = isset($_POST['remove_logo']) && $_POST['remove_logo'] === '1';

    if ($institutionName === '') {
        jsonResponse(false, 'Institution name is required.', [], 422);
    }

    if ($phone !== '' && !preg_match('/^[0-9]{10}$/', $phone)) {
        jsonResponse(false, 'Please enter a valid 10-digit phone number.', [], 422);
    }

    if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        jsonResponse(false, 'Please enter a valid email address.', [], 422);
    }

    $pdo = db();

    // The WhatsApp Cloud API fields (whatsapp_token, whatsapp_phone_number_id,
    // whatsapp_api_version) are no longer editable from the Settings page,
    // so we intentionally leave those columns untouched here — this INSERT
    // ... ON CONFLICT only ever updates the institution-details columns.
    $current = $pdo->query("SELECT logo FROM settings WHERE id = 1")->fetch();
    $logo = $current['logo'] ?? null;

    if (!empty($_FILES['logo']['name'])) {
        // A newly uploaded file always takes priority over a "remove logo" request.
        $allowedTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $_FILES['logo']['tmp_name']);
        finfo_close($finfo);

        if (in_array($mime, $allowedTypes, true)) {
            $ext = pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION);
            $filename = 'logo_' . uniqid() . '.' . $ext;
            if (!is_dir(UPLOAD_LOGO_PATH)) {
                mkdir(UPLOAD_LOGO_PATH, 0755, true);
            }
            if (move_uploaded_file($_FILES['logo']['tmp_name'], UPLOAD_LOGO_PATH . $filename)) {
                if (!empty($logo) && file_exists(UPLOAD_LOGO_PATH . $logo)) {
                    @unlink(UPLOAD_LOGO_PATH . $logo);
                }
                $logo = $filename;
            }
        } else {
            jsonResponse(false, 'Invalid logo file. Only JPG, PNG, GIF, WEBP are allowed.', [], 422);
        }
    } elseif ($removeLogo && !empty($logo)) {
        // No new file, but the user asked to remove the current logo —
        // delete the file and clear the column so the app falls back to
        // its default mortarboard icon everywhere the logo is shown.
        if (file_exists(UPLOAD_LOGO_PATH . $logo)) {
            @unlink(UPLOAD_LOGO_PATH . $logo);
        }
        $logo = null;
    }

    $stmt = $pdo->prepare("
        INSERT INTO settings (id, institution_name, logo, address, phone, email, updated_at)
        VALUES (1, ?, ?, ?, ?, ?, datetime('now','localtime'))
        ON CONFLICT(id) DO UPDATE SET
            institution_name = excluded.institution_name,
            logo = excluded.logo,
            address = excluded.address,
            phone = excluded.phone,
            email = excluded.email,
            updated_at = datetime('now','localtime')
    ");
    $stmt->execute([$institutionName, $logo, $address, $phone, $email]);

    jsonResponse(true, 'Settings saved successfully.');
} catch (Exception $e) {
    jsonResponse(false, 'Failed to save settings: ' . $e->getMessage(), [], 500);
}