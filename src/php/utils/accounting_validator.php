<?php
/**
 * Accounting Equation Validator
 * Validates that the accounting equation (Assets = Liabilities + Equity) remains balanced
 */

class AccountingValidator {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Validate the accounting equation for a company
     * Assets = Liabilities + Equity + (Revenue - Expenses)
     *
     * Note: Revenue and Expenses are temporary accounts that close to Equity,
     * but while open, they are part of the equation.
     *
     * @param int $company_id
     * @return array ['balanced' => bool, 'metrics' => [...], 'difference' => float]
     */
    public function validateAccountingEquation($company_id) {
        try {
            // Calculate total assets (debit normal balance)
            // EXCLUDE system accounts - they are external simulation accounts
            $stmt = $this->pdo->prepare("
                SELECT COALESCE(SUM(current_balance), 0) as total
                FROM accounts
                WHERE company_id = ? AND account_type_id = 1 AND is_active = 1 AND is_system_account = 0
            ");
            $stmt->execute([$company_id]);
            $total_assets = (float)$stmt->fetchColumn();

            // Calculate total liabilities (credit normal balance)
            // EXCLUDE system accounts
            $stmt = $this->pdo->prepare("
                SELECT COALESCE(SUM(current_balance), 0) as total
                FROM accounts
                WHERE company_id = ? AND account_type_id = 2 AND is_active = 1 AND is_system_account = 0
            ");
            $stmt->execute([$company_id]);
            $total_liabilities = (float)$stmt->fetchColumn();

            // Calculate total equity (credit normal balance)
            // EXCLUDE system accounts
            $stmt = $this->pdo->prepare("
                SELECT COALESCE(SUM(current_balance), 0) as total
                FROM accounts
                WHERE company_id = ? AND account_type_id = 3 AND is_active = 1 AND is_system_account = 0
            ");
            $stmt->execute([$company_id]);
            $total_equity = (float)$stmt->fetchColumn();

            // Calculate total revenue (credit normal balance - temporary account)
            // EXCLUDE system accounts
            $stmt = $this->pdo->prepare("
                SELECT COALESCE(SUM(current_balance), 0) as total
                FROM accounts
                WHERE company_id = ? AND account_type_id = 4 AND is_active = 1 AND is_system_account = 0
            ");
            $stmt->execute([$company_id]);
            $total_revenue = (float)$stmt->fetchColumn();

            // Calculate total expenses (debit normal balance - temporary account)
            // EXCLUDE system accounts
            $stmt = $this->pdo->prepare("
                SELECT COALESCE(SUM(current_balance), 0) as total
                FROM accounts
                WHERE company_id = ? AND account_type_id = 5 AND is_active = 1 AND is_system_account = 0
            ");
            $stmt->execute([$company_id]);
            $total_expenses = (float)$stmt->fetchColumn();

            // Accounting equation: Assets = Liabilities + Equity + (Revenue - Expenses)
            // Revenue and Expenses are temporary - they close to Equity at period end
            $left_side = $total_assets;
            $right_side = $total_liabilities + $total_equity + $total_revenue - $total_expenses;
            $difference = $left_side - $right_side;

            // Allow small rounding differences (1 cent)
            $is_balanced = abs($difference) < 0.01;

            return [
                'balanced' => $is_balanced,
                'metrics' => [
                    'assets' => $total_assets,
                    'liabilities' => $total_liabilities,
                    'equity' => $total_equity,
                    'revenue' => $total_revenue,
                    'expenses' => $total_expenses,
                    'left_side' => $left_side,
                    'right_side' => $right_side,
                    'difference' => $difference
                ],
                'message' => $is_balanced
                    ? 'Accounting equation is balanced'
                    : sprintf(
                        'Accounting equation is NOT balanced! Assets ($%.2f) != Liabilities + Equity + Revenue - Expenses ($%.2f). Difference: $%.2f',
                        $left_side,
                        $right_side,
                        $difference
                    )
            ];
        } catch (Exception $e) {
            error_log("AccountingValidator error: " . $e->getMessage());
            return [
                'balanced' => false,
                'metrics' => [],
                'message' => 'Error validating equation: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Validate a proposed transaction before saving
     * Checks if the transaction would break the accounting equation
     *
     * @param int $company_id
     * @param array $lines Transaction lines with account_id, line_type, amount
     * @return array ['valid' => bool, 'message' => string, 'violations' => array]
     */
    public function validateProposedTransaction($company_id, $lines) {
        $violations = [];

        // STEP 1: Check debits = credits
        $total_debits = 0;
        $total_credits = 0;

        foreach ($lines as $line) {
            if ($line['line_type'] === 'debit') {
                $total_debits += (float)$line['amount'];
            } else if ($line['line_type'] === 'credit') {
                $total_credits += (float)$line['amount'];
            }
        }

        if (abs($total_debits - $total_credits) > 0.01) {
            $violations[] = sprintf(
                "Transaction not balanced: Debits ($%.2f) != Credits ($%.2f)",
                $total_debits,
                $total_credits
            );
        }

        // STEP 2: Check each account for negative balances
        $balance_changes_by_type = [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0];

        error_log("=== VALIDATING TRANSACTION LINES ===");
        foreach ($lines as $line) {
            $stmt = $this->pdo->prepare("
                SELECT a.current_balance, a.account_name, a.is_system_account,
                       at.id as type_id, at.type_name, at.normal_balance
                FROM accounts a
                JOIN account_types at ON a.account_type_id = at.id
                WHERE a.id = ? AND a.company_id = ?
            ");
            $stmt->execute([$line['account_id'], $company_id]);
            $account = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$account) {
                error_log("ERROR: Account ID {$line['account_id']} not found");
                $violations[] = "Account ID {$line['account_id']} not found";
                continue;
            }

            // Calculate balance change based on normal balance and transaction type
            require_once __DIR__ . '/transaction_processor.php';
            $change = TransactionProcessor::calculateBalanceChange(
                $account['normal_balance'],
                $line['line_type'],
                $line['amount']
            );

            $new_balance = $account['current_balance'] + $change;

            // DEBUG: Log each account's calculation
            error_log("Account: {$account['account_name']} (Type: {$account['type_name']}, ID: {$account['type_id']})");
            error_log("  Normal Balance: {$account['normal_balance']}");
            error_log("  Transaction: {$line['line_type']} \${$line['amount']}");
            error_log("  Current Balance: {$account['current_balance']}");
            error_log("  Change: {$change}");
            error_log("  New Balance: {$new_balance}");
            error_log("  Is System Account: " . ($account['is_system_account'] ? 'YES' : 'NO'));

            // Track changes by account type for equation validation
            // IMPORTANT: Track ALL accounts, including system accounts, for equation balance
            $balance_changes_by_type[$account['type_id']] += $change;

            // Skip negative balance check for external/system accounts (unlimited balance)
            if ($account['is_system_account'] == 1) {
                continue; // Skip to next account, but we already tracked the change
            }

            // Asset, Liability, Revenue, Expense cannot be negative
            $cannot_be_negative = [1, 2, 4, 5];
            if (in_array($account['type_id'], $cannot_be_negative) && $new_balance < 0) {
                $violations[] = sprintf(
                    "%s account '%s' would become negative ($%.2f)",
                    $account['type_name'],
                    $account['account_name'],
                    $new_balance
                );
            }
        }

        // STEP 3: Validate accounting equation will remain balanced
        // Get current balances (now includes Revenue and Expenses)
        $current_equation = $this->validateAccountingEquation($company_id);

        // DEBUG: Log current equation state
        error_log("=== CURRENT EQUATION STATE ===");
        error_log("Assets: " . $current_equation['metrics']['assets']);
        error_log("Liabilities: " . $current_equation['metrics']['liabilities']);
        error_log("Equity: " . $current_equation['metrics']['equity']);
        error_log("Revenue: " . $current_equation['metrics']['revenue']);
        error_log("Expenses: " . $current_equation['metrics']['expenses']);

        // DEBUG: Log balance changes by type
        error_log("=== BALANCE CHANGES BY TYPE ===");
        error_log("Asset changes (type 1): " . $balance_changes_by_type[1]);
        error_log("Liability changes (type 2): " . $balance_changes_by_type[2]);
        error_log("Equity changes (type 3): " . $balance_changes_by_type[3]);
        error_log("Revenue changes (type 4): " . $balance_changes_by_type[4]);
        error_log("Expense changes (type 5): " . $balance_changes_by_type[5]);

        // Project new balances after transaction
        // NOTE: Revenue and Expenses are temporary accounts that eventually close to Equity,
        // but they are part of the equation while they have balances

        $projected_assets = $current_equation['metrics']['assets'] + $balance_changes_by_type[1];
        $projected_liabilities = $current_equation['metrics']['liabilities'] + $balance_changes_by_type[2];
        $projected_equity = $current_equation['metrics']['equity'] + $balance_changes_by_type[3];
        $projected_revenue = $current_equation['metrics']['revenue'] + $balance_changes_by_type[4];
        $projected_expenses = $current_equation['metrics']['expenses'] + $balance_changes_by_type[5];

        // DEBUG: Log projected values
        error_log("=== PROJECTED BALANCES ===");
        error_log("Projected Assets: " . $projected_assets);
        error_log("Projected Liabilities: " . $projected_liabilities);
        error_log("Projected Equity: " . $projected_equity);
        error_log("Projected Revenue: " . $projected_revenue);
        error_log("Projected Expenses: " . $projected_expenses);

        // Accounting equation: Assets = Liabilities + Equity + (Revenue - Expenses)
        $left_side = $projected_assets;
        $right_side = $projected_liabilities + $projected_equity + $projected_revenue - $projected_expenses;

        // DEBUG: Log equation calculation
        error_log("=== EQUATION CHECK ===");
        error_log("Left Side (Assets): " . $left_side);
        error_log("Right Side (L + E + R - Ex): " . $right_side);
        error_log("Difference: " . ($left_side - $right_side));
        error_log("Is Balanced: " . (abs($left_side - $right_side) < 0.01 ? 'YES' : 'NO'));

        if (abs($left_side - $right_side) > 0.01) {
            $violations[] = sprintf(
                "Transaction would break accounting equation! Projected: Assets ($%.2f) != Liabilities + Equity + Revenue - Expenses ($%.2f), Difference: $%.2f",
                $left_side,
                $right_side,
                $left_side - $right_side
            );
        }

        return [
            'valid' => count($violations) === 0,
            'message' => count($violations) === 0
                ? 'Transaction is valid'
                : 'Transaction would violate accounting rules',
            'violations' => $violations,
            'projected' => [
                'assets' => $projected_assets,
                'liabilities' => $projected_liabilities,
                'equity' => $projected_equity,
                'revenue' => $projected_revenue,
                'expenses' => $projected_expenses,
                'left_side' => $left_side,
                'right_side' => $right_side,
                'balanced' => abs($left_side - $right_side) < 0.01
            ]
        ];
    }
}

