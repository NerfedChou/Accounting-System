<?php
/**
 * Admin: Void Account
 * Marks account as voided (permanent) and voids all related transactions
 *
 * CRITICAL OPERATION: Voiding an account voids ALL transactions using it
 * Aligned with: SELF.md Void Account Logic
 */

header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store, must-revalidate');

session_start();

// Debug logging
error_log("VOID API CALLED - Session ID: " . session_id());
error_log("Session data: " . print_r($_SESSION, true));
error_log("POST data: " . file_get_contents('php://input'));

// Check admin authentication
// If role is not in session, check database (for old sessions)
if (!isset($_SESSION['user_id'])) {
    error_log("VOID API: No user_id in session");
    echo json_encode(['success' => false, 'message' => 'Unauthorized - Please login as admin']);
    exit;
}

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    // Role not in session or not admin - check database to be sure
    error_log("VOID API: Role check - role in session: " . ($_SESSION['role'] ?? 'NOT SET'));

    require_once __DIR__ . '/../../../config/database.php';
    $pdo = Database::getInstance()->getConnection();
    $stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user || $user['role'] !== 'admin') {
        error_log("VOID API: User is not admin in database");
        echo json_encode(['success' => false, 'message' => 'Unauthorized - Admin access required. Please logout and login again.']);
        exit;
    }

    // Update session with role
    $_SESSION['role'] = 'admin';
    error_log("VOID API: Updated session with admin role from database");
}

$data = json_decode(file_get_contents('php://input'), true);
$account_id = $data['id'] ?? null;
$reason = trim($data['reason'] ?? '');
$cascadeConfirmed = isset($data['cascade_confirmed']) ? (bool)$data['cascade_confirmed'] : false;

error_log("VOID API: Parsed data - account_id: " . ($account_id ?? 'NULL') . ", reason: '" . $reason . "', cascade: " . ($cascadeConfirmed ? 'true' : 'false'));

if (!$account_id || empty($reason)) {
    error_log("VOID API: FAILED VALIDATION - account_id: " . var_export($account_id, true) . ", reason empty: " . (empty($reason) ? 'YES' : 'NO'));
    echo json_encode(['success' => false, 'message' => 'Account ID and reason required', 'debug' => [
        'account_id' => $account_id,
        'reason' => $reason,
        'reason_length' => strlen($reason)
    ]]);
    exit;
}

require_once __DIR__ . '/../../../config/database.php';

