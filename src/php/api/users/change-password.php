<?php
/**
 * Change Password API
 * Allows users to change their password
 * Simple password validation (Reference.md: school project, keep it simple)
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: application/json');
session_start();
require_once __DIR__ . '/../../config/database.php';
function respond($ok, $msg = '', $data = null, $code = 200) {
    http_response_code($code);
    echo json_encode(['success' => $ok, 'message' => $msg, 'data' => $data]);
    exit;
}
try {
    if (!isset($_SESSION['user_id'])) respond(false, 'Unauthorized', null, 401);
    $userId = $_SESSION['user_id'];
    $input = json_decode(file_get_contents('php://input'), true);
    if (empty($input['current_password'])) respond(false, 'Current password is required', null, 400);
    if (empty($input['new_password'])) respond(false, 'New password is required', null, 400);
    if (strlen($input['new_password']) < 6) respond(false, 'Password must be at least 6 characters', null, 400);
    $db = Database::getInstance()->getConnection();
    // Verify current password (plain text comparison - school project)
    $sql = "SELECT password FROM users WHERE id = :id";
    $stmt = $db->prepare($sql);
    $stmt->execute([':id' => $userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$user || $user['password'] !== $input['current_password']) {
        respond(false, 'Current password is incorrect', null, 400);
    }
    // Update password
    $sql = "UPDATE users SET password = :password, updated_at = NOW() WHERE id = :id";
    $stmt = $db->prepare($sql);
    $stmt->execute([
        ':password' => $input['new_password'],
        ':id' => $userId
    ]);
    respond(true, 'Password changed successfully', ['id' => $userId], 200);
} catch (Throwable $e) {
    error_log('Change password error: ' . $e->getMessage());
    respond(false, 'Server error: ' . $e->getMessage(), null, 500);
}
