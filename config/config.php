<?php
/**
 * Global Application Configuration
 * Institution Billing & Fee Management System
 */

// Error reporting - set to 0 in live production after testing
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session (kept for potential future use / flash messages)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Timezone - change to your local timezone
date_default_timezone_set('Asia/Kolkata');

// Base paths
define('BASE_PATH', dirname(__DIR__));
define('DB_PATH', BASE_PATH . '/database/institution.sqlite');
define('UPLOAD_PHOTO_PATH', BASE_PATH . '/assets/uploads/photos/');
define('UPLOAD_LOGO_PATH', BASE_PATH . '/assets/uploads/logo/');

// Base URL - IMPORTANT: change this if your project folder name is different
// Example: if you access the project as http://localhost/institution-billing/
// then BASE_URL should be '/institution-billing'
define('BASE_URL', '/institution-billing');

// App info
define('APP_NAME', 'Institution Billing & Fee Management System');
define('APP_VERSION', '1.0.0');

// Currency symbol used throughout the app
define('CURRENCY', '₹');
