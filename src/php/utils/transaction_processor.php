<?php
/**
 * Transaction Processor - Centralized Transaction Logic
 *
 * SINGLE SOURCE OF TRUTH for all transaction balance calculations
 * This ensures consistency across ALL transaction endpoints
 */

class TransactionProcessor {
    private $pdo;
    private $validator;

    public function __construct($pdo) {
        $this->pdo = $pdo;
        require_once __DIR__ . '/accounting_validator.php';
        $this->validator = new AccountingValidator($pdo);
    }

    /**
     * Calculate balance change for an account
     *
     * This is the CORE ALGORITHM that determines how a debit or credit
     * affects an account's balance based on its normal balance.
     *
     * @param string $normal_balance 'debit' or 'credit' from account_types
     * @param string $line_type 'debit' or 'credit' from transaction_lines
     * @param float $amount The transaction amount
     * @return float The change amount (positive = increase, negative = decrease)
     */
    public static function calculateBalanceChange($normal_balance, $line_type, $amount) {
        // FUNDAMENTAL ACCOUNTING RULE:
        //
        // If the transaction side (debit/credit) MATCHES the account's normal balance
        // → The account INCREASES
        //
        // If the transaction side OPPOSES the account's normal balance
        // → The account DECREASES
        //
        // Examples:
        // - Asset (debit normal) + Debit → Increase (+)
        // - Asset (debit normal) + Credit → Decrease (-)
        // - Liability (credit normal) + Credit → Increase (+)
        // - Liability (credit normal) + Debit → Decrease (-)

        if ($normal_balance === $line_type) {
            return (float)$amount; // Same side = Increase
        } else {
            return -(float)$amount; // Opposite side = Decrease
        }
    }

