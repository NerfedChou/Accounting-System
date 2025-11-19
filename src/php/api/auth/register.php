<?php
/**
 * Tenant Registration API
 * Allows new tenants to self-register for account approval
 * Aligned with: TODO-TENANT-REGISTRATION-WORKFLOW.md Phase 3
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
    // This is a PUBLIC endpoint - no authentication required

    $input = json_decode(file_get_contents('php://input'), true);

    // Validate required fields
    if (empty($input['full_name'])) respond(false, 'Full name is required', null, 400);
    if (empty($input['email'])) respond(false, 'Email is required', null, 400);
    if (empty($input['username'])) respond(false, 'Username is required', null, 400);
    if (empty($input['password'])) respond(false, 'Password is required', null, 400);

    // Validate email format
    if (!filter_var($input['email'], FILTER_VALIDATE_EMAIL)) {
        respond(false, 'Invalid email format', null, 400);
    }

    // Validate username length
    if (strlen($input['username']) < 3 || strlen($input['username']) > 50) {
        respond(false, 'Username must be between 3 and 50 characters', null, 400);
    }

    // Validate password length (school project - keep it simple)
    if (strlen($input['password']) < 6) {
        respond(false, 'Password must be at least 6 characters', null, 400);
    }

    $db = Database::getInstance()->getConnection();

    // Check if username already exists
    $sql = "SELECT COUNT(*) as count FROM users WHERE username = :username";
    $stmt = $db->prepare($sql);
    $stmt->execute([':username' => $input['username']]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($result['count'] > 0) {
        respond(false, 'Username already exists. Please choose another.', null, 400);
    }

    // Check if email already exists
    $sql = "SELECT COUNT(*) as count FROM users WHERE email = :email";
    $stmt = $db->prepare($sql);
    $stmt->execute([':email' => $input['email']]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($result['count'] > 0) {
        respond(false, 'Email already registered. Please use another email.', null, 400);
    }

    // Create user with pending status
    $sql = "INSERT INTO users (
                username,
                email,
                password,
                full_name,
                role,
                company_id,
                is_active,
                registration_status,
                registration_date,
                company_name_requested,
                business_type,
                registration_notes,
                created_at
            ) VALUES (
                :username,
                :email,
                :password,
                :full_name,
                'tenant',
                NULL,
                0,
                'pending',
                NOW(),
                :company_name_requested,
                :business_type,
                :registration_notes,
                NOW()
            )";

    $stmt = $db->prepare($sql);
    $stmt->execute([
        ':username' => $input['username'],
        ':email' => $input['email'],
        ':password' => $input['password'], // Plain text (school project)
        ':full_name' => $input['full_name'],
        ':company_name_requested' => $input['company_name_requested'] ?? null,
        ':business_type' => $input['business_type'] ?? null,
        ':registration_notes' => $input['registration_notes'] ?? null
    ]);

    $userId = $db->lastInsertId();

    // Optional: Send email notification to admins
    // TODO: Implement email notification when ready

    respond(true, 'Registration submitted successfully! Please wait for admin approval.', [
        'user_id' => $userId,
        'username' => $input['username'],
        'status' => 'pending'
    ], 201);

} catch (Throwable $e) {
    error_log('Registration error: ' . $e->getMessage());
    respond(false, 'Server error: ' . $e->getMessage(), null, 500);
}

