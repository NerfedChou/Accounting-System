<?php
/**
 * Admin Registration API
 * Creates new administrator account with verification code
 */

header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store, must-revalidate');

session_start();

require_once __DIR__ . '/../../../config/database.php';

// Get POST data
$data = json_decode(file_get_contents('php://input'), true);

$full_name = trim($data['full_name'] ?? '');
$username = trim($data['username'] ?? '');
$email = trim($data['email'] ?? '');
$password = $data['password'] ?? '';
$verification_code = trim($data['verification_code'] ?? '');

// Validation
if (empty($full_name) || empty($username) || empty($email) || empty($password)) {
    echo json_encode(['success' => false, 'message' => 'All fields are required']);
    exit;
}

// Validate verification code (dummy check - any 6-digit code works for demo)
if (strlen($verification_code) !== 6 || !ctype_digit($verification_code)) {
    echo json_encode(['success' => false, 'message' => 'Invalid verification code. Please enter a 6-digit code.']);
    exit;
}

// Validate email
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Invalid email address']);
    exit;
}

// Validate password length
if (strlen($password) < 8) {
    echo json_encode(['success' => false, 'message' => 'Password must be at least 8 characters long']);
    exit;
}

// Validate username (alphanumeric and underscore only)
if (!preg_match('/^[a-zA-Z0-9_]{3,}$/', $username)) {
    echo json_encode(['success' => false, 'message' => 'Username must be at least 3 characters and contain only letters, numbers, and underscores']);
    exit;
}

try {
    $pdo = Database::getInstance()->getConnection();

    // Check if username already exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->execute([$username]);
    if ($stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Username already exists']);
        exit;
    }

    // Check if email already exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Email already registered']);
        exit;
    }

    // NOTE: For school project, we're storing plain text password
    // In production, use password_hash()

    // Insert new admin user
    $stmt = $pdo->prepare("
        INSERT INTO users (
            company_id, username, email, password, full_name,
            role, is_active, registration_status, created_at
        ) VALUES (
            NULL, ?, ?, ?, ?,
            'admin', 1, 'approved', NOW()
        )
    ");

    $stmt->execute([
        $username,
        $email,
        $password, // Plain text for school project
        $full_name
    ]);

    $new_admin_id = $pdo->lastInsertId();

    // Log the registration
    $log_stmt = $pdo->prepare("
        INSERT INTO activity_logs (
            user_id, username, user_role, company_id,
            activity_type, details, created_at
        ) VALUES (?, ?, 'admin', NULL, 'user', ?, NOW())
    ");

    $log_stmt->execute([
        $new_admin_id,
        $username,
        "New administrator account registered: $full_name ($email) with verification code: $verification_code"
    ]);

    echo json_encode([
        'success' => true,
        'message' => 'Administrator account created successfully',
        'data' => [
            'id' => $new_admin_id,
            'username' => $username,
            'email' => $email,
            'full_name' => $full_name
        ]
    ]);

} catch (PDOException $e) {
    error_log("Admin registration error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}

