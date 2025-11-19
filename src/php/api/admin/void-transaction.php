<?php
/**
 * Admin Void Transaction API
 * Void a posted transaction (admin only) with CASCADE support
 *
 * CRITICAL OPERATION: This reverses balances and can cascade to related transactions
 * Aligned with: SELF.md Void Transaction Logic
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: application/json');
session_start();
require_once __DIR__ . '/../../config/database.php';

function respond($ok, $msg = '', $data = null, $code = 200) {
    http_response_code($code);
    echo json_encode(['success' => $ok, 'message' => $msg, 'data' => $data]);
    exit;
}

/**
 * Find all transactions that depend on accounts affected by voiding this transaction
 */
function findRelatedTransactions($db, $transactionId) {
    // NOTE: In a simple double-entry system without parent-child transaction relationships,
    // cascade voiding is NOT needed. Each transaction is independent.
    //
    // Cascade would only be needed if you have:
    // - Parent-child transaction relationships (e.g., invoice -> payments)
    // - Transactions that explicitly reference other transactions
    // - Business logic requiring dependent transactions to be voided together
    //
    // Since this system doesn't have those features, we return empty array.
    // Users can void transactions independently without cascade.

    // Future: If you add parent_transaction_id column to transactions table,
    // you would query for children here:
    // SELECT * FROM transactions WHERE parent_transaction_id = :tid AND status_id = 2

    return []; // No cascade needed for independent transactions
}

/**
 * Void a single transaction (reverse balances and mark as voided)
 */
function voidSingleTransaction($db, $transactionId, $voidReason, $voidedBy) {
    // Get transaction lines
    $stmt = $db->prepare("SELECT * FROM transaction_lines WHERE transaction_id = :tid");
    $stmt->execute([':tid' => $transactionId]);
    $lines = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get account type for each line to properly reverse balance
    foreach ($lines as $line) {
        $accountId = $line['account_id'];
        $amount = (float)$line['amount'];
        $lineType = $line['line_type'];

        // Get account type to determine how to reverse
        $stmt = $db->prepare("
            SELECT a.id, a.account_type_id, at.type_name
            FROM accounts a
            JOIN account_types at ON a.account_type_id = at.id
            WHERE a.id = :id
        ");
        $stmt->execute([':id' => $accountId]);
        $account = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$account) continue; // Skip if account was deleted

        $accountType = $account['type_name'];

        // Calculate reversal based on account type and original line type
        // Assets & Expenses: Debit increases, Credit decreases
        // Liabilities, Equity, Revenue: Credit increases, Debit decreases
        if ($accountType === 'Asset' || $accountType === 'Expense') {
            // If original was debit (increased balance), subtract to reverse
            // If original was credit (decreased balance), add to reverse
            $balanceChange = ($lineType === 'debit') ? -$amount : +$amount;
        } else {
            // Liability, Equity, Revenue
            // If original was credit (increased balance), subtract to reverse
            // If original was debit (decreased balance), add to reverse
            $balanceChange = ($lineType === 'credit') ? -$amount : +$amount;
        }

        $stmt = $db->prepare("
            UPDATE accounts
            SET current_balance = current_balance + :change
            WHERE id = :id
        ");
        $stmt->execute([':change' => $balanceChange, ':id' => $accountId]);
    }

    // Mark transaction as voided
    $stmt = $db->prepare("
        UPDATE transactions
        SET status_id = 3,
            voided_by = :voided_by,
            voided_at = NOW(),
            void_reason = :void_reason
        WHERE id = :id
    ");
    $stmt->execute([
        ':voided_by' => $voidedBy,
        ':void_reason' => $voidReason,
        ':id' => $transactionId
    ]);
}

try {
    // Check authentication - Admin only
    if (!isset($_SESSION['user_id'])) respond(false, 'Unauthorized', null, 401);
    if ($_SESSION['role'] !== 'admin') respond(false, 'Admin access required', null, 403);

    // Get POST data
    $input = json_decode(file_get_contents('php://input'), true);
    $transactionId = isset($input['transaction_id']) ? (int)$input['transaction_id'] : 0;
    $voidReason = isset($input['void_reason']) ? trim($input['void_reason']) : '';
    $cascadeConfirmed = isset($input['cascade_confirmed']) ? (bool)$input['cascade_confirmed'] : false;

    if ($transactionId <= 0) {
        respond(false, 'Invalid transaction ID', null, 400);
    }

    if (empty($voidReason)) {
        respond(false, 'Void reason is required', null, 400);
    }

    $db = Database::getInstance()->getConnection();

    // Get transaction details - must be Posted (status_id = 2)
    $stmt = $db->prepare("SELECT * FROM transactions WHERE id = :id AND status_id = 2");
    $stmt->execute([':id' => $transactionId]);
    $transaction = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$transaction) {
        respond(false, 'Transaction not found or not posted', null, 404);
    }

    // Find related transactions that would be affected
    $relatedTransactions = findRelatedTransactions($db, $transactionId);

    // If there are related transactions and cascade not confirmed, ask for confirmation
    if (!empty($relatedTransactions) && !$cascadeConfirmed) {
        respond(false, 'Cascade void required', [
            'requires_cascade' => true,
            'affected_transactions' => $relatedTransactions,
            'warning' => 'Voiding this transaction will affect ' . count($relatedTransactions) . ' related transaction(s). All will be voided together.',
            'message' => 'Please confirm cascade void operation'
        ], 200);
    }

    // Begin transaction
    $db->beginTransaction();

    try {
        // Void all related transactions first (in reverse chronological order)
        if (!empty($relatedTransactions)) {
            foreach (array_reverse($relatedTransactions) as $related) {
                voidSingleTransaction(
                    $db,
                    $related['id'],
                    "CASCADE VOID: Related to {$transaction['transaction_number']} - {$voidReason}",
                    $_SESSION['user_id']
                );

                // Log cascade void
                $stmt = $db->prepare("
                    INSERT INTO activity_logs (user_id, username, user_role, company_id, activity_type, details)
                    VALUES (?, ?, 'admin', ?, 'void', ?)
                ");
                $stmt->execute([
                    $_SESSION['user_id'],
                    $_SESSION['username'] ?? 'admin',
                    $transaction['company_id'],
                    "CASCADE VOID: {$related['transaction_number']} (related to {$transaction['transaction_number']})"
                ]);
            }
        }

        // Void the main transaction
        voidSingleTransaction($db, $transactionId, $voidReason, $_SESSION['user_id']);

        // Log the main void action
        $stmt = $db->prepare("
            INSERT INTO activity_logs (user_id, username, user_role, company_id, activity_type, details)
            VALUES (?, ?, 'admin', ?, 'void', ?)
        ");
        $stmt->execute([
            $_SESSION['user_id'],
            $_SESSION['username'] ?? 'admin',
            $transaction['company_id'],
            "VOID: {$transaction['transaction_number']} - Reason: {$voidReason}"
        ]);

        $db->commit();

        respond(true, 'Transaction voided successfully', [
            'transaction_id' => $transactionId,
            'transaction_number' => $transaction['transaction_number'],
            'voided_count' => 1 + count($relatedTransactions),
            'cascade_voided' => $relatedTransactions
        ], 200);

    } catch (Exception $e) {
        $db->rollBack();
        throw $e;
    }

} catch (Throwable $e) {
    if (isset($db) && $db->inTransaction()) $db->rollBack();
    error_log('Void transaction error: ' . $e->getMessage());
    respond(false, 'Server error: ' . $e->getMessage(), null, 500);
}

