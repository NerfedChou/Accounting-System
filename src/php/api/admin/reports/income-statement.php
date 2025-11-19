<?php
/**
 * Admin Income Statement Report API
 * Generate income statement for any company
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
$start_date = isset($_GET['start']) ? $_GET['start'] : date('Y-m-01');
$end_date = isset($_GET['end']) ? $_GET['end'] : date('Y-m-d');

if (!$company_id) {
    echo json_encode(['success' => false, 'message' => 'company_id parameter required']);
    exit;
}

if (!$start_date || !$end_date) {
    echo json_encode(['success' => false, 'message' => 'start and end date parameters required']);
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

    // Get Revenue accounts (EXCLUDE external AND voided accounts)
    $stmt = $pdo->prepare("
        SELECT account_code, account_name, current_balance as balance
        FROM accounts
        WHERE company_id = ?
          AND account_type_id = 4
          AND is_active = 1
          AND is_system_account = 0
          AND (description IS NULL OR description NOT LIKE '[VOIDED:%')
        ORDER BY account_code
    ");
    $stmt->execute([$company_id]);
    $revenue_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $total_revenue = array_sum(array_column($revenue_items, 'balance'));

    // Get Expense accounts (EXCLUDE external AND voided accounts)
    $stmt = $pdo->prepare("
        SELECT account_code, account_name, current_balance as balance
        FROM accounts
        WHERE company_id = ?
          AND account_type_id = 5
          AND is_active = 1
          AND is_system_account = 0
          AND (description IS NULL OR description NOT LIKE '[VOIDED:%')
        ORDER BY account_code
    ");
    $stmt->execute([$company_id]);
    $expense_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $total_expenses = array_sum(array_column($expense_items, 'balance'));

    // Calculate net income
    $net_income = $total_revenue - $total_expenses;

    // Calculate net profit margin
    $net_income_margin = $total_revenue > 0 ? ($net_income / $total_revenue) * 100 : 0;

    $response = [
        'success' => true,
        'message' => 'Income statement generated',
        'data' => [
            'company' => $company,
            'period' => [
                'start' => $start_date,
                'end' => $end_date
            ],
            'revenue' => [
                'items' => $revenue_items,
                'total' => $total_revenue
            ],
            'expenses' => [
                'items' => $expense_items,
                'total' => $total_expenses
            ],
            'net_income' => $net_income,
            'net_income_margin' => $net_income_margin
        ]
    ];

    echo json_encode($response);

} catch (PDOException $e) {
    error_log("Admin Income Statement Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}