    /**
     * Validate transaction lines before posting
     *
     * Checks:
     * 1. Debits = Credits (double-entry rule)
     * 2. All accounts exist and are active
     * 3. Balances won't go negative (except equity)
     * 4. Accounting equation will remain balanced
     *
     * @param int $company_id
     * @param array $lines Array of transaction lines
     * @param array $account_cache Optional pre-loaded account data
     * @return array ['valid' => bool, 'message' => string, 'details' => array]
     */
    public function validateTransactionLines($company_id, $lines, $account_cache = null) {
        $validation = [
            'valid' => true,
            'message' => 'Transaction is valid',
            'details' => [],
            'warnings' => []
        ];

        try {
            // Rule 1: Check debits = credits
            $total_debits = 0;
            $total_credits = 0;

            foreach ($lines as $line) {
                if ($line['line_type'] === 'debit') {
                    $total_debits += (float)$line['amount'];
                } else {
                    $total_credits += (float)$line['amount'];
                }
            }

            if (abs($total_debits - $total_credits) > 0.01) {
                return [
                    'valid' => false,
                    'message' => 'Transaction not balanced! Debits must equal Credits.',
                    'details' => [
                        'debits' => $total_debits,
                        'credits' => $total_credits,
                        'difference' => $total_debits - $total_credits
                    ]
                ];
            }

            // Rule 2 & 3: Check each account
            $balance_changes = [];

            foreach ($lines as $line) {
                $account_id = (int)$line['account_id'];
                $amount = (float)$line['amount'];

                // Get account details
                if ($account_cache && isset($account_cache[$account_id])) {
                    $account = $account_cache[$account_id];
                } else {
                    $stmt = $this->pdo->prepare("
                        SELECT
                            a.id,
                            a.account_name,
                            a.current_balance,
                            a.is_active,
                            a.is_system_account,
                            at.id as type_id,
                            at.type_name,
                            at.normal_balance
                        FROM accounts a
                        JOIN account_types at ON a.account_type_id = at.id
                        WHERE a.id = ? AND a.company_id = ?
                    ");
                    $stmt->execute([$account_id, $company_id]);
                    $account = $stmt->fetch(PDO::FETCH_ASSOC);
                }

                if (!$account) {
                    return [
                        'valid' => false,
                        'message' => 'Account not found',
                        'details' => ['account_id' => $account_id]
                    ];
                }

                if ($account['is_active'] != 1) {
                    return [
                        'valid' => false,
                        'message' => 'Cannot use inactive account',
                        'details' => [
                            'account_id' => $account_id,
                            'account_name' => $account['account_name']
                        ]
                    ];
                }

                // Skip validation for external/system accounts (they have unlimited balance)
                if ($account['is_system_account'] == 1) {
                    continue;
                }

                // Calculate projected balance change
                $change = self::calculateBalanceChange(
                    $account['normal_balance'],
                    $line['line_type'],
                    $amount
                );

                $current_balance = (float)$account['current_balance'];
                $new_balance = $current_balance + $change;

                $balance_changes[$account_id] = [
                    'account_name' => $account['account_name'],
                    'type_name' => $account['type_name'],
                    'type_id' => $account['type_id'],
                    'current' => $current_balance,
                    'change' => $change,
                    'projected' => $new_balance
                ];

                // Rule 3: Check for negative balances (not allowed for most account types)
                // Type IDs: 1=Asset, 2=Liability, 3=Equity, 4=Revenue, 5=Expense
                $cannot_be_negative = [1, 2, 4, 5]; // Asset, Liability, Revenue, Expense

                if (in_array($account['type_id'], $cannot_be_negative) && $new_balance < 0) {
                    return [
                        'valid' => false,
                        'message' => "{$account['type_name']} accounts cannot have negative balances!",
                        'details' => [
                            'account_name' => $account['account_name'],
                            'type' => $account['type_name'],
                            'current_balance' => $current_balance,
                            'change' => $change,
                            'would_result_in' => $new_balance
                        ]
                    ];
                }

                // Special check: Negative equity requires approval
                if ($account['type_id'] == 3 && $new_balance < 0 && $current_balance >= 0) {
                    $validation['warnings'][] = [
                        'type' => 'NEGATIVE_EQUITY',
                        'message' => 'Transaction would create negative equity (owner withdrawal exceeds investment)',
                        'requires_approval' => true,
                        'details' => $balance_changes[$account_id]
                    ];
                }
            }

            $validation['details']['balance_changes'] = $balance_changes;

            // Rule 4: Validate accounting equation will remain balanced
            // This is the ULTIMATE test - if equation breaks, REJECT!
            $equation_check = $this->validator->validateProposedTransaction($company_id, $lines);

            if (!$equation_check['valid']) {
                return [
                    'valid' => false,
                    'message' => $equation_check['message'],
                    'details' => [
                        'violations' => $equation_check['violations'],
                        'projected' => $equation_check['projected'] ?? []
                    ]
                ];
            }

            return $validation;

        } catch (Exception $e) {
            return [
                'valid' => false,
                'message' => 'Validation error: ' . $e->getMessage(),
                'details' => []
            ];
        }
    }

    /**
     * Post a transaction (update account balances)
     *
     * This should ONLY be called after validateTransactionLines() passes!
     *
     * @param int $transaction_id
     * @return array ['success' => bool, 'message' => string]
     */
    public function postTransaction($transaction_id) {
        try {
            // Get transaction details
            $stmt = $this->pdo->prepare("
                SELECT company_id, transaction_number, status_id
                FROM transactions
                WHERE id = ?
            ");
            $stmt->execute([$transaction_id]);
            $transaction = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$transaction) {
                return ['success' => false, 'message' => 'Transaction not found'];
            }

            if ($transaction['status_id'] != 1) {
                return ['success' => false, 'message' => 'Only pending transactions can be posted'];
            }

            // Get transaction lines
            $stmt = $this->pdo->prepare("
                SELECT
                    tl.*,
                    a.current_balance,
                    at.normal_balance,
                    at.type_name
                FROM transaction_lines tl
                JOIN accounts a ON tl.account_id = a.id
                JOIN account_types at ON a.account_type_id = at.id
                WHERE tl.transaction_id = ?
            ");
            $stmt->execute([$transaction_id]);
            $lines = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (empty($lines)) {
                return ['success' => false, 'message' => 'Transaction has no lines'];
            }

            // Convert to validation format
            $lines_for_validation = array_map(function($line) {
                return [
                    'account_id' => $line['account_id'],
                    'line_type' => $line['line_type'],
                    'amount' => $line['amount']
                ];
            }, $lines);

            // CRITICAL: Validate BEFORE updating
            $validation = $this->validateTransactionLines(
                $transaction['company_id'],
                $lines_for_validation
            );

            if (!$validation['valid']) {
                return [
                    'success' => false,
                    'message' => $validation['message'],
                    'details' => $validation['details']
                ];
            }

            // Validation passed - update balances
            foreach ($lines as $line) {
                $change = self::calculateBalanceChange(
                    $line['normal_balance'],
                    $line['line_type'],
                    (float)$line['amount']
                );

                $stmt = $this->pdo->prepare("
                    UPDATE accounts
                    SET current_balance = current_balance + ?
                    WHERE id = ?
                ");
                $stmt->execute([$change, $line['account_id']]);
            }

            // Update transaction status to Posted (status_id = 2)
            $stmt = $this->pdo->prepare("
                UPDATE transactions
                SET status_id = 2, posted_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$transaction_id]);

            // Verify equation after posting
            $equation = $this->validator->validateAccountingEquation($transaction['company_id']);

            if (!$equation['balanced']) {
                error_log("CRITICAL: Equation broken after posting transaction {$transaction['transaction_number']}");
                error_log("Company: {$transaction['company_id']}, Difference: {$equation['metrics']['difference']}");
            }

            return [
                'success' => true,
                'message' => 'Transaction posted successfully',
                'equation_balanced' => $equation['balanced']
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Error posting transaction: ' . $e->getMessage()
            ];
        }
    }
}

