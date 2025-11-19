<?php
/**
 * Get Company Details API
 * Admin only - returns company details by ID
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
    // Check authentication
    if (!isset($_SESSION['user_id'])) {
        respond(false, 'Unauthorized', null, 401);
    }

    if ($_SESSION['role'] !== 'admin') {
        respond(false, 'Admin access required', null, 403);
    }

    // Get company ID from query parameter
    $companyId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

    if ($companyId <= 0) {
        respond(false, 'Invalid company ID', null, 400);
    }

    $db = Database::getInstance()->getConnection();

    // Get company details
    $sql = "SELECT
                c.id,
                c.company_name,
                c.address,
                c.city,
                c.state,
                c.postal_code,
                c.country,
                c.phone,
                c.email,
                c.website,
                c.logo_path,
                c.tax_id,
                c.fiscal_year_start,
                c.currency_code,
                c.is_active,
                c.created_at,
                c.updated_at,
                u.full_name as created_by_name
            FROM companies c
            LEFT JOIN users u ON c.created_by = u.id
            WHERE c.id = :id";

    $stmt = $db->prepare($sql);
    $stmt->execute([':id' => $companyId]);
    $company = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$company) {
        respond(false, 'Company not found', null, 404);
    }

    // Get tenant count for this company
    $sql = "SELECT COUNT(*) as tenant_count
            FROM users
            WHERE company_id = :company_id AND role = 'tenant'";

    $stmt = $db->prepare($sql);
    $stmt->execute([':company_id' => $companyId]);
    $tenantData = $stmt->fetch(PDO::FETCH_ASSOC);
    $company['tenant_count'] = $tenantData['tenant_count'] ?? 0;

    respond(true, 'Company loaded', $company, 200);

} catch (Throwable $e) {
    error_log('Get company error: ' . $e->getMessage());
    respond(false, 'Server error: ' . $e->getMessage(), null, 500);
}

