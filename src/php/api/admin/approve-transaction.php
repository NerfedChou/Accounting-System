<?php
/**
 * Approve Transaction API
 * Posts a pending approval transaction
 */

header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store, must-revalidate');

session_start();

// DEBUG: Log request
error_log("=== APPROVE TRANSACTION REQUEST ===");
error_log("Session User ID: " . ($_SESSION['user_id'] ?? 'NOT SET'));
error_log("Session Role: " . ($_SESSION['role'] ?? 'NOT SET'));

// Check admin authentication
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    error_log("Auth check FAILED");
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$transaction_id = $data['id'] ?? null;

error_log("Request Data: " . json_encode($data));
error_log("Transaction ID: " . ($transaction_id ?? 'NULL'));

if (!$transaction_id) {
    error_log("No transaction ID provided");
    echo json_encode(['success' => false, 'message' => 'Transaction ID required']);
    exit;
}

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../utils/transaction_processor.php';

try {
    $pdo = Database::getInstance()->getConnection();
    error_log("Database connected");

    $pdo->beginTransaction();
    error_log("Transaction started");

    $admin_id = $_SESSION['user_id'];
    error_log("Admin ID: " . $admin_id);

    // Get transaction details
    error_log("Fetching transaction details...");
    $stmt = $pdo->prepare("
        SELECT t.*, c.company_name
        FROM transactions t
        JOIN companies c ON t.company_id = c.id
        WHERE t.id = ? AND t.requires_approval = 1 AND t.status_id = 1
    ");
    $stmt->execute([$transaction_id]);
    $transaction = $stmt->fetch(PDO::FETCH_ASSOC);

    error_log("Transaction fetch result: " . ($transaction ? 'FOUND' : 'NOT FOUND'));
    if (!$transaction) {
        error_log("Checking why transaction not found...");
        // Debug: check if transaction exists at all
        $debug_stmt = $pdo->prepare("SELECT id, status_id, requires_approval FROM transactions WHERE id = ?");
        $debug_stmt->execute([$transaction_id]);
        $debug = $debug_stmt->fetch();
        error_log("Debug check - Transaction: " . json_encode($debug));

        throw new Exception('Transaction not found or not pending approval');
    }

    error_log("Transaction found: " . $transaction['transaction_number']);

    // Get transaction lines
    $stmt = $pdo->prepare("SELECT * FROM transaction_lines WHERE transaction_id = ?");
    $stmt->execute([$transaction_id]);
    $lines = $stmt->fetchAll(PDO::FETCH_ASSOC);
    error_log("Transaction lines: " . count($lines));

    // Validate balances and calculate changes correctly
    foreach ($lines as $line) {
        $amount = (float)$line['amount'];

        // Get account details including normal_balance
        $stmt = $pdo->prepare("
            SELECT a.current_balance, a.account_name, at.id as type_id, at.type_name, at.normal_balance
            FROM accounts a
            JOIN account_types at ON a.account_type_id = at.id
            WHERE a.id = ?
        ");
        $stmt->execute([(int)$line['account_id']]);
        $account = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$account) {
            throw new Exception("Account not found: " . $line['account_id']);
        }

        // ⚙️ USE CENTRALIZED BALANCE CALCULATION
        $change = TransactionProcessor::calculateBalanceChange(
            $account['normal_balance'],
            $line['line_type'],
            $amount
        );

        $new_balance = $account['current_balance'] + $change;
        $type_id = $account['type_id'];

        // Note: We're approving positive equity, so skip that check
        // But still validate other account types
        $cannot_be_negative = [1, 2, 4, 5]; // Asset, Liability, Revenue, Expense

        if (in_array($type_id, $cannot_be_negative) && $new_balance < 0) {
            throw new Exception(
                "Cannot approve: " . $account['type_name'] . " account " .
                $account['account_name'] . " would have negative balance ($" .
                number_format($new_balance, 2) . ")"
            );
        }
    }

    error_log("Balance validation passed");

    // Update transaction status to Posted (2)
    error_log("Updating transaction status to Posted...");
    $stmt = $pdo->prepare("
        UPDATE transactions
        SET status_id = 2,
            posted_by = ?,
            posted_at = NOW(),
            approved_by = ?,
            approved_at = NOW()
        WHERE id = ?
    ");
    $stmt->execute([$admin_id, $admin_id, $transaction_id]);
    error_log("Transaction status updated");

    // Update account balances using correct normal balance logic
    error_log("Updating account balances...");
    foreach ($lines as $line) {
        $amount = (float)$line['amount'];

        // Get account's normal balance
        $stmt_nb = $pdo->prepare("
            SELECT at.normal_balance
            FROM accounts a
            JOIN account_types at ON a.account_type_id = at.id
            WHERE a.id = ?
        ");
        $stmt_nb->execute([(int)$line['account_id']]);
        $acc = $stmt_nb->fetch(PDO::FETCH_ASSOC);

        // ⚙️ USE CENTRALIZED BALANCE CALCULATION
        $change = TransactionProcessor::calculateBalanceChange(
            $acc['normal_balance'],
            $line['line_type'],
            $amount
        );

        // Update account balance
        $stmt = $pdo->prepare("
            UPDATE accounts
            SET current_balance = current_balance + ?
            WHERE id = ?
        ");
        $stmt->execute([$change, (int)$line['account_id']]);
        error_log("Updated account {$line['account_id']} by {$change}");
    }

    error_log("Account balances updated");

    // Log activity
    error_log("Logging activity...");
    $stmt = $pdo->prepare("
        INSERT INTO activity_logs (user_id, username, user_role, company_id, activity_type, details)
        VALUES (?, ?, 'admin', ?, 'transaction', ?)
    ");
    $stmt->execute([
        $admin_id,
        $_SESSION['username'] ?? 'admin',
        $transaction['company_id'],
        "Approved and posted transaction: " . $transaction['transaction_number'] . " (required admin approval)"
    ]);
    error_log("Activity logged");

    // Record approval history
    error_log("Recording approval history...");
    $stmt = $pdo->prepare("
        INSERT INTO approval_history (transaction_id, action, reason, reviewed_by, company_id, transaction_number, transaction_amount, created_by)
        VALUES (?, 'approved', 'Transaction approved by admin and posted to ledger', ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $transaction_id,
        $admin_id,
        $transaction['company_id'],
        $transaction['transaction_number'],
        $transaction['total_amount'],
        $transaction['created_by']
    ]);
    error_log("Approval history recorded");

    $pdo->commit();
    error_log("Transaction committed successfully");

    echo json_encode([
        'success' => true,
        'message' => 'Transaction approved and posted successfully'
    ]);

} catch (Exception $e) {
    error_log("ERROR: " . $e->getMessage());
    if ($pdo && $pdo->inTransaction()) {
        $pdo->rollBack();
        error_log("Transaction rolled back");
    }
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
