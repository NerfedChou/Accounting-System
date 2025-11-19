<?php
/**
 * Update Own Company API
 * Allows tenant to update their company information
 * Aligned with: DATABASE-UPDATED.md companies table
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: application/json');
session_start();
require_once __DIR__ . '/../../config/database.php';
function respond($ok, $msg = '', $data = null, $code = 200) {
    http_response_code($code);
    echo json_encode(['success' => $ok, 'message' => $msg, 'data' => $data]);
    exit;
}
try {
    // Check authentication - Tenant only
    if (!isset($_SESSION['user_id'])) respond(false, 'Unauthorized', null, 401);
    if ($_SESSION['role'] !== 'tenant') respond(false, 'Tenant access required', null, 403);
    if (!isset($_SESSION['company_id'])) respond(false, 'No company assigned', null, 403);
    $companyId = $_SESSION['company_id'];
    $input = json_decode(file_get_contents('php://input'), true);
    // Validate required fields
    if (empty($input['company_name'])) respond(false, 'Company name is required', null, 400);
    $db = Database::getInstance()->getConnection();
    // Verify company belongs to tenant
    if (isset($input['id']) && $input['id'] != $companyId) {
        respond(false, 'Cannot update other companies', null, 403);
    }
    // Update company
    $sql = "UPDATE companies SET
                company_name = :company_name,
                address = :address,
                city = :city,
                state = :state,
                postal_code = :postal_code,
                country = :country,
                phone = :phone,
                email = :email,
                website = :website,
                tax_id = :tax_id,
                fiscal_year_start = :fiscal_year_start,
                currency_code = :currency_code,
                updated_at = NOW()
            WHERE id = :id";
    $stmt = $db->prepare($sql);
    $stmt->execute([
        ':company_name' => $input['company_name'],
        ':address' => $input['address'] ?? null,
        ':city' => $input['city'] ?? null,
        ':state' => $input['state'] ?? null,
        ':postal_code' => $input['postal_code'] ?? null,
        ':country' => $input['country'] ?? null,
        ':phone' => $input['phone'] ?? null,
        ':email' => $input['email'] ?? null,
        ':website' => $input['website'] ?? null,
        ':tax_id' => $input['tax_id'] ?? null,
        ':fiscal_year_start' => $input['fiscal_year_start'] ?? null,
        ':currency_code' => $input['currency_code'] ?? 'USD',
        ':id' => $companyId
    ]);
    respond(true, 'Company updated successfully', ['id' => $companyId], 200);
} catch (Throwable $e) {
    error_log('Update own company error: ' . $e->getMessage());
    respond(false, 'Server error: ' . $e->getMessage(), null, 500);
}
