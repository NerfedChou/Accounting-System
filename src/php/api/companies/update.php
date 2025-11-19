<?php
/**
 * Update Company API
 * Updates company information
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

    $companyId = isset($input['id']) ? (int)$input['id'] : 0;
    if ($companyId <= 0) respond(false, 'Invalid company ID', null, 400);

    $companyName = isset($input['company_name']) ? trim($input['company_name']) : '';
    if (empty($companyName)) respond(false, 'Company name is required', null, 400);

    $db = Database::getInstance()->getConnection();

    $sql = "UPDATE companies SET
                company_name = :company_name,
                email = :email,
                phone = :phone,
                address = :address,
                city = :city,
                state = :state,
                postal_code = :postal_code,
                country = :country
            WHERE id = :id";

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
        ':id' => $companyId
    ]);

    respond(true, 'Company updated successfully', ['id' => $companyId], 200);

} catch (Throwable $e) {
    error_log('Update company error: ' . $e->getMessage());
    respond(false, 'Server error: ' . $e->getMessage(), null, 500);
}

