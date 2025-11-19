<?php
/**
 * Get Registration Details API
 * Returns full details for a specific registration
 * Aligned with: QUERIES.md Query 7.3
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
    // Check admin auth
    if (!isset($_SESSION['user_id'])) respond(false, 'Unauthorized', null, 401);
    if ($_SESSION['role'] !== 'admin') respond(false, 'Admin access required', null, 403);

    if (empty($_GET['id'])) respond(false, 'User ID required', null, 400);

    $userId = $_GET['id'];
    $db = Database::getInstance()->getConnection();

    // Get full registration details (Query 7.3)
    $sql = "SELECT
                u.id,
                u.username,
                u.email,
                u.full_name,
                u.company_name_requested,
                u.business_type,
                u.registration_notes,
                u.registration_status,
                u.registration_date,
                u.approved_by,
                u.approved_at,
                u.declined_reason,
                u.is_active,
                u.created_at,
                admin.full_name as approved_by_name,
                admin.email as approved_by_email,
                c.company_name as assigned_company_name
            FROM users u
            LEFT JOIN users admin ON u.approved_by = admin.id
            LEFT JOIN companies c ON u.company_id = c.id
            WHERE u.id = :user_id AND u.role = 'tenant'";

    $stmt = $db->prepare($sql);
    $stmt->execute([':user_id' => $userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) respond(false, 'User not found', null, 404);

    respond(true, 'Registration details loaded', $user, 200);

} catch (Throwable $e) {
    error_log('Get registration details error: ' . $e->getMessage());
    respond(false, 'Server error: ' . $e->getMessage(), null, 500);
}

