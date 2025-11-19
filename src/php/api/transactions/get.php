<?php
/**
 * Get Transaction Details API
 * Returns transaction with all lines
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

    $transactionId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    if ($transactionId <= 0) respond(false, 'Invalid transaction ID', null, 400);
    $companyId = $_SESSION['company_id'];
    // Get transaction
    $sql = "SELECT t.*, ts.status_name
            FROM transactions t
            JOIN transaction_statuses ts ON t.status_id = ts.id
            WHERE t.id = :id AND t.company_id = :company_id";
    $stmt = $db->prepare($sql);
    $stmt->execute([':id' => $transactionId, ':company_id' => $companyId]);
    $transaction = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$transaction) respond(false, 'Transaction not found', null, 404);
    // Get transaction lines
    $sql = "SELECT tl.*, a.account_code, a.account_name
            FROM transaction_lines tl
            JOIN accounts a ON tl.account_id = a.id
            WHERE tl.transaction_id = :transaction_id
            ORDER BY tl.line_order";
    $stmt = $db->prepare($sql);
    $stmt->execute([':transaction_id' => $transactionId]);
    $transaction['lines'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    respond(true, 'Transaction loaded', $transaction, 200);
} catch (Throwable $e) {
    error_log('Get transaction error: ' . $e->getMessage());
    respond(false, 'Server error: ' . $e->getMessage(), null, 500);
}
