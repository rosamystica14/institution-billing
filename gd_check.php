<?php
/**
 * gd_check.php - Temporary diagnostic page.
 * Visit this in your browser to confirm whether the GD extension is
 * enabled, and which php.ini file PHP is actually using.
 *
 * DELETE THIS FILE once you're done checking — it's not part of the app.
 */
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head><title>GD Extension Check</title></head>
<body style="font-family: sans-serif; padding: 24px; max-width: 700px; margin: 0 auto;">
    <h2>GD Extension Diagnostic</h2>

    <?php $gdLoaded = extension_loaded('gd'); ?>

    <p style="font-size: 20px; font-weight: bold; color: <?= $gdLoaded ? 'green' : 'red' ?>;">
        GD Extension: <?= $gdLoaded ? '✅ LOADED' : '❌ NOT LOADED' ?>
    </p>

    <?php if ($gdLoaded): ?>
        <p>GD is available. imagecreatetruecolor() and imagettftext() should work.</p>
        <?php $info = gd_info(); ?>
        <ul>
            <?php foreach ($info as $key => $val): ?>
                <li><strong><?= htmlspecialchars($key) ?>:</strong> <?= htmlspecialchars(is_bool($val) ? ($val ? 'Yes' : 'No') : (string)$val) ?></li>
            <?php endforeach; ?>
        </ul>
    <?php else: ?>
        <p>GD is <strong>not</strong> enabled. This is why the receipt image / WhatsApp copy feature fails.</p>
    <?php endif; ?>

    <hr>
    <p><strong>PHP Version:</strong> <?= htmlspecialchars(phpversion()) ?></p>
    <p><strong>Loaded php.ini file:</strong><br><code><?= htmlspecialchars(php_ini_loaded_file() ?: 'None detected') ?></code></p>
    <p><strong>Additional .ini files scanned:</strong><br><code><?= htmlspecialchars(php_ini_scanned_files() ?: 'None') ?></code></p>

    <hr>
    <p style="color: #888;">Delete this file after checking — it exposes server configuration details.</p>
</body>
</html>