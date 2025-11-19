<?php
// Admin Account List API - List accounts for a specific company
header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store, must-revalidate');

session_start();

// Check admin authentication
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized - Admin access required']);
    exit;
}

require_once __DIR__ . '/../../../config/database.php';

// Get company_id from query parameter
$company_id = isset($_GET['company_id']) ? (int)$_GET['company_id'] : null;

if (!$company_id) {
    echo json_encode(['success' => false, 'message' => 'company_id parameter required']);
    exit;
}

try {
    $pdo = Database::getInstance()->getConnection();

    // Verify company exists
    $stmt = $pdo->prepare("SELECT id, company_name FROM companies WHERE id = ?");
    $stmt->execute([$company_id]);
    $company = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$company) {
        echo json_encode(['success' => false, 'message' => 'Company not found']);
        exit;
    }

    // Get all accounts for this company, including external system accounts
    $stmt = $pdo->prepare("
        SELECT
            a.id,
            a.company_id,
            a.account_code,
            a.account_name,
            a.account_type_id,
            at.type_name as account_type_name,
            at.normal_balance,
            a.current_balance,
            a.description,
            a.is_active,
            a.is_system_account,
            a.created_at
        FROM accounts a
        JOIN account_types at ON a.account_type_id = at.id
        WHERE a.company_id = ?
        ORDER BY
            a.is_system_account DESC,
            a.account_type_id ASC,
            a.account_code ASC
    ");

    $stmt->execute([$company_id]);
    $accounts = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'message' => 'Accounts loaded',
        'data' => $accounts,
        'company' => $company
    ]);

} catch (PDOException $e) {
    error_log("Admin account list error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}

