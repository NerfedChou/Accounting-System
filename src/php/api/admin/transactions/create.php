<?php
// Admin Transaction Creation API - Create transaction for any company
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
require_once __DIR__ . '/../../../utils/transaction_processor.php';

// Get request data
$input = file_get_contents('php://input');
$data = json_decode($input, true);

// Check if JSON is valid
if ($data === null && json_last_error() !== JSON_ERROR_NONE) {
    echo json_encode(['success' => false, 'message' => 'Invalid JSON: ' . json_last_error_msg()]);
    exit;
}

// Validate required fields
if (empty($data) || !is_array($data)) {
    echo json_encode(['success' => false, 'message' => 'No data provided']);
    exit;
}

if (empty($data['company_id']) || empty($data['transaction_date']) || empty($data['description']) || empty($data['lines'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields: company_id, transaction_date, description, lines']);
    exit;
}

if (count($data['lines']) < 2) {
    echo json_encode(['success' => false, 'message' => 'Transaction must have at least 2 lines']);
    exit;
}

$company_id = (int)$data['company_id'];
$transaction_date = $data['transaction_date'];
$description = trim($data['description']);
$reference_number = isset($data['reference_number']) ? trim($data['reference_number']) : null;
$status = isset($data['status']) && $data['status'] === 'posted' ? 2 : 1; // 1=pending, 2=posted
$lines = $data['lines'];
$created_by = $_SESSION['user_id'];

// Validate balance (debits must equal credits)
$total_debits = 0;
$total_credits = 0;

foreach ($lines as $line) {
    if (empty($line['account_id']) || empty($line['line_type']) || !isset($line['amount'])) {
        echo json_encode(['success' => false, 'message' => 'Invalid line data']);
        exit;
    }

    $amount = (float)$line['amount'];
    if ($amount <= 0) {
        echo json_encode(['success' => false, 'message' => 'Line amounts must be greater than 0']);
        exit;
    }

    if ($line['line_type'] === 'debit') {
        $total_debits += $amount;
    } elseif ($line['line_type'] === 'credit') {
        $total_credits += $amount;
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid line type']);
        exit;
    }
}

// Check if balanced
if (abs($total_debits - $total_credits) > 0.01) {
    echo json_encode([
        'success' => false,
        'message' => 'Transaction is not balanced. Debits must equal Credits.',
        'debug' => [
            'debits' => $total_debits,
            'credits' => $total_credits,
            'difference' => $total_debits - $total_credits
        ]
    ]);
    exit;
}

try {
    $pdo = Database::getInstance()->getConnection();
    $pdo->beginTransaction();

    // Verify company exists and is active
    $stmt = $pdo->prepare("SELECT id, company_name, is_active FROM companies WHERE id = ?");
    $stmt->execute([$company_id]);
    $company = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$company) {
        echo json_encode(['success' => false, 'message' => 'Company not found']);
        exit;
    }

    if (!$company['is_active']) {
        echo json_encode(['success' => false, 'message' => 'Cannot create transaction for inactive company']);
        exit;
    }

    // Verify all accounts belong to this company
    foreach ($lines as $line) {
        $stmt = $pdo->prepare("SELECT company_id FROM accounts WHERE id = ?");
        $stmt->execute([$line['account_id']]);
        $account = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$account) {
            $pdo->rollBack();
            echo json_encode(['success' => false, 'message' => 'Account ID ' . $line['account_id'] . ' not found']);
            exit;
        }

        if ($account['company_id'] != $company_id) {
            $pdo->rollBack();
            echo json_encode(['success' => false, 'message' => 'Account ID ' . $line['account_id'] . ' does not belong to the selected company']);
            exit;
        }
    }

    // Determine transaction type from first line account type
    $firstAccountId = $lines[0]['account_id'];
    $stmt = $pdo->prepare("
        SELECT at.type_name
        FROM accounts a
        JOIN account_types at ON a.account_type_id = at.id
        WHERE a.id = ?
    ");
    $stmt->execute([$firstAccountId]);
    $accountType = $stmt->fetch(PDO::FETCH_ASSOC);
    $transaction_type = $accountType ? $accountType['type_name'] : 'Other';

    // Generate transaction number
    $stmt = $pdo->prepare("
        SELECT MAX(CAST(SUBSTRING(transaction_number, 5) AS UNSIGNED)) as max_num
        FROM transactions
        WHERE company_id = ? AND transaction_number LIKE 'TXN-%'
    ");
    $stmt->execute([$company_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $next_num = ($result['max_num'] ?? 0) + 1;
    $transaction_number = 'TXN-' . str_pad($next_num, 6, '0', STR_PAD_LEFT);

    // Insert transaction
    $stmt = $pdo->prepare("
        INSERT INTO transactions (
            company_id,
            transaction_number,
            transaction_date,
            transaction_type,
            entry_mode,
            description,
            reference_number,
            total_amount,
            status_id,
            created_by,
            requires_approval,
            posted_by,
            posted_at
        ) VALUES (?, ?, ?, ?, 'double', ?, ?, ?, ?, ?, 0, ?, ?)
    ");

    $stmt->execute([
        $company_id,
        $transaction_number,
        $transaction_date,
        $transaction_type,
        $description,
        $reference_number,
        $total_debits,
        $status,
        $created_by,
        $status == 2 ? $created_by : null,
        $status == 2 ? date('Y-m-d H:i:s') : null
    ]);

    $transaction_id = $pdo->lastInsertId();

    // Insert transaction lines
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

    // If posted, update account balances with validation
    if ($status == 2) {
        // First, validate that balances won't go negative for restricted account types
        foreach ($lines as $line) {
            $amount = (float)$line['amount'];

            // Get account type and current balance INCLUDING normal_balance
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
            $type_name = $account['type_name'];
            $account_name = $account['account_name'];

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

        // Validation passed, now update balances
        foreach ($lines as $line) {
            $amount = (float)$line['amount'];

            // Get account's normal balance
            $stmt = $pdo->prepare("
                SELECT at.normal_balance
                FROM accounts a
                JOIN account_types at ON a.account_type_id = at.id
                WHERE a.id = ?
            ");
            $stmt->execute([(int)$line['account_id']]);
            $account = $stmt->fetch(PDO::FETCH_ASSOC);

            // ⚙️ USE CENTRALIZED BALANCE CALCULATION
            $change = TransactionProcessor::calculateBalanceChange(
                $account['normal_balance'],
                $line['line_type'],
                $amount
            );

            // Update balance
            $stmt = $pdo->prepare("UPDATE accounts SET current_balance = current_balance + ? WHERE id = ?");
            $stmt->execute([$change, (int)$line['account_id']]);
        }
    }

    // Log activity
    $stmt = $pdo->prepare("
        INSERT INTO activity_logs (user_id, username, user_role, company_id, activity_type, details)
        VALUES (?, ?, 'admin', ?, 'transaction', ?)
    ");
    $stmt->execute([
        $created_by,
        $_SESSION['username'] ?? 'admin',
        $company_id,
        ($status == 2 ? 'Posted' : 'Created') . " transaction: $transaction_number for company {$company['company_name']}"
    ]);

    $pdo->commit();

    // Validate accounting equation after posting
    if ($status == 2) {
        require_once __DIR__ . '/../../../utils/accounting_validator.php';
        $validator = new AccountingValidator($pdo);
        $validation = $validator->validateAccountingEquation($company_id);

        if (!$validation['balanced']) {
            error_log("CRITICAL: Accounting equation broken after admin transaction $transaction_number for company $company_id");
            error_log("Difference: " . $validation['metrics']['difference']);
        }
    }

    echo json_encode([
        'success' => true,
        'message' => $status == 2 ? 'Transaction posted successfully' : 'Transaction saved as pending',
        'data' => [
            'id' => $transaction_id,
            'transaction_number' => $transaction_number,
            'status_id' => $status,
            'company_id' => $company_id,
            'company_name' => $company['company_name']
        ]
    ]);

} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Admin transaction creation error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Admin transaction error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

