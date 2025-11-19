<?php
/**
 * Dashboard Revenue vs Expenses API
 * Returns revenue and expenses for tenant company
 * Aligned with: QUERIES.md Query 2.2 (Net Income calculation)
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

    // Get revenue accounts - Aligned with QUERIES.md
    // EXCLUDE external accounts (is_system_account = 1) - they are not company revenue
    $sqlRevenue = "SELECT
                a.id,
                a.account_code,
                a.account_name,
                a.current_balance as balance
            FROM accounts a
            WHERE a.company_id = :company_id
              AND a.account_type_id = 4
              AND a.is_active = TRUE
              AND a.is_system_account = 0
            ORDER BY a.account_code";

    $stmt = $db->prepare($sqlRevenue);
    $stmt->execute([':company_id' => $companyId]);
    $revenue = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get expense accounts
    // EXCLUDE external accounts (is_system_account = 1) - they are not company expenses
    $sqlExpenses = "SELECT
                a.id,
                a.account_code,
                a.account_name,
                a.current_balance as balance
            FROM accounts a
            WHERE a.company_id = :company_id
              AND a.account_type_id = 5
              AND a.is_active = TRUE
              AND a.is_system_account = 0
            ORDER BY a.account_code";

    $stmt = $db->prepare($sqlExpenses);
    $stmt->execute([':company_id' => $companyId]);
    $expenses = $stmt->fetchAll(PDO::FETCH_ASSOC);

    respond(true, 'Revenue and expenses loaded', [
        'revenue' => $revenue,
        'expenses' => $expenses
    ], 200);

} catch (Throwable $e) {
    error_log('Dashboard revenue-expenses error: ' . $e->getMessage());
    respond(false, 'Server error: ' . $e->getMessage(), null, 500);
}