try {
    $pdo = Database::getInstance()->getConnection();
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

    // Cannot void system/external accounts
    if (isset($account['is_system_account']) && $account['is_system_account'] == 1) {
        throw new Exception('Cannot void system accounts - these are required for system operation');
    }

    // Check for ALL transactions using this account (both pending and posted)
    $stmt = $pdo->prepare("
        SELECT t.id, t.transaction_number, t.transaction_date, t.description,
               ts.status_name, t.status_id
        FROM transaction_lines tl
        JOIN transactions t ON tl.transaction_id = t.id
        JOIN transaction_statuses ts ON t.status_id = ts.id
        WHERE tl.account_id = ?
        ORDER BY t.transaction_date DESC, t.id DESC
    ");
    $stmt->execute([$account_id]);
    $affectedTransactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Separate pending and posted transactions
    $pendingTransactions = array_filter($affectedTransactions, fn($t) => $t['status_id'] == 1);
    $postedTransactions = array_filter($affectedTransactions, fn($t) => $t['status_id'] == 2);
    $totalAffected = count($affectedTransactions);

    // If there are transactions and cascade not confirmed, ask for confirmation
    if ($totalAffected > 0 && !$cascadeConfirmed) {
        echo json_encode([
            'success' => false,
            'requires_cascade' => true,
            'affected_transactions' => [
                'pending' => array_values($pendingTransactions),
                'posted' => array_values($postedTransactions),
                'total' => $totalAffected
            ],
            'warning' => "This account has {$totalAffected} transaction(s). " .
                        count($pendingTransactions) . " pending will be DELETED. " .
                        count($postedTransactions) . " posted will be VOIDED.",
            'message' => 'Please confirm cascade void operation'
        ]);
        exit;
    }

    // Begin transaction
    $pdo->beginTransaction();

    // Delete pending transactions (they haven't affected balances yet)
    if (!empty($pendingTransactions)) {
        $pendingIds = array_column($pendingTransactions, 'id');
        $placeholders = implode(',', array_fill(0, count($pendingIds), '?'));

        // Delete transaction lines first
        $stmt = $pdo->prepare("DELETE FROM transaction_lines WHERE transaction_id IN ($placeholders)");
        $stmt->execute($pendingIds);

        // Delete transactions
        $stmt = $pdo->prepare("DELETE FROM transactions WHERE id IN ($placeholders)");
        $stmt->execute($pendingIds);
    }

    // Void posted transactions (need to reverse balances)
    if (!empty($postedTransactions)) {
        foreach ($postedTransactions as $transaction) {
            // Get transaction lines
            $stmt = $pdo->prepare("SELECT * FROM transaction_lines WHERE transaction_id = ?");
            $stmt->execute([$transaction['id']]);
            $lines = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Reverse balances for each line
            foreach ($lines as $line) {
                $lineAccountId = $line['account_id'];
                $amount = (float)$line['amount'];
                $lineType = $line['line_type'];

                // Get account type
                $stmt = $pdo->prepare("
                    SELECT a.account_type_id, at.type_name
                    FROM accounts a
                    JOIN account_types at ON a.account_type_id = at.id
                    WHERE a.id = ?
                ");
                $stmt->execute([$lineAccountId]);
                $lineAccount = $stmt->fetch(PDO::FETCH_ASSOC);

                if (!$lineAccount) continue;

                $accountType = $lineAccount['type_name'];

                // Calculate reversal
                if ($accountType === 'Asset' || $accountType === 'Expense') {
                    $balanceChange = ($lineType === 'debit') ? -$amount : +$amount;
                } else {
                    $balanceChange = ($lineType === 'credit') ? -$amount : +$amount;
                }

                $stmt = $pdo->prepare("
                    UPDATE accounts
                    SET current_balance = current_balance + ?
                    WHERE id = ?
                ");
                $stmt->execute([$balanceChange, $lineAccountId]);
            }

            // Mark transaction as voided
            $stmt = $pdo->prepare("
                UPDATE transactions
                SET status_id = 3,
                    voided_by = ?,
                    voided_at = NOW(),
                    void_reason = ?
                WHERE id = ?
            ");
            $voidMsg = "CASCADE VOID: Account {$account['account_code']} voided - {$reason}";
            $stmt->execute([$admin_id, $voidMsg, $transaction['id']]);

            // Log each voided transaction
            $stmt = $pdo->prepare("
                INSERT INTO activity_logs (user_id, username, user_role, company_id, activity_type, details)
                VALUES (?, ?, 'admin', ?, 'void', ?)
            ");
            $stmt->execute([
                $admin_id,
                $_SESSION['username'] ?? 'admin',
                $account['company_id'],
                "CASCADE VOID Transaction: {$transaction['transaction_number']} (account {$account['account_code']} voided)"
            ]);
        }
    }

    // Mark account as voided (deactivate + add VOIDED to description)
    $voided_description = "[VOIDED: {$reason}] " . ($account['description'] ?? '');

    $stmt = $pdo->prepare("
        UPDATE accounts
        SET is_active = FALSE,
            description = ?,
            current_balance = 0.00
        WHERE id = ?
    ");
    $stmt->execute([$voided_description, $account_id]);

    // Log account void activity
    $stmt = $pdo->prepare("
        INSERT INTO activity_logs (user_id, username, user_role, company_id, activity_type, details)
        VALUES (?, ?, 'admin', ?, 'void', ?)
    ");
    $stmt->execute([
        $admin_id,
        $_SESSION['username'] ?? 'admin',
        $account['company_id'],
        "VOID ACCOUNT: {$account['account_code']} - {$account['account_name']}. " .
        "Reason: {$reason}. Affected: {$totalAffected} transactions " .
        "(" . count($pendingTransactions) . " deleted, " . count($postedTransactions) . " voided)"
    ]);

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Account voided successfully',
        'voided_account' => $account['account_code'],
        'transactions_affected' => $totalAffected,
        'transactions_deleted' => count($pendingTransactions),
        'transactions_voided' => count($postedTransactions)
    ]);

} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Void account error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>

