<?php
/**
 * Income Statement Report API
 * Generates Income Statement for a date range
 * Aligned with: QUERIES.md Query 2.2 (Net Income Calculation)
 *
 * Income Statement Format:
 * Net Income = Revenue - Expenses
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
    // Check authentication
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
    $dateFrom = isset($_GET['start']) ? $_GET['start'] : (isset($_GET['date_from']) ? $_GET['date_from'] : date('Y-01-01'));
    $dateTo = isset($_GET['end']) ? $_GET['end'] : (isset($_GET['date_to']) ? $_GET['date_to'] : date('Y-m-d'));


    // Get company info
    $stmt = $db->prepare("SELECT * FROM companies WHERE id = ?");
    $stmt->execute([$companyId]);
    $company = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$company) {
        respond(false, 'Company not found', null, 404);
    }

    // Get Revenue - Use current_balance from accounts
    // EXCLUDE external accounts AND voided accounts
    $sql = "SELECT account_code, account_name, current_balance as balance
            FROM accounts
            WHERE company_id = ?
              AND account_type_id = 4
              AND is_active = 1
              AND is_system_account = 0
              AND (description IS NULL OR description NOT LIKE '[VOIDED:%')
            ORDER BY account_code";

    $stmt = $db->prepare($sql);
    $stmt->execute([$companyId]);
    $revenue = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $totalRevenue = array_sum(array_column($revenue, 'balance'));

    // Get Expenses - Use current_balance from accounts
    // EXCLUDE external accounts AND voided accounts
    $sql = "SELECT account_code, account_name, current_balance as balance
            FROM accounts
            WHERE company_id = ?
              AND account_type_id = 5
              AND is_active = 1
              AND is_system_account = 0
              AND (description IS NULL OR description NOT LIKE '[VOIDED:%')
            ORDER BY account_code";

    $stmt = $db->prepare($sql);
    $stmt->execute([$companyId]);
    $expenses = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $totalExpenses = array_sum(array_column($expenses, 'balance'));

    // Calculate Net Income
    $netIncome = $totalRevenue - $totalExpenses;

    // Calculate net profit margin
    $net_income_margin = $totalRevenue > 0 ? ($netIncome / $totalRevenue) * 100 : 0;

    respond(true, 'Income Statement generated', [
        'company' => $company,
        'period' => [
            'start' => $dateFrom,
            'end' => $dateTo
        ],
        'revenue' => [
            'items' => $revenue,
            'total' => $totalRevenue
        ],
        'expenses' => [
            'items' => $expenses,
            'total' => $totalExpenses
        ],
        'net_income' => $netIncome,
        'net_income_margin' => $net_income_margin
    ], 200);

} catch (Throwable $e) {
    error_log('Income Statement error: ' . $e->getMessage());
    respond(false, 'Server error: ' . $e->getMessage(), null, 500);
}

