<?php
/**
 * Admin Tenants API
 * Returns all tenant users
 * Aligned with: USE-CASE-DIAGRAM.md UC-A02
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
    // Check authentication - Aligned with: DATABASE.md users.role
    if (!isset($_SESSION['user_id'])) respond(false, 'Unauthorized', null, 401);
    if ($_SESSION['role'] !== 'admin') respond(false, 'Admin access required', null, 403);

    $db = Database::getInstance()->getConnection();
    $limit = isset($_GET['limit']) ? max(1, min(100, (int)$_GET['limit'])) : 50;

    $sql = "SELECT
                u.id,
                u.username,
                u.company_id,
                u.full_name,
                u.email,
                u.is_active,
                u.last_login,
                u.created_at,
                c.company_name,
                c.is_active as company_is_active
            FROM users u
            LEFT JOIN companies c ON u.company_id = c.id
            WHERE u.role = 'tenant'
            ORDER BY u.created_at DESC
            LIMIT :limit";

    $stmt = $db->prepare($sql);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    $tenants = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Format data
    foreach ($tenants as &$tenant) {
        $tenant['id'] = (int)$tenant['id'];
        $tenant['is_active'] = (bool)$tenant['is_active'];
    }

    respond(true, 'Tenants loaded', $tenants, 200);

} catch (Throwable $e) {
    error_log('Admin tenants error: ' . $e->getMessage());
    respond(false, 'Server error: ' . $e->getMessage(), null, 500);
}

