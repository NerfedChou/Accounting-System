<?php
/**
 * Admin Balance Sheet Report API
 * Generate balance sheet for any company
 */
header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store, must-revalidate');

session_start();

// Check admin authentication
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized - Admin access required']);
    exit;
}

require_once __DIR__ . '/../../../config/database.php';

// Get parameters
$company_id = isset($_GET['company_id']) ? (int)$_GET['company_id'] : null;
$as_of_date = isset($_GET['as_of']) ? $_GET['as_of'] : date('Y-m-d');

if (!$company_id) {
    echo json_encode(['success' => false, 'message' => 'company_id parameter required']);
    exit;
}

try {
    $pdo = Database::getInstance()->getConnection();

    // Get company info
    $stmt = $pdo->prepare("SELECT * FROM companies WHERE id = ?");
    $stmt->execute([$company_id]);
    $company = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$company) {
        echo json_encode(['success' => false, 'message' => 'Company not found']);
        exit;
    }

    // Get Assets (EXCLUDE external accounts AND voided accounts)
    $stmt = $pdo->prepare("
        SELECT account_code, account_name, current_balance as balance
        FROM accounts
        WHERE company_id = ?
          AND account_type_id = 1
          AND is_active = 1
          AND is_system_account = 0
          AND (description IS NULL OR description NOT LIKE '[VOIDED:%')
        ORDER BY account_code
    ");
    $stmt->execute([$company_id]);
    $assets = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Categorize assets (simple categorization)
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

    $total_assets = array_sum(array_column($assets, 'balance'));

    // Get Liabilities (EXCLUDE external accounts AND voided accounts)
    $stmt = $pdo->prepare("
        SELECT account_code, account_name, current_balance as balance
        FROM accounts
        WHERE company_id = ?
          AND account_type_id = 2
          AND is_active = 1
          AND is_system_account = 0
          AND (description IS NULL OR description NOT LIKE '[VOIDED:%')
        ORDER BY account_code
    ");
    $stmt->execute([$company_id]);
    $liabilities = $stmt->fetchAll(PDO::FETCH_ASSOC);

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

    $total_liabilities = array_sum(array_column($liabilities, 'balance'));

    // Get Equity (EXCLUDE external accounts AND voided accounts)
    $stmt = $pdo->prepare("
        SELECT account_code, account_name, current_balance as balance
        FROM accounts
        WHERE company_id = ?
          AND account_type_id = 3
          AND is_active = 1
          AND is_system_account = 0
          AND (description IS NULL OR description NOT LIKE '[VOIDED:%')
        ORDER BY account_code
    ");
    $stmt->execute([$company_id]);
    $equity = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $contributed_capital = $equity;
    $total_equity = array_sum(array_column($equity, 'balance'));

    // Calculate retained earnings (Revenue - Expenses) - EXCLUDE external accounts AND voided accounts
    $stmt = $pdo->prepare("
        SELECT SUM(current_balance) as revenue
        FROM accounts
        WHERE company_id = ?
          AND account_type_id = 4
          AND is_active = 1
          AND is_system_account = 0
          AND (description IS NULL OR description NOT LIKE '[VOIDED:%')
    ");
    $stmt->execute([$company_id]);
    $revenue_result = $stmt->fetch(PDO::FETCH_ASSOC);
    $total_revenue = $revenue_result['revenue'] ?? 0;

    $stmt = $pdo->prepare("
        SELECT SUM(current_balance) as expenses
        FROM accounts
        WHERE company_id = ?
          AND account_type_id = 5
          AND is_active = 1
          AND is_system_account = 0
          AND (description IS NULL OR description NOT LIKE '[VOIDED:%')
    ");
    $stmt->execute([$company_id]);
    $expense_result = $stmt->fetch(PDO::FETCH_ASSOC);
    $total_expenses = $expense_result['expenses'] ?? 0;

    $retained_earnings = $total_revenue - $total_expenses;
    $total_equity_with_earnings = $total_equity + $retained_earnings;

    // Check if balanced
    $difference = abs($total_assets - ($total_liabilities + $total_equity_with_earnings));
    $balanced = $difference < 0.01;

    $response = [
        'success' => true,
        'message' => 'Balance sheet generated',
        'data' => [
            'company' => $company,
            'as_of_date' => $as_of_date,
            'assets' => [
                'current_assets' => $current_assets,
                'fixed_assets' => $fixed_assets,
                'total' => $total_assets
            ],
            'liabilities' => [
                'current_liabilities' => $current_liabilities,
                'long_term_liabilities' => $long_term_liabilities,
                'total' => $total_liabilities
            ],
            'equity' => [
                'contributed_capital' => $contributed_capital,
                'retained_earnings' => $retained_earnings,
                'total' => $total_equity_with_earnings
            ],
            'balanced' => $balanced,
            'difference' => $difference
        ]
    ];

    echo json_encode($response);

} catch (PDOException $e) {
    error_log("Admin Balance Sheet Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}

