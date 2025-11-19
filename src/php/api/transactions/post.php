<?php
// Transaction Post API (Convert pending to posted)
error_reporting(0);
ini_set('display_errors', 0);

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
require_once __DIR__ . '/../../utils/transaction_processor.php';

// Get transaction ID
$input = file_get_contents('php://input');
$data = json_decode($input, true);

// Check if JSON is valid
if ($data === null && json_last_error() !== JSON_ERROR_NONE) {
    echo json_encode(['success' => false, 'message' => 'Invalid JSON: ' . json_last_error_msg()]);
    exit;
}

$transaction_id = isset($data['id']) ? (int)$data['id'] : 0;

if (!$transaction_id) {
    echo json_encode(['success' => false, 'message' => 'Transaction ID required']);
    exit;
}

$company_id = $_SESSION['company_id'];
$user_id = $_SESSION['user_id'];

try {
    $pdo = Database::getInstance()->getConnection();

    // Check if transaction exists and is pending
    $stmt = $pdo->prepare("
        SELECT * FROM transactions
        WHERE id = ? AND company_id = ? AND status_id = 1
    ");
    $stmt->execute([$transaction_id, $company_id]);
    $transaction = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$transaction) {
        echo json_encode(['success' => false, 'message' => 'Transaction not found or already posted']);
        exit;
    }

    // CRITICAL SECURITY CHECK: Tenants cannot post transactions requiring admin approval
    if ($transaction['requires_approval'] == 1) {
        echo json_encode([
            'success' => false,
            'message' => 'This transaction requires admin approval',
            'details' => 'This transaction has been flagged for admin review and cannot be posted by tenants. Please wait for admin approval or contact your administrator.'
        ]);
        exit;
    }

    $pdo->beginTransaction();

    // Update transaction status to posted (status_id = 2)
    $stmt = $pdo->prepare("
        UPDATE transactions
        SET status_id = 2,
            posted_by = ?,
            posted_at = NOW()
        WHERE id = ?
    ");
    $stmt->execute([$user_id, $transaction_id]);

    // Get transaction lines with account normal_balance
    $stmt = $pdo->prepare("
        SELECT tl.account_id, tl.line_type, tl.amount,
               a.current_balance, a.account_name, a.is_system_account,
               at.id as type_id, at.type_name, at.normal_balance
        FROM transaction_lines tl
        JOIN accounts a ON tl.account_id = a.id
        JOIN account_types at ON a.account_type_id = at.id
        WHERE tl.transaction_id = ?
    ");
    $stmt->execute([$transaction_id]);
    $lines = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Validate balances BEFORE updating
    foreach ($lines as $line) {
        $amount = (float)$line['amount'];

        // ⚙️ USE CENTRALIZED BALANCE CALCULATION
        $change = TransactionProcessor::calculateBalanceChange(
            $line['normal_balance'],
            $line['line_type'],
            $amount
        );

        $account = [
            'current_balance' => $line['current_balance'],
            'account_name' => $line['account_name'],
            'is_system_account' => $line['is_system_account'],
            'type_id' => $line['type_id'],
            'type_name' => $line['type_name']
        ];

        if (!$account) {
            throw new Exception("Account not found: " . $line['account_id']);
        }

        // ⭐ SKIP VALIDATION FOR EXTERNAL ACCOUNTS (unlimited balance for simulation)
        if ($account['is_system_account'] == 1) {
            continue; // External accounts have infinite balance, skip validation
        }

        $new_balance = $account['current_balance'] + $change;
        $type_id = $account['type_id'];
        $type_name = $account['type_name'];
        $account_name = $account['account_name'];

        // Accounting Rule: Certain account types CANNOT have negative balances
        $cannot_be_negative = [1, 2, 4, 5]; // Asset, Liability, Revenue, Expense

        if (in_array($type_id, $cannot_be_negative) && $new_balance < 0) {
            $pdo->rollBack();
            echo json_encode([
                'success' => false,
                'message' => "Cannot post transaction - violates accounting rules!",
                'details' => sprintf(
                    "%s accounts cannot have negative balances!\n\n" .
                    "Account: %s (%s)\n" .
                    "Current Balance: $%.2f\n" .
                    "Change: %s $%.2f\n" .
                    "Would Result In: $%.2f\n\n" .
                    "Rule: %s accounts represent what you %s - they cannot go negative.",
                    $type_name,
                    $account_name,
                    $type_name,
                    $account['current_balance'],
                    $line['line_type'] === 'debit' ? '+' : '-',
                    $amount,
                    $new_balance,
                    $type_name,
                    $type_id == 1 ? 'OWN' : ($type_id == 2 ? 'OWE' : ($type_id == 4 ? 'EARN' : 'SPEND'))
                )
            ]);
            exit;
        }
    }

    // Validation passed, update account balances
    $stmt = $pdo->prepare("
        UPDATE accounts
        SET current_balance = current_balance + ?
        WHERE id = ?
    ");

    foreach ($lines as $line) {
        $amount = (float)$line['amount'];

        // ⚙️ USE CENTRALIZED BALANCE CALCULATION
        $change = TransactionProcessor::calculateBalanceChange(
            $line['normal_balance'],
            $line['line_type'],
            $amount
        );

        $stmt->execute([$change, (int)$line['account_id']]);
    }

    // Log activity
    $stmt = $pdo->prepare("
        INSERT INTO activity_logs (user_id, username, user_role, company_id, activity_type, details)
        VALUES (?, ?, 'tenant', ?, 'transaction', ?)
    ");
    $stmt->execute([
        $user_id,
        $_SESSION['username'] ?? 'unknown',
        $company_id,
        "Posted transaction: " . $transaction['transaction_number']
    ]);

    $pdo->commit();

    // ✅ CRITICAL: Validate accounting equation after posting
    require_once __DIR__ . '/../../utils/accounting_validator.php';
    $validator = new AccountingValidator($pdo);
    $validation = $validator->validateAccountingEquation($company_id);

    if (!$validation['balanced']) {
        error_log("CRITICAL: Accounting equation broken after posting transaction " . $transaction['transaction_number']);
        error_log("Company: $company_id, Difference: " . $validation['metrics']['difference']);
    }

    echo json_encode([
        'success' => true,
        'message' => 'Transaction posted successfully'
    ]);

} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Transaction post error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Transaction post error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
