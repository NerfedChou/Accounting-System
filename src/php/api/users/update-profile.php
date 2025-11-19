<?php
/**
 * Update User Profile API
 * Allows users to update their name and email
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
    if (empty($input['full_name'])) respond(false, 'Full name is required', null, 400);
    if (empty($input['email'])) respond(false, 'Email is required', null, 400);
    $db = Database::getInstance()->getConnection();
    // Update user profile
    $sql = "UPDATE users SET
                full_name = :full_name,
                email = :email,
                updated_at = NOW()
            WHERE id = :id";
    $stmt = $db->prepare($sql);
    $stmt->execute([
        ':full_name' => $input['full_name'],
        ':email' => $input['email'],
        ':id' => $userId
    ]);
    respond(true, 'Profile updated successfully', ['id' => $userId], 200);
} catch (Throwable $e) {
    error_log('Update profile error: ' . $e->getMessage());
    respond(false, 'Server error: ' . $e->getMessage(), null, 500);
}
