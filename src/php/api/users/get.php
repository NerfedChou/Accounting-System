<?php
/**
 * Get User Details API
 * Returns single user information
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
    if ($_SESSION['role'] !== 'admin') respond(false, 'Admin access required', null, 403);

    $userId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    if ($userId <= 0) respond(false, 'Invalid user ID', null, 400);

    $db = Database::getInstance()->getConnection();

    $sql = "SELECT id, company_id, username, email, full_name, role, is_active, created_at
            FROM users WHERE id = :id";
    $stmt = $db->prepare($sql);
    $stmt->execute([':id' => $userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) respond(false, 'User not found', null, 404);

    respond(true, 'User loaded', $user, 200);

} catch (Throwable $e) {
    error_log('Get user error: ' . $e->getMessage());
    respond(false, 'Server error: ' . $e->getMessage(), null, 500);
}

