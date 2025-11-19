<?php
/**
 * Balance Sheet Report API
 * Aligned with: QUERIES.md Query 2.1
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
    $asOfDate = isset($_GET['as_of_date']) ? $_GET['as_of_date'] : date('Y-m-d');

    // Get company info
    $stmt = $db->prepare("SELECT * FROM companies WHERE id = ?");
    $stmt->execute([$companyId]);
    $company = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$company) {
        respond(false, 'Company not found', null, 404);
    }

    // Get Assets (EXCLUDE external accounts AND voided accounts)
    $sql = "SELECT a.id, a.account_code, a.account_name, a.current_balance as balance
            FROM accounts a
            WHERE a.company_id = :company_id
              AND a.account_type_id = 1
              AND a.is_active = TRUE
              AND a.is_system_account = 0
              AND (a.description IS NULL OR a.description NOT LIKE '[VOIDED:%')
            ORDER BY a.account_code";
    $stmt = $db->prepare($sql);
    $stmt->execute([':company_id' => $companyId]);
    $assets = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $totalAssets = array_sum(array_column($assets, 'balance'));

    // Get Liabilities (EXCLUDE external accounts AND voided accounts)
    $sql = "SELECT a.id, a.account_code, a.account_name, a.current_balance as balance
            FROM accounts a
            WHERE a.company_id = :company_id
              AND a.account_type_id = 2
              AND a.is_active = TRUE
              AND a.is_system_account = 0
              AND (a.description IS NULL OR a.description NOT LIKE '[VOIDED:%')
            ORDER BY a.account_code";
    $stmt = $db->prepare($sql);
    $stmt->execute([':company_id' => $companyId]);
    $liabilities = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $totalLiabilities = array_sum(array_column($liabilities, 'balance'));

    // Get Equity (EXCLUDE external accounts AND voided accounts)
    $sql = "SELECT a.id, a.account_code, a.account_name, a.current_balance as balance
            FROM accounts a
            WHERE a.company_id = :company_id
              AND a.account_type_id = 3
              AND a.is_active = TRUE
              AND a.is_system_account = 0
              AND (a.description IS NULL OR a.description NOT LIKE '[VOIDED:%')
            ORDER BY a.account_code";
    $stmt = $db->prepare($sql);
    $stmt->execute([':company_id' => $companyId]);
    $equity = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $totalEquity = array_sum(array_column($equity, 'balance'));

    // Categorize assets (simple categorization based on account codes)
    $current_assets = [];
    $fixed_assets = [];
    foreach ($assets as $asset) {
        $code = (int)$asset['account_code'];
        if ($code >= 1000 && $code < 1500) {
            $current_assets[] = $asset;
        } else {
            $fixed_assets[] = $asset;
        }
    }

    // Categorize liabilities
    $current_liabilities = [];
    $long_term_liabilities = [];
    foreach ($liabilities as $liability) {
        $code = (int)$liability['account_code'];
        if ($code >= 2000 && $code < 2500) {
            $current_liabilities[] = $liability;
        } else {
            $long_term_liabilities[] = $liability;
        }
    }

    // Get revenue and expenses (EXCLUDE external accounts AND voided accounts)
    $stmt = $db->prepare("
        SELECT SUM(current_balance) as revenue
        FROM accounts
        WHERE company_id = ?
          AND account_type_id = 4
          AND is_active = 1
          AND is_system_account = 0
          AND (description IS NULL OR description NOT LIKE '[VOIDED:%')
    ");
    $stmt->execute([$companyId]);
    $revenue_result = $stmt->fetch(PDO::FETCH_ASSOC);
    $total_revenue = $revenue_result['revenue'] ?? 0;

    $stmt = $db->prepare("
        SELECT SUM(current_balance) as expenses
        FROM accounts
        WHERE company_id = ?
          AND account_type_id = 5
          AND is_active = 1
          AND is_system_account = 0
          AND (description IS NULL OR description NOT LIKE '[VOIDED:%')
    ");
    
    $stmt->execute([$companyId]);
    $expense_result = $stmt->fetch(PDO::FETCH_ASSOC);
    $total_expenses = $expense_result['expenses'] ?? 0;

    $retained_earnings = $total_revenue - $total_expenses;
    $total_equity_with_earnings = $totalEquity + $retained_earnings;

    $difference = abs($totalAssets - ($totalLiabilities + $total_equity_with_earnings));
    $balanced = $difference < 0.01;

    respond(true, 'Balance Sheet generated', [
        'company' => $company,
        'as_of_date' => $asOfDate,
        'assets' => [
            'current_assets' => $current_assets,
            'fixed_assets' => $fixed_assets,
            'total' => $totalAssets
        ],
        'liabilities' => [
            'current_liabilities' => $current_liabilities,
            'long_term_liabilities' => $long_term_liabilities,
            'total' => $totalLiabilities
        ],
        'equity' => [
            'contributed_capital' => $equity,
            'retained_earnings' => $retained_earnings,
            'total' => $total_equity_with_earnings
        ],
        'balanced' => $balanced,
        'difference' => $difference
    ], 200);
} catch (Throwable $e) {
    error_log('Balance Sheet error: ' . $e->getMessage());
    respond(false, 'Server error: ' . $e->getMessage(), null, 500);
}
