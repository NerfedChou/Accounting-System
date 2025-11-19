<?php
/**
 * Get Pending Approvals API
 * Returns all transactions with status = 4 (Pending Approval)
 */

header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store, must-revalidate');

session_start();

// Check admin authentication
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

require_once __DIR__ . '/../../config/database.php';

try {
    $pdo = Database::getInstance()->getConnection();

    // Get all transactions with status 4 (Pending Approval)
    $stmt = $pdo->prepare("
        SELECT
            t.id,
            t.transaction_number,
            t.transaction_date,
            t.description,
            t.total_amount,
            t.created_at,
            t.requires_approval,
            c.company_name,
            u.full_name as created_by_name,
            'This transaction creates a rare accounting scenario (e.g., positive equity) that requires admin review for financial integrity.' as approval_reason
        FROM transactions t
        JOIN companies c ON t.company_id = c.id
        JOIN users u ON t.created_by = u.id
        WHERE t.status_id = 4 AND t.requires_approval = 1
        ORDER BY t.created_at DESC
    ");

    $stmt->execute();
    $approvals = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'data' => $approvals,
        'count' => count($approvals)
    ]);

} catch (PDOException $e) {
    error_log("Pending approvals error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}

