<?php
/**
 * Get Company Tenants API
 * Returns all tenants for a specific company
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

    $companyId = isset($_GET['company_id']) ? (int)$_GET['company_id'] : 0;
    if ($companyId <= 0) respond(false, 'Invalid company ID', null, 400);

    $db = Database::getInstance()->getConnection();

    $sql = "SELECT id, company_id, username, email, full_name, is_active, created_at, last_login
            FROM users
            WHERE company_id = :company_id AND role = 'tenant'
            ORDER BY created_at DESC";

    $stmt = $db->prepare($sql);
    $stmt->execute([':company_id' => $companyId]);
    $tenants = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($tenants as &$tenant) {
        $tenant['id'] = (int)$tenant['id'];
        $tenant['company_id'] = (int)$tenant['company_id'];
        $tenant['is_active'] = (bool)$tenant['is_active'];
    }

    respond(true, 'Tenants loaded', $tenants, 200);

} catch (Throwable $e) {
    error_log('Get tenants error: ' . $e->getMessage());
    respond(false, 'Server error: ' . $e->getMessage(), null, 500);
}

