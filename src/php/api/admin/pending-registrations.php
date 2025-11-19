<?php
/**
 * List Pending Registrations API
 * Returns all tenant registrations for admin review
 * Aligned with: QUERIES.md Query 7.1
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

    $db = Database::getInstance()->getConnection();

    // Get filter parameter (default to 'all')
    $status = isset($_GET['status']) ? $_GET['status'] : 'all';

    // Build WHERE clause based on filter
    $whereClause = "u.role = 'tenant'";
    if ($status !== 'all') {
        $whereClause .= " AND u.registration_status = :status";
    }

    // Get all registrations (Query 7.1)
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
                u.created_at,
                DATEDIFF(NOW(), COALESCE(u.registration_date, u.created_at)) as days_waiting,
                u.approved_by,
                u.approved_at,
                u.declined_reason,
                admin.full_name as approved_by_name,
                c.company_name as assigned_company_name
            FROM users u
            LEFT JOIN users admin ON u.approved_by = admin.id
            LEFT JOIN companies c ON u.company_id = c.id
            WHERE $whereClause
            ORDER BY
                CASE u.registration_status
                    WHEN 'pending' THEN 1
                    WHEN 'approved' THEN 2
                    WHEN 'declined' THEN 3
                END,
                COALESCE(u.registration_date, u.created_at) DESC";

    $stmt = $db->prepare($sql);

    if ($status !== 'all') {
        $stmt->execute([':status' => $status]);
    } else {
        $stmt->execute();
    }

    $registrations = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Count by status
    $counts = [
        'pending' => 0,
        'approved' => 0,
        'declined' => 0,
        'total' => count($registrations)
    ];

    foreach ($registrations as $reg) {
        if (isset($counts[$reg['registration_status']])) {
            $counts[$reg['registration_status']]++;
        }
    }

    respond(true, 'Registrations loaded', [
        'registrations' => $registrations,
        'counts' => $counts,
        'filter' => $status
    ], 200);

} catch (Throwable $e) {
    error_log('List registrations error: ' . $e->getMessage());
    respond(false, 'Server error: ' . $e->getMessage(), null, 500);
}

