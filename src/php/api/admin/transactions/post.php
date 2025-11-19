<?php
// Admin Transaction Post API - Post pending transaction
header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store, must-revalidate');

session_start();

// Check admin authentication
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized - Admin access required']);
    exit;
}

require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../utils/transaction_processor.php';

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
$user_id = $_SESSION['user_id'];

try {
    $pdo = Database::getInstance()->getConnection();
    $pdo->beginTransaction();

    // Get transaction details
    $stmt = $pdo->prepare("
        SELECT t.*, c.company_name
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

    // Can only post pending transactions (status_id = 1)
    if ($transaction['status_id'] != 1) {
        echo json_encode(['success' => false, 'message' => 'Only pending transactions can be posted']);
        exit;
    }

    // Get transaction lines
    $stmt = $pdo->prepare("
        SELECT tl.*, a.account_type_id, at.type_name, at.normal_balance, a.current_balance, a.account_name
        FROM transaction_lines tl
        JOIN accounts a ON tl.account_id = a.id
        JOIN account_types at ON a.account_type_id = at.id
        WHERE tl.transaction_id = ?
    ");
    $stmt->execute([$transaction_id]);
    $lines = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($lines)) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'Transaction has no lines']);
        exit;
    }

    // Validate accounting integrity before posting
    foreach ($lines as $line) {
        $amount = (float)$line['amount'];

        // ⚙️ USE CENTRALIZED BALANCE CALCULATION
        $change = TransactionProcessor::calculateBalanceChange(
            $line['normal_balance'],
            $line['line_type'],
            $amount
        );

        $new_balance = $line['current_balance'] + $change;
        $type_id = $line['account_type_id'];

        // Accounting Rule: Certain account types CANNOT have negative balances
        $cannot_be_negative = [1, 2, 4, 5]; // Asset, Liability, Revenue, Expense

        if (in_array($type_id, $cannot_be_negative) && $new_balance < 0) {
            $pdo->rollBack();
            echo json_encode([
                'success' => false,
                'message' => "Transaction violates accounting rules!",
                'details' => sprintf(
                    "%s accounts cannot have negative balances!\n\n" .
                    "Account: %s (%s)\n" .
                    "Current Balance: $%.2f\n" .
                    "Change: %s $%.2f\n" .
                    "Would Result In: $%.2f",
                    $line['type_name'],
                    $line['account_name'],
                    $line['type_name'],
                    $line['current_balance'],
                    $line['line_type'] === 'debit' ? '+' : '-',
                    $amount,
                    $new_balance
                )
            ]);
            exit;
        }
    }

    // Validation passed - update balances
    foreach ($lines as $line) {
        $amount = (float)$line['amount'];

        // ⚙️ USE CENTRALIZED BALANCE CALCULATION
        $change = TransactionProcessor::calculateBalanceChange(
            $line['normal_balance'],
            $line['line_type'],
            $amount
        );

        // Update balance
        $stmt = $pdo->prepare("UPDATE accounts SET current_balance = current_balance + ? WHERE id = ?");
        $stmt->execute([$change, $line['account_id']]);
    }

    // Update transaction status to Posted (status_id = 2)
    $stmt = $pdo->prepare("
        UPDATE transactions
        SET status_id = 2,
            posted_by = ?,
            posted_at = NOW()
        WHERE id = ?
    ");
    $stmt->execute([$user_id, $transaction_id]);

    // Log activity
    $stmt = $pdo->prepare("
        INSERT INTO activity_logs (user_id, username, user_role, company_id, activity_type, details)
        VALUES (?, ?, 'admin', ?, 'transaction', ?)
    ");
    $stmt->execute([
        $user_id,
        $_SESSION['username'] ?? 'admin',
        $transaction['company_id'],
        "Posted transaction: {$transaction['transaction_number']} for company {$transaction['company_name']}"
    ]);

    $pdo->commit();

    // Validate accounting equation after posting
    require_once __DIR__ . '/../../../utils/accounting_validator.php';
    $validator = new AccountingValidator($pdo);
    $validation = $validator->validateAccountingEquation($transaction['company_id']);

    if (!$validation['balanced']) {
        error_log("CRITICAL: Accounting equation broken after admin posting transaction {$transaction['transaction_number']} for company {$transaction['company_id']}");
        error_log("Difference: " . $validation['metrics']['difference']);
    }

    echo json_encode([
        'success' => true,
        'message' => 'Transaction posted successfully',
        'data' => [
            'id' => $transaction_id,
            'transaction_number' => $transaction['transaction_number'],
            'status_id' => 2
        ]
    ]);

} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Admin transaction post error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Admin transaction post error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

