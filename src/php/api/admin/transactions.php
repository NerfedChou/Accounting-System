<?php
/**
 * Admin Transactions API
 * Returns all transactions from all companies
 * Aligned with: USE-CASE-DIAGRAM.md UC-A04
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
    // Check authentication - Aligned with: DATABASE.md users.role
    if (!isset($_SESSION['user_id'])) respond(false, 'Unauthorized', null, 401);
    if ($_SESSION['role'] !== 'admin') respond(false, 'Admin access required', null, 403);

    $db = Database::getInstance()->getConnection();
    $limit = isset($_GET['limit']) ? max(1, min(100, (int)$_GET['limit'])) : 50;

    // Query all transactions from all companies - Aligned with: QUERIES.md Query 4.1
    $sql = "SELECT
                t.id,
                t.transaction_number,
                t.transaction_date,
                t.description,
                t.status_id,
                ts.status_name,
                c.company_name,
                u.full_name as created_by_name,
                COALESCE(SUM(tl.amount), 0) as total_amount
            FROM transactions t
            JOIN transaction_statuses ts ON t.status_id = ts.id
            LEFT JOIN companies c ON t.company_id = c.id
            LEFT JOIN users u ON t.created_by = u.id
            LEFT JOIN transaction_lines tl ON t.id = tl.transaction_id AND tl.line_type = 'debit'
            GROUP BY t.id, t.transaction_number, t.transaction_date, t.description,
                     t.status_id, ts.status_name, c.company_name, u.full_name
            ORDER BY t.transaction_date DESC, t.id DESC
            LIMIT :limit";

    $stmt = $db->prepare($sql);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Format data
    foreach ($transactions as &$transaction) {
        $transaction['id'] = (int)$transaction['id'];
        $transaction['status_id'] = (int)$transaction['status_id'];
        $transaction['total_amount'] = number_format((float)$transaction['total_amount'], 2, '.', '');
    }

    respond(true, 'Transactions loaded', $transactions, 200);

} catch (Throwable $e) {
    error_log('Admin transactions error: ' . $e->getMessage());
    respond(false, 'Server error: ' . $e->getMessage(), null, 500);
}

