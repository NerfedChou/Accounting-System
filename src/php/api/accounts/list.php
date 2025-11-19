<?php
/**
 * Accounts List API
 * Returns chart of accounts for tenant company
 * Aligned with: QUERIES.md Query 3.1 (Account Balance Integrity)
 * Reference.md: Accounts are READ-ONLY here, created via transactions
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
    // Check authentication - Tenant only
    if (!isset($_SESSION['user_id'])) respond(false, 'Unauthorized', null, 401);
    if ($_SESSION['role'] !== 'tenant') respond(false, 'Tenant access required', null, 403);
    if (!isset($_SESSION['company_id'])) respond(false, 'No company assigned', null, 403);

    $db = Database::getInstance()->getConnection();

    // Check if user and company are active
    $stmt = $db->prepare("
        SELECT u.is_active, u.deactivation_reason, c.is_active as company_is_active
        FROM users u
        LEFT JOIN companies c ON u.company_id = c.id
        WHERE u.id = :user_id
    ");
    $stmt->execute([':user_id' => $_SESSION['user_id']]);
    $status = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$status['is_active']) {
        respond(false, 'Account deactivated: ' . ($status['deactivation_reason'] ?? 'Contact administrator'), null, 403);
    }

    if (!$status['company_is_active']) {
        respond(false, 'Company has been deactivated', null, 403);
    }

    $companyId = $_SESSION['company_id'];

    // Get all accounts for company - Aligned with QUERIES.md Query 3.1
    // This query gets accounts with their current balances
    $sql = "SELECT 
                a.id,
                a.account_code,
                a.account_name,
                a.description,
                a.account_type_id,
                at.type_name as account_type_name,
                at.normal_balance,
                a.opening_balance,
                a.current_balance,
                a.is_active,
                a.is_system_account,
                a.parent_account_id,
                a.created_at,
                (SELECT COUNT(*) 
                 FROM transaction_lines tl 
                 JOIN transactions t ON tl.transaction_id = t.id
                 WHERE tl.account_id = a.id AND t.status_id = 2) as transaction_count
            FROM accounts a
            JOIN account_types at ON a.account_type_id = at.id
            WHERE a.company_id = :company_id
            ORDER BY a.is_system_account DESC, a.account_type_id, a.account_code";

    $stmt = $db->prepare($sql);
    $stmt->execute([':company_id' => $companyId]);
    $accounts = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Format data
    foreach ($accounts as &$account) {
        $account['id'] = (int)$account['id'];
        $account['account_type_id'] = (int)$account['account_type_id'];
        $account['is_active'] = (bool)$account['is_active'];
        $account['is_system_account'] = (bool)$account['is_system_account'];
        $account['parent_account_id'] = $account['parent_account_id'] ? (int)$account['parent_account_id'] : null;
        $account['transaction_count'] = (int)$account['transaction_count'];
    }

    respond(true, 'Accounts loaded', $accounts, 200);

} catch (Throwable $e) {
    error_log('Accounts list error: ' . $e->getMessage());
    respond(false, 'Server error: ' . $e->getMessage(), null, 500);
}

