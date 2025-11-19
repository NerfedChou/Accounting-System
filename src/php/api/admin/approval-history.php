<?php
/**
 * Approval History API
 * Returns approval/decline history with filtering
 */

header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store, must-revalidate');

session_start();

// Check admin authentication
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$filter = $_GET['filter'] ?? 'all'; // all, approved, declined, pending

require_once __DIR__ . '/../../config/database.php';

try {
    $pdo = Database::getInstance()->getConnection();

    // Build query based on filter
    $whereClause = '';
    if ($filter === 'approved') {
        $whereClause = "WHERE ah.action = 'approved'";
    } elseif ($filter === 'declined') {
        $whereClause = "WHERE ah.action = 'declined'";
    } elseif ($filter === 'pending') {
        // Show current pending transactions that require approval
        // Note: status_id=1 (Pending) with requires_approval=1, NOT status_id=4!
        $stmt = $pdo->prepare("
            SELECT
                t.id,
                t.id as transaction_id,
                t.transaction_number,
                t.transaction_date,
                t.description,
                t.total_amount,
                t.created_at,
                t.requires_approval,
                c.company_name,
                u.full_name as created_by_name,
                'pending' as action,
                'This transaction creates a rare but valid accounting scenario that requires admin oversight.' as reason,
                NULL as reviewed_at,
                NULL as reviewed_by_name
            FROM transactions t
            JOIN companies c ON t.company_id = c.id
            JOIN users u ON t.created_by = u.id
            WHERE t.status_id = 1 AND t.requires_approval = 1
            ORDER BY t.created_at DESC
        ");
        $stmt->execute();
        $history = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode([
            'success' => true,
            'data' => $history,
            'count' => count($history)
        ]);
        exit;
    }

    // Get history for approved/declined/all
    $stmt = $pdo->prepare("
        SELECT
            ah.id,
            ah.transaction_id,
            ah.transaction_number,
            ah.action,
            ah.reason,
            ah.reviewed_at,
            ah.transaction_amount as total_amount,
            c.company_name,
            reviewer.full_name as reviewed_by_name,
            creator.full_name as created_by_name
        FROM approval_history ah
        JOIN companies c ON ah.company_id = c.id
        JOIN users reviewer ON ah.reviewed_by = reviewer.id
        JOIN users creator ON ah.created_by = creator.id
        $whereClause
        ORDER BY ah.reviewed_at DESC
    ");

    $stmt->execute();
    $history = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'data' => $history,
        'count' => count($history)
    ]);

} catch (PDOException $e) {
    error_log("Approval history error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}

