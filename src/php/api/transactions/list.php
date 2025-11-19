<?php
// Transaction List API
header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store, must-revalidate');

session_start();

// Check authentication
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'tenant') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

require_once __DIR__ . '/../../config/database.php';

$company_id = $_SESSION['company_id'];

try {
    $pdo = Database::getInstance()->getConnection();

    // Get all transactions for this company
    $stmt = $pdo->prepare("
        SELECT
            t.id,
            t.transaction_number,
            t.transaction_date,
            t.description,
            t.reference_number,
            t.total_amount,
            t.status_id,
            ts.status_name,
            ts.can_edit,
            ts.can_delete,
            t.created_by,
            u1.full_name as created_by_name,
            t.created_at,
            t.posted_by,
            u2.full_name as posted_by_name,
            t.posted_at
        FROM transactions t
        JOIN transaction_statuses ts ON t.status_id = ts.id
        LEFT JOIN users u1 ON t.created_by = u1.id
        LEFT JOIN users u2 ON t.posted_by = u2.id
        WHERE t.company_id = ?
        ORDER BY t.transaction_date DESC, t.created_at DESC
    ");

    $stmt->execute([$company_id]);
    $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'message' => 'Transactions loaded',
        'data' => $transactions
    ]);

} catch (PDOException $e) {
    error_log("Transaction list error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error']);
}

