<?php
/**
 * Admin: Reactivate Account
 * Sets account is_active to TRUE
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
$account_id = $data['id'] ?? null;

if (!$account_id) {
    echo json_encode(['success' => false, 'message' => 'Account ID required']);
    exit;
}

require_once __DIR__ . '/../../../config/database.php';

try {
    $pdo = Database::getInstance()->getConnection();
    $pdo->beginTransaction();

    $admin_id = $_SESSION['user_id'];

    // Get account details
    $stmt = $pdo->prepare("
        SELECT a.*, c.company_name, at.type_name
        FROM accounts a
        JOIN companies c ON a.company_id = c.id
        JOIN account_types at ON a.account_type_id = at.id
        WHERE a.id = ?
    ");
    $stmt->execute([$account_id]);
    $account = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$account) {
        throw new Exception('Account not found');
    }

    // Reactivate account
    $stmt = $pdo->prepare("
        UPDATE accounts
        SET is_active = TRUE
        WHERE id = ?
    ");
    $stmt->execute([$account_id]);

    // Log activity
    $stmt = $pdo->prepare("
        INSERT INTO activity_logs (user_id, username, user_role, company_id, activity_type, details)
        VALUES (?, ?, 'admin', ?, 'other', ?)
    ");
    $stmt->execute([
        $admin_id,
        $_SESSION['username'] ?? 'admin',
        $account['company_id'],
        "[ACCOUNT MGMT] Reactivated account: {$account['account_code']} - {$account['account_name']}"
    ]);

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Account reactivated successfully'
    ]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Reactivate account error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>

