<?php
/**
 * Change Password API
 * Allows admin to change their password
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

    $currentPassword = $input['current_password'] ?? '';
    $newPassword = $input['new_password'] ?? '';

    // Validation
    if (empty($currentPassword)) {
        respond(false, 'Current password is required', null, 400);
    }

    if (empty($newPassword) || strlen($newPassword) < 6) {
        respond(false, 'New password must be at least 6 characters', null, 400);
    }

    $db = Database::getInstance()->getConnection();

    // Get current user
    $stmt = $db->prepare("SELECT password FROM users WHERE id = :user_id");
    $stmt->execute([':user_id' => $_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        respond(false, 'User not found', null, 404);
    }

    // Verify current password (plain text comparison for this school project)
    if ($currentPassword !== $user['password']) {
        respond(false, 'Current password is incorrect', null, 400);
    }

    // Update password
    $stmt = $db->prepare("
        UPDATE users
        SET password = :password,
            updated_at = CURRENT_TIMESTAMP
        WHERE id = :user_id
    ");

    $stmt->execute([
        ':password' => $newPassword,
        ':user_id' => $_SESSION['user_id']
    ]);

    // Log activity
    $logStmt = $db->prepare("
        INSERT INTO activity_logs (user_id, username, user_role, activity_type, details, ip_address)
        VALUES (:user_id, :username, :role, 'user', :details, :ip)
    ");
    $logStmt->execute([
        ':user_id' => $_SESSION['user_id'],
        ':username' => $_SESSION['username'],
        ':role' => 'admin',
        ':details' => 'Changed password',
        ':ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
    ]);

    respond(true, 'Password changed successfully');

} catch (Throwable $e) {
    error_log('Change password error: ' . $e->getMessage());
    respond(false, 'Server error: ' . $e->getMessage(), null, 500);
}

