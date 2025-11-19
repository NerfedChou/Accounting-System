<?php
// Account Creation API
header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store, must-revalidate');

session_start();

require_once __DIR__ . '/../../config/database.php';

// Check authentication and is_active status
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$pdo = Database::getInstance()->getConnection();

// Check if user is active tenant
$stmt = $pdo->prepare("SELECT role, company_id, is_active, deactivation_reason FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user || $user['role'] !== 'tenant') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized - Tenant access required']);
    exit;
}

if ($user['is_active'] != 1) {
    $reason = $user['deactivation_reason'] ?? 'Account has been deactivated';
    echo json_encode(['success' => false, 'message' => 'Account is deactivated: ' . $reason, 'deactivated' => true]);
    exit;
}

$_SESSION['company_id'] = $user['company_id'];

// Get request data
$data = json_decode(file_get_contents('php://input'), true);

// Validate required fields
if (empty($data['account_code']) || empty($data['account_name']) || empty($data['account_type_id'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

$company_id = $_SESSION['company_id'];
$account_code = trim($data['account_code']);
$account_name = trim($data['account_name']);
$account_type_id = (int)$data['account_type_id'];
$description = isset($data['description']) ? trim($data['description']) : '';
$created_by = $_SESSION['user_id'];

// External account support
$is_system_account = isset($data['is_system_account']) ? (int)$data['is_system_account'] : 0;
$opening_balance = isset($data['opening_balance']) ? (float)$data['opening_balance'] : 0;

try {
    $pdo = Database::getInstance()->getConnection();

    // Check if account code already exists for this company
    $stmt = $pdo->prepare("
        SELECT id FROM accounts
        WHERE company_id = ? AND account_code = ?
    ");
    $stmt->execute([$company_id, $account_code]);

    if ($stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Account code already exists']);
        exit;
    }

    // Validate account type (1-5)
    if ($account_type_id < 1 || $account_type_id > 5) {
        echo json_encode(['success' => false, 'message' => 'Invalid account type']);
        exit;
    }

    // Insert account
    $stmt = $pdo->prepare("
        INSERT INTO accounts (
            company_id,
            account_type_id,
            account_code,
            account_name,
            description,
            opening_balance,
            current_balance,
            is_active,
            is_system_account,
            created_by
        ) VALUES (?, ?, ?, ?, ?, ?, ?, 1, ?, ?)
    ");

    $stmt->execute([
        $company_id,
        $account_type_id,
        $account_code,
        $account_name,
        $description,
        $opening_balance,
        $opening_balance, // current_balance = opening_balance
        $is_system_account,
        $created_by
    ]);

    $account_id = $pdo->lastInsertId();

    // Log activity
    $stmt = $pdo->prepare("
        INSERT INTO activity_logs (user_id, username, user_role, company_id, activity_type, details)
        VALUES (?, ?, 'tenant', ?, 'account', ?)
    ");
    $stmt->execute([
        $created_by,
        $_SESSION['username'] ?? 'unknown',
        $company_id,
        "Created account: $account_code - $account_name"
    ]);

    echo json_encode([
        'success' => true,
        'message' => 'Account created successfully',
        'data' => [
            'id' => $account_id,
            'account_code' => $account_code,
            'account_name' => $account_name,
            'account_type_id' => $account_type_id,
            'current_balance' => 0
        ]
    ]);

} catch (PDOException $e) {
    error_log("Account creation error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}

