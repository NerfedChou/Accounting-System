<?php
/**
 * Validation Messages Helper
 * Formats validation errors with helpful suggestions for users
 */

class ValidationMessages {
    /**
     * Format validation violations into user-friendly messages with suggestions
     *
     * @param array $violations Array of violation strings
     * @return array Array of formatted messages with type, message, and suggestion
     */
    public static function formatViolations($violations) {
        $messages = [];

        foreach ($violations as $violation) {
            $formatted = [
                'original' => $violation,
                'type' => 'UNKNOWN',
                'message' => $violation,
                'suggestion' => 'Please review your transaction entries'
            ];

            // Detect violation type and add specific suggestions
            if (stripos($violation, 'negative') !== false) {
                $formatted['type'] = 'NEGATIVE_BALANCE';
                $formatted['suggestion'] = 'This account does not have sufficient balance. Check the current balance and adjust the amount.';

                // Extract account name and amount if possible
                if (preg_match("/account '([^']+)' would become negative \(([^)]+)\)/", $violation, $matches)) {
                    $formatted['account_name'] = $matches[1];
                    $formatted['would_be_balance'] = $matches[2];
                    $formatted['suggestion'] = sprintf(
                        "The account '%s' would have a balance of %s. Accounts of this type cannot have negative balances.",
                        $matches[1],
                        $matches[2]
                    );
                }

            } elseif (stripos($violation, 'not balanced') !== false) {
                $formatted['type'] = 'UNBALANCED';
                $formatted['suggestion'] = 'In double-entry accounting, total debits must equal total credits. Please adjust your entries so they balance.';

                // Extract amounts if possible
                if (preg_match('/Debits \(\$([0-9.]+)\) != Credits \(\$([0-9.]+)\)/', $violation, $matches)) {
                    $formatted['debits'] = (float)$matches[1];
                    $formatted['credits'] = (float)$matches[2];
                    $formatted['difference'] = abs($formatted['debits'] - $formatted['credits']);
                    $formatted['suggestion'] = sprintf(
                        "Your debits ($%.2f) and credits ($%.2f) don't match. The difference is $%.2f. %s",
                        $formatted['debits'],
                        $formatted['credits'],
                        $formatted['difference'],
                        $formatted['debits'] > $formatted['credits']
                            ? 'Add more credits or reduce debits.'
                            : 'Add more debits or reduce credits.'
                    );
                }

            } elseif (stripos($violation, 'accounting equation') !== false) {
                $formatted['type'] = 'EQUATION_BROKEN';
                $formatted['suggestion'] = 'This transaction would create an accounting discrepancy (Assets ≠ Liabilities + Equity). This is a fundamental accounting error. Please review all entries carefully.';

                // Extract projected values if possible
                if (preg_match('/Assets \(\$([0-9.]+)\) != Liabilities \+ Equity \(\$([0-9.]+)\)/', $violation, $matches)) {
                    $formatted['projected_assets'] = (float)$matches[1];
                    $formatted['projected_liabilities_equity'] = (float)$matches[2];
                    $formatted['difference'] = abs($formatted['projected_assets'] - $formatted['projected_liabilities_equity']);
                }

            } elseif (stripos($violation, 'not found') !== false) {
                $formatted['type'] = 'ACCOUNT_NOT_FOUND';
                $formatted['suggestion'] = 'The specified account does not exist or you do not have access to it. Please select a valid account.';

            } elseif (stripos($violation, 'inactive') !== false) {
                $formatted['type'] = 'INACTIVE_ACCOUNT';
                $formatted['suggestion'] = 'This account has been deactivated and cannot be used in transactions. Please select an active account.';
            }

            $messages[] = $formatted;
        }

        return $messages;
    }

    /**
     * Format a single violation message
     *
     * @param string $violation The violation string
     * @return array Formatted message
     */
    public static function formatViolation($violation) {
        $result = self::formatViolations([$violation]);
        return $result[0] ?? null;
    }

    /**
     * Get a user-friendly summary of all violations
     *
     * @param array $violations Array of violation strings
     * @return string Summary message
     */
    public static function getSummary($violations) {
        if (empty($violations)) {
            return 'Transaction is valid';
        }

        $count = count($violations);
        $types = [];

        foreach ($violations as $violation) {
            if (stripos($violation, 'negative') !== false) {
                $types['negative'] = true;
            } elseif (stripos($violation, 'not balanced') !== false) {
                $types['unbalanced'] = true;
            } elseif (stripos($violation, 'accounting equation') !== false) {
                $types['equation'] = true;
            }
        }

        if (isset($types['equation'])) {
            return 'Transaction would break the accounting equation (critical error)';
        } elseif (isset($types['unbalanced'])) {
            return 'Transaction is not balanced (debits ≠ credits)';
        } elseif (isset($types['negative'])) {
            return 'Transaction would cause negative account balances';
        }

        return sprintf('Transaction has %d validation error%s', $count, $count === 1 ? '' : 's');
    }
}

