<?php
/**
 * Dashboard Recent Transactions API
 * Returns recent transactions for tenant company
 * Aligned with: QUERIES.md Query 4.1
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
    $limit = isset($_GET['limit']) ? max(1, min(50, (int)$_GET['limit'])) : 10;
    // Get recent transactions - Aligned with QUERIES.md
    $sql = "SELECT
                t.id,
                t.transaction_number,
                t.transaction_date,
                t.description,
                t.status_id,
                ts.status_name,
                COALESCE(SUM(CASE WHEN tl.line_type = 'debit' THEN tl.amount ELSE 0 END), 0) as total_amount
            FROM transactions t
            JOIN transaction_statuses ts ON t.status_id = ts.id
            LEFT JOIN transaction_lines tl ON t.id = tl.transaction_id
            WHERE t.company_id = :company_id
            GROUP BY t.id, t.transaction_number, t.transaction_date, t.description, 
                     t.status_id, ts.status_name
            ORDER BY t.transaction_date DESC, t.id DESC
            LIMIT :limit";
    $stmt = $db->prepare($sql);
    $stmt->bindValue(':company_id', $companyId, PDO::PARAM_INT);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    respond(true, 'Recent transactions loaded', $transactions, 200);
} catch (Throwable $e) {
    error_log('Dashboard recent transactions error: ' . $e->getMessage());
    respond(false, 'Server error: ' . $e->getMessage(), null, 500);
}
