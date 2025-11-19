<?php
/**
 * List Companies API
 * Admin only - returns list of all companies
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

    $db = Database::getInstance()->getConnection();

    // Get limit from query parameter (default: all)
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 0;

    // Build SQL query
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
                c.tax_id,
                c.fiscal_year_start,
                c.currency_code,
                c.is_active,
                c.created_at,
                c.updated_at,
                u.full_name as created_by_name,
                (SELECT COUNT(*) FROM users WHERE company_id = c.id AND role = 'tenant') as tenant_count
            FROM companies c
            LEFT JOIN users u ON c.created_by = u.id
            ORDER BY c.created_at DESC";

    // Add limit if specified
    if ($limit > 0) {
        $sql .= " LIMIT :limit";
    }

    $stmt = $db->prepare($sql);

    if ($limit > 0) {
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    }

    $stmt->execute();
    $companies = $stmt->fetchAll(PDO::FETCH_ASSOC);

    respond(true, 'Companies loaded', $companies, 200);

} catch (Throwable $e) {
    error_log('List companies error: ' . $e->getMessage());
    respond(false, 'Server error: ' . $e->getMessage(), null, 500);
}

