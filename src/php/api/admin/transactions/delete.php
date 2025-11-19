<?php
// Admin Transaction Delete API - Delete pending transaction
error_reporting(0);
ini_set('display_errors', 0);

header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store, must-revalidate');

session_start();

// Check admin authentication
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized - Admin access required']);
    exit;
}

require_once __DIR__ . '/../../../config/database.php';

// Get request data
$input = file_get_contents('php://input');
$data = json_decode($input, true);

// Check if JSON is valid
if ($data === null && json_last_error() !== JSON_ERROR_NONE) {
    echo json_encode(['success' => false, 'message' => 'Invalid JSON: ' . json_last_error_msg()]);
    exit;
}

if (empty($data['id'])) {
    echo json_encode(['success' => false, 'message' => 'Transaction ID required']);
    exit;
}

$transaction_id = (int)$data['id'];

try {
    $pdo = Database::getInstance()->getConnection();
    $pdo->beginTransaction();

    // Get transaction details
    $stmt = $pdo->prepare("
        SELECT t.id, t.transaction_number, t.status_id, t.company_id, c.company_name
        FROM transactions t
        JOIN companies c ON t.company_id = c.id
        WHERE t.id = ?
    ");
    $stmt->execute([$transaction_id]);
    $transaction = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$transaction) {
        echo json_encode(['success' => false, 'message' => 'Transaction not found']);
        exit;
    }

    // Can only delete pending transactions (status_id = 1)
    if ($transaction['status_id'] != 1) {
        echo json_encode(['success' => false, 'message' => 'Only pending transactions can be deleted']);
        exit;
    }

    // Delete transaction lines first (foreign key constraint)
    $stmt = $pdo->prepare("DELETE FROM transaction_lines WHERE transaction_id = ?");
    $stmt->execute([$transaction_id]);

    // Delete transaction
    $stmt = $pdo->prepare("DELETE FROM transactions WHERE id = ?");
    $stmt->execute([$transaction_id]);

    // Log activity
    $stmt = $pdo->prepare("
        INSERT INTO activity_logs (user_id, username, user_role, company_id, activity_type, details)
        VALUES (?, ?, 'admin', ?, 'transaction', ?)
    ");
    $stmt->execute([
        $_SESSION['user_id'],
        $_SESSION['username'] ?? 'admin',
        $transaction['company_id'],
        "Deleted pending transaction: {$transaction['transaction_number']} for company {$transaction['company_name']}"
    ]);

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Transaction deleted successfully',
        'data' => [
            'id' => $transaction_id,
            'transaction_number' => $transaction['transaction_number']
        ]
    ]);

} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Admin transaction delete error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}

