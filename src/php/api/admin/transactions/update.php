<?php
/**
 * Admin Transaction Update API
 * Allows admin to update pending transactions for any company
 */
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

$input = file_get_contents('php://input');
$data = json_decode($input, true);

// Check if JSON is valid
if ($data === null && json_last_error() !== JSON_ERROR_NONE) {
    echo json_encode(['success' => false, 'message' => 'Invalid JSON: ' . json_last_error_msg()]);
    exit;
}

// Validate input
if (empty($data) || !is_array($data)) {
    echo json_encode(['success' => false, 'message' => 'No data provided']);
    exit;
}

if (!isset($data['id']) || !isset($data['transaction_date']) || !isset($data['description']) || !isset($data['lines'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields: id, transaction_date, description, lines']);
    exit;
}

if (count($data['lines']) < 2) {
    echo json_encode(['success' => false, 'message' => 'Transaction must have at least 2 lines']);
    exit;
}

$transaction_id = (int)$data['id'];
$transaction_date = $data['transaction_date'];
$description = trim($data['description']);
$reference_number = isset($data['reference_number']) ? trim($data['reference_number']) : null;
$lines = $data['lines'];
$updated_by = $_SESSION['user_id'];

try {
    $pdo = Database::getInstance()->getConnection();
    $pdo->beginTransaction();

    // Verify transaction exists and is pending
    $stmt = $pdo->prepare("
        SELECT t.id, t.status_id, t.transaction_number, t.company_id, c.company_name
        FROM transactions t
        JOIN companies c ON t.company_id = c.id
        WHERE t.id = ?
    ");
    $stmt->execute([$transaction_id]);
    $transaction = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$transaction) {
        throw new Exception('Transaction not found');
    }

    if ($transaction['status_id'] != 1) {
        throw new Exception('Only pending transactions can be edited');
    }

    $company_id = $transaction['company_id'];

    // Validate balance (debits must equal credits)
    $total_debits = 0;
    $total_credits = 0;

    foreach ($lines as $line) {
        if (empty($line['account_id']) || empty($line['line_type']) || !isset($line['amount'])) {
            throw new Exception('Invalid line data');
        }

        $amount = (float)$line['amount'];
        if ($amount <= 0) {
            throw new Exception('Line amounts must be greater than 0');
        }

        if ($line['line_type'] === 'debit') {
            $total_debits += $amount;
        } else {
            $total_credits += $amount;
        }
    }

    if (abs($total_debits - $total_credits) > 0.01) {
        throw new Exception('Transaction is not balanced. Debits: $' . number_format($total_debits, 2) . ', Credits: $' . number_format($total_credits, 2));
    }

    // Verify all accounts belong to this company
    foreach ($lines as $line) {
        $stmt = $pdo->prepare("SELECT company_id FROM accounts WHERE id = ?");
        $stmt->execute([$line['account_id']]);
        $account = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$account) {
            throw new Exception('Account ID ' . $line['account_id'] . ' not found');
        }

        if ($account['company_id'] != $company_id) {
            throw new Exception('Account ID ' . $line['account_id'] . ' does not belong to the transaction company');
        }
    }

    // Determine transaction type from first line's account
    $first_account_id = $lines[0]['account_id'];
    $stmt = $pdo->prepare("
        SELECT at.type_name
        FROM accounts a
        JOIN account_types at ON a.account_type_id = at.id
        WHERE a.id = ?
    ");
    $stmt->execute([$first_account_id]);
    $transaction_type = $stmt->fetchColumn();

    if (!$transaction_type) {
        throw new Exception('Invalid account in first line');
    }

    // Update transaction
    $stmt = $pdo->prepare("
        UPDATE transactions
        SET transaction_date = ?,
            transaction_type = ?,
            description = ?,
            reference_number = ?,
            total_amount = ?
        WHERE id = ?
    ");

    $stmt->execute([
        $transaction_date,
        $transaction_type,
        $description,
        $reference_number,
        $total_debits,
        $transaction_id
    ]);

    // Delete old transaction lines
    $stmt = $pdo->prepare("DELETE FROM transaction_lines WHERE transaction_id = ?");
    $stmt->execute([$transaction_id]);

    // Insert new transaction lines
    $stmt = $pdo->prepare("
        INSERT INTO transaction_lines (
            transaction_id,
            account_id,
            line_type,
            amount
        ) VALUES (?, ?, ?, ?)
    ");

    foreach ($lines as $line) {
        $stmt->execute([
            $transaction_id,
            (int)$line['account_id'],
            $line['line_type'],
            (float)$line['amount']
        ]);
    }

    // Log activity
    $stmt = $pdo->prepare("
        INSERT INTO activity_logs (user_id, username, user_role, company_id, activity_type, details)
        VALUES (?, ?, 'admin', ?, 'transaction', ?)
    ");
    $stmt->execute([
        $updated_by,
        $_SESSION['username'] ?? 'admin',
        $company_id,
        "Updated transaction: " . $transaction['transaction_number'] . " for company " . $transaction['company_name']
    ]);

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Transaction updated successfully',
        'data' => [
            'id' => $transaction_id,
            'transaction_number' => $transaction['transaction_number'],
            'company_id' => $company_id,
            'company_name' => $transaction['company_name']
        ]
    ]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Admin update transaction error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

