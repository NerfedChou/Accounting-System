<?php
/**
 * Transaction Pre-flight Validation API
 * Validates a transaction WITHOUT saving it
 * Used for real-time frontend validation
 *
 * This endpoint allows the frontend to check if a transaction would be valid
 * before the user submits it, providing immediate feedback.
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store, must-revalidate');
session_start();

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../utils/transaction_processor.php';

function respond($success, $message = '', $data = null, $code = 200) {
    http_response_code($code);
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data
    ]);
    exit;
}

try {
    // Check authentication
    if (!isset($_SESSION['user_id'])) {
        respond(false, 'Unauthorized', null, 401);
    }

    if ($_SESSION['role'] !== 'tenant') {
        respond(false, 'Tenant access required', null, 403);
    }

    if (!isset($_SESSION['company_id'])) {
        respond(false, 'No company assigned', null, 403);
    }

    // Get input
    $input = json_decode(file_get_contents('php://input'), true);

    if (empty($input['lines']) || !is_array($input['lines'])) {
        respond(false, 'No transaction lines provided', null, 400);
    }

    $company_id = $_SESSION['company_id'];
    $lines = $input['lines'];

    // Validate line format
    foreach ($lines as $line) {
        if (empty($line['account_id']) || empty($line['line_type']) || !isset($line['amount'])) {
            respond(false, 'Invalid line data: missing account_id, line_type, or amount', null, 400);
        }

        if (!in_array($line['line_type'], ['debit', 'credit'])) {
            respond(false, 'Invalid line_type: must be "debit" or "credit"', null, 400);
        }

        if ((float)$line['amount'] <= 0) {
            respond(false, 'Line amounts must be greater than 0', null, 400);
        }
    }

    // Connect to database
    $pdo = Database::getInstance()->getConnection();

    // Create processor and validate
    $processor = new TransactionProcessor($pdo);

    $validation = $processor->validateTransactionLines($company_id, $lines);

    // Return validation results
    respond(true, 'Validation complete', [
        'valid' => $validation['valid'],
        'validation_message' => $validation['message'],
        'details' => $validation['details'] ?? [],
        'warnings' => $validation['warnings'] ?? [],
        'balance_changes' => $validation['details']['balance_changes'] ?? []
    ]);

} catch (Exception $e) {
    error_log('Transaction validation error: ' . $e->getMessage());
    respond(false, 'Validation error: ' . $e->getMessage(), null, 500);
}

