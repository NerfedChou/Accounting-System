<?php
// Transaction Delete API (Only for pending transactions)
header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store, must-revalidate');

session_start();

// Check authentication - if role not in session, check database
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized - Please login']);
    exit;
}

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'tenant') {
    // Role not in session or not tenant - check database
    require_once __DIR__ . '/../../config/database.php';
    $pdo = Database::getInstance()->getConnection();
    $stmt = $pdo->prepare("SELECT role, company_id FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user || $user['role'] !== 'tenant') {
        echo json_encode(['success' => false, 'message' => 'Unauthorized - Tenant access required']);
        exit;
    }

    // Update session with role and company_id
    $_SESSION['role'] = 'tenant';
    $_SESSION['company_id'] = $user['company_id'];
}

require_once __DIR__ . '/../../config/database.php';

// Get transaction ID from JSON POST body or query string
$transaction_id = 0;

// Try to get from JSON body first
$json = file_get_contents('php://input');
if ($json) {
    $data = json_decode($json, true);
    if (isset($data['id'])) {
        $transaction_id = (int)$data['id'];
    }
    // Debug logging (remove in production)
    error_log("[DELETE API] Received JSON: " . $json);
    error_log("[DELETE API] Parsed transaction ID: " . $transaction_id);
}

// Fallback to POST or GET
if (!$transaction_id) {
    $transaction_id = isset($_POST['id']) ? (int)$_POST['id'] : (isset($_GET['id']) ? (int)$_GET['id'] : 0);
    if ($transaction_id) {
        error_log("[DELETE API] Got ID from POST/GET: " . $transaction_id);
    }
}

if (!$transaction_id) {
    error_log("[DELETE API] ERROR: No transaction ID provided");
    echo json_encode(['success' => false, 'message' => 'Transaction ID required']);
    exit;
}

$company_id = $_SESSION['company_id'];
$user_id = $_SESSION['user_id'];

try {
    $pdo = Database::getInstance()->getConnection();

    // Check if transaction exists and is pending (status_id = 1)
    $stmt = $pdo->prepare("
        SELECT t.id, t.transaction_number, t.status_id, t.requires_approval
        FROM transactions t
        WHERE t.id = ? AND t.company_id = ?
    ");
    $stmt->execute([$transaction_id, $company_id]);
    $transaction = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$transaction) {
        echo json_encode(['success' => false, 'message' => 'Transaction not found']);
        exit;
    }

    // Only pending transactions (status_id = 1) can be deleted
    if ($transaction['status_id'] != 1) {
        echo json_encode(['success' => false, 'message' => 'Only pending transactions can be deleted']);
        exit;
    }

    // CRITICAL SECURITY CHECK: Tenants cannot delete transactions requiring admin approval
    if ($transaction['requires_approval'] == 1) {
        echo json_encode([
            'success' => false,
            'message' => 'This transaction requires admin approval and cannot be deleted by tenants',
            'details' => 'Please wait for admin review or contact your administrator to withdraw this transaction.'
        ]);
        exit;
    }

    $pdo->beginTransaction();

    // Delete transaction lines first
    $stmt = $pdo->prepare("DELETE FROM transaction_lines WHERE transaction_id = ?");
    $stmt->execute([$transaction_id]);

    // Delete transaction
    $stmt = $pdo->prepare("DELETE FROM transactions WHERE id = ?");
    $stmt->execute([$transaction_id]);

    // Log activity
    $stmt = $pdo->prepare("
        INSERT INTO activity_logs (user_id, username, user_role, company_id, activity_type, details)
        VALUES (?, ?, 'tenant', ?, 'transaction', ?)
    ");
    $stmt->execute([
        $user_id,
        $_SESSION['username'] ?? 'unknown',
        $company_id,
        "Deleted pending transaction: " . $transaction['transaction_number']
    ]);

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Transaction deleted successfully'
    ]);

} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Transaction delete error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}

