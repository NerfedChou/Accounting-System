<?php
/**
 * Environment Configuration
 * Accounting System
 */

// Database Configuration
define('DB_HOST', 'mysql');
define('DB_NAME', 'accounting_db');
define('DB_USER', 'accounting_user');
define('DB_PASS', 'accounting_pass');
define('DB_CHARSET', 'utf8mb4');

// Application Configuration
define('APP_NAME', 'Accounting System');
define('APP_ENV', 'development'); // development, production
define('APP_DEBUG', true);

// Session Configuration
define('SESSION_LIFETIME', 3600); // 1 hour
define('SESSION_NAME', 'accounting_session');

// Paths
define('BASE_PATH', dirname(__DIR__));
define('API_PATH', BASE_PATH . '/api');

// Error Reporting
if (APP_DEBUG) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// Timezone
date_default_timezone_set('UTC');

