<?php
/**
 * Create Company API
 * Creates a new company
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');
session_start();
require_once __DIR__ . '/../../config/database.php';

function respond($ok, $msg = '', $data = null, $code = 200) {
    http_response_code($code);
    echo json_encode(['success' => $ok, 'message' => $msg, 'data' => $data]);
    exit;
}

try {
    if (!isset($_SESSION['user_id'])) respond(false, 'Unauthorized', null, 401);
    if ($_SESSION['role'] !== 'admin') respond(false, 'Admin access required', null, 403);

    $input = json_decode(file_get_contents('php://input'), true);

    $companyName = isset($input['company_name']) ? trim($input['company_name']) : '';
    if (empty($companyName)) respond(false, 'Company name is required', null, 400);

    $db = Database::getInstance()->getConnection();

    $sql = "INSERT INTO companies (
                company_name, email, phone, address, city, state, postal_code, country,
                is_active, created_by, created_at
            ) VALUES (
                :company_name, :email, :phone, :address, :city, :state, :postal_code, :country,
                1, :created_by, NOW()
            )";

    $stmt = $db->prepare($sql);
    $stmt->execute([
        ':company_name' => $companyName,
        ':email' => $input['email'] ?? null,
        ':phone' => $input['phone'] ?? null,
        ':address' => $input['address'] ?? null,
        ':city' => $input['city'] ?? null,
        ':state' => $input['state'] ?? null,
        ':postal_code' => $input['postal_code'] ?? null,
        ':country' => $input['country'] ?? 'USA',
        ':created_by' => $_SESSION['user_id']
    ]);

    $companyId = $db->lastInsertId();

    respond(true, 'Company created successfully', ['id' => $companyId], 201);

} catch (Throwable $e) {
    error_log('Create company error: ' . $e->getMessage());
    respond(false, 'Server error: ' . $e->getMessage(), null, 500);
}

