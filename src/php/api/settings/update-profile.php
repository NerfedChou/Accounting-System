<?php
/**
 * Update Admin Profile API
 * Allows admin to update their profile information
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store, must-revalidate');
session_start();

require_once __DIR__ . '/../../config/database.php';

function respond($ok, $msg = '', $data = null, $code = 200) {
    http_response_code($code);
    echo json_encode(['success' => $ok, 'message' => $msg, 'data' => $data]);
    exit;
}

try {
    // Check authentication
    if (!isset($_SESSION['user_id'])) respond(false, 'Unauthorized', null, 401);
    if ($_SESSION['role'] !== 'admin') respond(false, 'Admin access required', null, 403);

    // Get input
    $input = json_decode(file_get_contents('php://input'), true);

    if (!$input) {
        respond(false, 'Invalid JSON input', null, 400);
    }

    $fullName = trim($input['full_name'] ?? '');
    $email = trim($input['email'] ?? '');

    // Validation
    if (empty($fullName)) {
        respond(false, 'Full name is required', null, 400);
    }

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        respond(false, 'Valid email is required', null, 400);
    }

    $db = Database::getInstance()->getConnection();

    // Check if email is already taken by another user
    $stmt = $db->prepare("SELECT id FROM users WHERE email = :email AND id != :user_id");
    $stmt->execute([
        ':email' => $email,
        ':user_id' => $_SESSION['user_id']
    ]);

    if ($stmt->fetch()) {
        respond(false, 'Email is already in use by another account', null, 400);
    }

    // Update profile
    $stmt = $db->prepare("
        UPDATE users
        SET full_name = :full_name,
            email = :email,
            updated_at = CURRENT_TIMESTAMP
        WHERE id = :user_id
    ");

    $stmt->execute([
        ':full_name' => $fullName,
        ':email' => $email,
        ':user_id' => $_SESSION['user_id']
    ]);

    // Update session variables
    $_SESSION['full_name'] = $fullName;
    $_SESSION['email'] = $email;

    // Log activity
    $logStmt = $db->prepare("
        INSERT INTO activity_logs (user_id, username, user_role, activity_type, details, ip_address)
        VALUES (:user_id, :username, :role, 'user', :details, :ip)
    ");
    $logStmt->execute([
        ':user_id' => $_SESSION['user_id'],
        ':username' => $_SESSION['username'],
        ':role' => 'admin',
        ':details' => 'Updated profile information',
        ':ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
    ]);

    respond(true, 'Profile updated successfully', [
        'full_name' => $fullName,
        'email' => $email
    ]);

} catch (Throwable $e) {
    error_log('Update profile error: ' . $e->getMessage());
    respond(false, 'Server error: ' . $e->getMessage(), null, 500);
}

