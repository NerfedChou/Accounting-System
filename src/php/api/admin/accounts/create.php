<?php
// Admin Account Creation API - Create account for any company
header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store, must-revalidate');

session_start();

// Check admin authentication
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized - Admin access required']);
    exit;
}

require_once __DIR__ . '/../../../config/database.php';

// Get request data
$data = json_decode(file_get_contents('php://input'), true);

// Validate required fields
if (empty($data['company_id']) || empty($data['account_code']) || empty($data['account_name']) || empty($data['account_type_id'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields: company_id, account_code, account_name, account_type_id']);
    exit;
}

$company_id = (int)$data['company_id'];
$account_code = trim($data['account_code']);
$account_name = trim($data['account_name']);
$account_type_id = (int)$data['account_type_id'];
$description = isset($data['description']) ? trim($data['description']) : null;
$opening_balance = isset($data['opening_balance']) ? (float)$data['opening_balance'] : 0.00;
$is_active = isset($data['is_active']) ? (int)$data['is_active'] : 1;
$is_system_account = isset($data['is_system_account']) ? (int)$data['is_system_account'] : 0; // Support external accounts

try {
    $pdo = Database::getInstance()->getConnection();

    // Verify company exists and is active
    $stmt = $pdo->prepare("SELECT id, company_name, is_active FROM companies WHERE id = ?");
    $stmt->execute([$company_id]);
    $company = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$company) {
        echo json_encode(['success' => false, 'message' => 'Company not found']);
        exit;
    }

    if (!$company['is_active']) {
        echo json_encode(['success' => false, 'message' => 'Cannot create account for inactive company']);
        exit;
    }

    // Check if account code already exists for this company
    $stmt = $pdo->prepare("SELECT id FROM accounts WHERE company_id = ? AND account_code = ?");
    $stmt->execute([$company_id, $account_code]);
    if ($stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => "Account code {$account_code} already exists for this company"]);
        exit;
    }

    // Validate account type
    $stmt = $pdo->prepare("SELECT id, type_name, normal_balance FROM account_types WHERE id = ?");
    $stmt->execute([$account_type_id]);
    $accountType = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$accountType) {
        echo json_encode(['success' => false, 'message' => 'Invalid account type']);
        exit;
    }

    // Insert account (with is_system_account support)
    $stmt = $pdo->prepare("
        INSERT INTO accounts (
            company_id,
            account_code,
            account_name,
            account_type_id,
            description,
            opening_balance,
            current_balance,
            is_active,
            is_system_account,
            created_by,
            created_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
    ");

    $stmt->execute([
        $company_id,
        $account_code,
        $account_name,
        $account_type_id,
        $description,
        $opening_balance,
        $opening_balance, // current_balance = opening_balance
        $is_active,
        $is_system_account,
        $_SESSION['user_id']
    ]);

    $account_id = $pdo->lastInsertId();

    // Log activity
    $stmt = $pdo->prepare("
        INSERT INTO activity_logs (user_id, username, user_role, company_id, activity_type, details)
        VALUES (?, ?, 'admin', ?, 'account', ?)
    ");
    $stmt->execute([
        $_SESSION['user_id'],
        $_SESSION['username'] ?? 'admin',
        $company_id,
        "Created account: {$account_code} - {$account_name} for company {$company['company_name']}"
    ]);

    echo json_encode([
        'success' => true,
        'message' => 'Account created successfully',
        'data' => [
            'id' => $account_id,
            'account_code' => $account_code,
            'account_name' => $account_name,
            'company_id' => $company_id,
            'company_name' => $company['company_name']
        ]
    ]);

} catch (PDOException $e) {
    error_log("Admin account creation error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}

