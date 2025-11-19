<?php
/**
 * Accounting Equation Integrity Check
 * 
 * This is a diagnostic tool to verify the integrity of the accounting equation
 * for a given company. It reads the current balances of all accounts and
 * checks if Assets = Liabilities + Equity.
 * 
 * How to use:
 * 1. Place this file in the /api/ directory.
 * 2. Access it via your browser after logging in as a tenant.
 *    e.g., https://your-domain.com/php/api/check-balance.php
 */

header('Content-Type: text/html; charset=utf-8');
session_start();

require_once __DIR__ . '/../config/database.php'; // Corrected path to config/database.php

// --- Functions ---

function format_currency($amount) {
    return '$' . number_format($amount, 2);
}

function get_sum_for_account_type($pdo, $company_id, $type_id) {
    $sql = "SELECT COALESCE(SUM(current_balance), 0) as total
            FROM accounts
            WHERE company_id = :company_id
              AND account_type_id = :type_id
              AND is_active = TRUE
              AND is_system_account = 0";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':company_id' => $company_id, ':type_id' => $type_id]);
    return (float)$stmt->fetch(PDO::FETCH_ASSOC)['total'];
}

// --- Main Logic ---

// Check authentication
if (!isset($_SESSION['user_id']) || !isset($_SESSION['company_id'])) {
    die('<h1>Unauthorized</h1><p>Please log in as a tenant first.</p>');
}

$company_id = $_SESSION['company_id'];
$pdo = Database::getInstance()->getConnection();

// Get totals for each category
$total_assets = get_sum_for_account_type($pdo, $company_id, 1); // Type 1 = Asset
$total_liabilities = get_sum_for_account_type($pdo, $company_id, 2); // Type 2 = Liability
$total_equity = get_sum_for_account_type($pdo, $company_id, 3); // Type 3 = Equity

// Perform the check
$liabilities_plus_equity = $total_liabilities + $total_equity;
$difference = $total_assets - $liabilities_plus_equity;
$is_balanced = abs($difference) < 0.01; // Use a small tolerance for float comparison

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Accounting Equation Check</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; line-height: 1.6; color: #333; max-width: 800px; margin: 40px auto; padding: 20px; background-color: #f8f9fa; }
        .container { background-color: #fff; border: 1px solid #dee2e6; border-radius: 8px; padding: 30px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
        h1 { color: #212529; border-bottom: 2px solid #dee2e6; padding-bottom: 15px; margin-top: 0; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 12px 15px; text-align: left; }
        th { background-color: #f1f3f5; color: #495057; font-weight: 600; }
        tr:nth-child(even) { background-color: #f8f9fa; }
        .total-row td { font-weight: bold; border-top: 2px solid #adb5bd; }
        .monospace { font-family: "SF Mono", "Fira Code", "Fira Mono", "Roboto Mono", monospace; }
        .result { padding: 20px; margin-top: 30px; border-radius: 8px; font-size: 1.2em; text-align: center; font-weight: 500; }
        .balanced { background-color: #e6f9f0; color: #099268; border: 1px solid #a6e9c9; }
        .unbalanced { background-color: #fff4e6; color: #f76707; border: 1px solid #ffd8a8; }
        .equation { margin-top: 20px; padding: 15px; background: #f1f3f5; border-radius: 4px; text-align: center; font-size: 1.1em; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Accounting Equation Integrity Check</h1>
        <p>This tool reads the current, real-time balances from your <code>accounts</code> table to verify that the fundamental accounting equation is in balance.</p>

        <table>
            <tr>
                <th>Component</th>
                <th>Total Balance</th>
            </tr>
            <tr>
                <td>Assets</td>
                <td class="monospace"><?= format_currency($total_assets) ?></td>
            </tr>
            <tr>
                <td>Liabilities</td>
                <td class="monospace"><?= format_currency($total_liabilities) ?></td>
            </tr>
            <tr>
                <td>Equity</td>
                <td class="monospace"><?= format_currency($total_equity) ?></td>
            </tr>
            <tr class="total-row">
                <td>Liabilities + Equity</td>
                <td class="monospace"><?= format_currency($liabilities_plus_equity) ?></td>
            </tr>
        </table>

        <div class="equation">
            <strong>Assets = Liabilities + Equity</strong><br>
            <span class="monospace"><?= format_currency($total_assets) ?> = <?= format_currency($liabilities_plus_equity) ?></span>
        </div>

        <?php if ($is_balanced): ?>
            <div class="result balanced">
                ✅ System is BALANCED
            </div>
            <p style="text-align: center; margin-top: 15px;">The difference is <span class="monospace"><?= format_currency($difference) ?></span>. This confirms that your posted transactions are being calculated correctly and the database is in a valid state.</p>
        <?php else: ?>
            <div class="result unbalanced">
                ❌ System is UNBALANCED
            </div>
            <p style="text-align: center; margin-top: 15px;">The difference is <span class="monospace"><?= format_currency($difference) ?></span>. This indicates that one or more posted transactions were recorded incorrectly, pointing to a bug in the transaction creation process.</p>
        <?php endif; ?>

    </div>
</body>
</html>
