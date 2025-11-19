<?php
/**
 * Session Check API
 * Accounting System
 *
 * Returns current session data if user is logged in
 * Aligned with: session.js init() method
 */

// Enable error reporting for development
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Prevent caching of session data
header('Cache-Control: no-cache, no-store, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: Thu, 01 Jan 1970 00:00:00 GMT');

// Set JSON header
header('Content-Type: application/json');

// Start session
session_start();

// Response helper
function jsonResponse($success, $message = '', $data = null, $statusCode = 200) {
    http_response_code($statusCode);
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data
    ]);
    exit;
}

try {
    // Check if user is logged in
    if (isset($_SESSION['user_id']) && isset($_SESSION['role'])) {
        // Query database for real-time user status (is_active, deactivation_reason)
        require_once __DIR__ . '/../../config/database.php';
        $db = Database::getInstance()->getConnection();

        $stmt = $db->prepare("
            SELECT u.is_active, u.deactivation_reason, u.registration_status, u.declined_reason,
                   c.is_active as company_is_active
            FROM users u
            LEFT JOIN companies c ON u.company_id = c.id
            WHERE u.id = :user_id
            LIMIT 1
        ");
        $stmt->execute([':user_id' => $_SESSION['user_id']]);
        $dbUser = $stmt->fetch(PDO::FETCH_ASSOC);

        // If user not found in DB, session is invalid
        if (!$dbUser) {
            jsonResponse(false, 'Invalid session', null, 401);
        }

        // Update session with latest data from database
        $_SESSION['is_active'] = $dbUser['is_active'];
        $_SESSION['deactivation_reason'] = $dbUser['deactivation_reason'];
        $_SESSION['registration_status'] = $dbUser['registration_status'] ?? 'approved';
        $_SESSION['declined_reason'] = $dbUser['declined_reason'];
        $_SESSION['company_is_active'] = $dbUser['company_is_active'];

        // User is logged in, return session data with real-time DB values
        $sessionData = [
            'user' => [
                'id' => $_SESSION['user_id'],
                'username' => $_SESSION['username'] ?? 'Unknown',
                'full_name' => $_SESSION['full_name'] ?? 'Unknown User',
                'email' => $_SESSION['email'] ?? '',
                'role' => $_SESSION['role'],
                'is_active' => (int)$dbUser['is_active'],  // Real-time from DB
                'company_id' => $_SESSION['company_id'] ?? null,
                'registration_status' => $dbUser['registration_status'] ?? 'approved',  // Real-time from DB
                'declined_reason' => $dbUser['declined_reason'],  // Real-time from DB
                'approved_at' => $_SESSION['approved_at'] ?? null,
                'created_at' => $_SESSION['created_at'] ?? null,
                'last_login' => $_SESSION['last_login'] ?? null,
                'deactivation_reason' => $dbUser['deactivation_reason']  // Real-time from DB
            ],
            'company' => null
        ];

        // If tenant, include company data
        if ($_SESSION['role'] === 'tenant' && isset($_SESSION['company_id'])) {
            $sessionData['company'] = [
                'id' => $_SESSION['company_id'],
                'company_name' => $_SESSION['company_name'] ?? 'Unknown Company',
                'currency_code' => $_SESSION['currency_code'] ?? 'USD',
                'is_active' => (int)($dbUser['company_is_active'] ?? 1)  // Real-time from DB
            ];
        }

        jsonResponse(true, 'Session active', $sessionData, 200);
    } else {
        // Not logged in
        jsonResponse(false, 'No active session', null, 401);
    }

} catch (Exception $e) {
    error_log("Session API Error: " . $e->getMessage());
    jsonResponse(false, 'Server error', null, 500);
}

