<?php
/**
 * Decline Transaction API
 * Declines a pending approval transaction and deletes it
 */

header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store, must-revalidate');

session_start();

// Check admin authentication
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$transaction_id = $data['id'] ?? null;
$decline_reason = trim($data['reason'] ?? '');

if (!$transaction_id || empty($decline_reason)) {
    echo json_encode(['success' => false, 'message' => 'Transaction ID and reason required']);
    exit;
}

require_once __DIR__ . '/../../config/database.php';

try {
    $pdo = Database::getInstance()->getConnection();
    $pdo->beginTransaction();

    $admin_id = $_SESSION['user_id'];

    // Get transaction details
    // Note: Transactions requiring approval have status_id=1 (Pending) with requires_approval=1
    // There is NO status_id=4 in the database!
    $stmt = $pdo->prepare("
        SELECT t.*, c.company_name, u.full_name as created_by_name
        FROM transactions t
        JOIN companies c ON t.company_id = c.id
        JOIN users u ON t.created_by = u.id
        WHERE t.id = ? AND t.status_id = 1 AND t.requires_approval = 1
    ");
    $stmt->execute([$transaction_id]);
    $transaction = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$transaction) {
        throw new Exception('Transaction not found or not pending approval');
    }

    // Log the decline reason
    $stmt = $pdo->prepare("
        UPDATE transactions
        SET declined_by = ?,
            declined_at = NOW(),
            decline_reason = ?
        WHERE id = ?
    ");
    $stmt->execute([$admin_id, $decline_reason, $transaction_id]);

    // Log activity before deleting
    $stmt = $pdo->prepare("
        INSERT INTO activity_logs (user_id, username, user_role, company_id, activity_type, details)
        VALUES (?, ?, 'admin', ?, 'transaction', ?)
    ");
    $stmt->execute([
        $admin_id,
        $_SESSION['username'] ?? 'admin',
        $transaction['company_id'],
        "Declined transaction: " . $transaction['transaction_number'] . " - Reason: " . $decline_reason
    ]);

    // Record decline history BEFORE deleting
    $stmt = $pdo->prepare("
        INSERT INTO approval_history (transaction_id, action, reason, reviewed_by, company_id, transaction_number, transaction_amount, created_by)
        VALUES (?, 'declined', ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $transaction_id,
        $decline_reason,
        $admin_id,
        $transaction['company_id'],
        $transaction['transaction_number'],
        $transaction['total_amount'],
        $transaction['created_by']
    ]);

    // Delete transaction (transaction_lines will cascade delete)
    $stmt = $pdo->prepare("DELETE FROM transactions WHERE id = ?");
    $stmt->execute([$transaction_id]);

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Transaction declined and removed successfully'
    ]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Decline transaction error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

