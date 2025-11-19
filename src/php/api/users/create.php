<?php
/**
 * Create User API
 * Creates a new tenant user
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

    $input = json_decode(file_get_contents('php://input'), true);

    $companyId = isset($input['company_id']) ? (int)$input['company_id'] : 0;
    $username = isset($input['username']) ? trim($input['username']) : '';
    $email = isset($input['email']) ? trim($input['email']) : '';
    $fullName = isset($input['full_name']) ? trim($input['full_name']) : '';
    $password = isset($input['password']) ? $input['password'] : '';

    if ($companyId <= 0) respond(false, 'Company ID is required', null, 400);
    if (empty($username)) respond(false, 'Username is required', null, 400);
    if (empty($email)) respond(false, 'Email is required', null, 400);
    if (empty($fullName)) respond(false, 'Full name is required', null, 400);
    if (empty($password)) respond(false, 'Password is required', null, 400);

    $db = Database::getInstance()->getConnection();

    // Check if username exists
    $stmt = $db->prepare("SELECT id FROM users WHERE username = :username");
    $stmt->execute([':username' => $username]);
    if ($stmt->fetch()) respond(false, 'Username already exists', null, 400);

    // Plain text password (school project - SelfPrompt.md)
    $sql = "INSERT INTO users (
                company_id, username, email, password, full_name, role, is_active, created_at
            ) VALUES (
                :company_id, :username, :email, :password, :full_name, 'tenant', 1, NOW()
            )";

    $stmt = $db->prepare($sql);
    $stmt->execute([
        ':company_id' => $companyId,
        ':username' => $username,
        ':email' => $email,
        ':password' => $password,
        ':full_name' => $fullName
    ]);

    $userId = $db->lastInsertId();

    respond(true, 'Tenant created successfully', ['id' => $userId], 201);

} catch (Throwable $e) {
    error_log('Create user error: ' . $e->getMessage());
    respond(false, 'Server error: ' . $e->getMessage(), null, 500);
}

