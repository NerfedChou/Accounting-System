<?php
/**
 * Get Transaction Details API
 * Returns full transaction details including lines
 */

header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store, must-revalidate');

session_start();

// Check admin authentication
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$transaction_id = $_GET['id'] ?? null;

if (!$transaction_id) {
    echo json_encode(['success' => false, 'message' => 'Transaction ID required']);
    exit;
}

require_once __DIR__ . '/../../config/database.php';

try {
    $pdo = Database::getInstance()->getConnection();

    // Get transaction details
    $stmt = $pdo->prepare("
        SELECT
            t.*,
            c.company_name,
            u.full_name as created_by_name
        FROM transactions t
        JOIN companies c ON t.company_id = c.id
        JOIN users u ON t.created_by = u.id
        WHERE t.id = ?
    ");

    $stmt->execute([$transaction_id]);
    $transaction = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$transaction) {
        echo json_encode(['success' => false, 'message' => 'Transaction not found']);
        exit;
    }

    // Get transaction lines
    $stmt = $pdo->prepare("
        SELECT
            tl.*,
            a.account_code,
            a.account_name,
            at.type_name
        FROM transaction_lines tl
        JOIN accounts a ON tl.account_id = a.id
        JOIN account_types at ON a.account_type_id = at.id
        WHERE tl.transaction_id = ?
        ORDER BY tl.line_order
    ");

    $stmt->execute([$transaction_id]);
    $lines = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $transaction['lines'] = $lines;
    $transaction['approval_reason'] = 'This transaction would create a rare accounting scenario (e.g., positive equity) that requires admin review.';

    echo json_encode([
        'success' => true,
        'data' => $transaction
    ]);

} catch (PDOException $e) {
    error_log("Transaction details error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}

