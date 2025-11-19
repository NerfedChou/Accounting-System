<?php
/**
 * Admin Activity Logs API
 * Returns system-wide activity logs for audit trail
 * Aligned with: Admin monitoring requirements
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
    // Check authentication - Admin only
    if (!isset($_SESSION['user_id'])) respond(false, 'Unauthorized', null, 401);
    if ($_SESSION['role'] !== 'admin') respond(false, 'Admin access required', null, 403);

    $db = Database::getInstance()->getConnection();
    $limit = isset($_GET['limit']) ? max(1, min(500, (int)$_GET['limit'])) : 100;

    // Get activity logs with user and company information
    $sql = "SELECT
                al.id,
                al.user_id,
                al.username,
                al.user_role,
                al.company_id,
                al.activity_type,
                al.details,
                al.ip_address,
                al.created_at,
                u.full_name as user_name,
                c.company_name
            FROM activity_logs al
            LEFT JOIN users u ON al.user_id = u.id
            LEFT JOIN companies c ON al.company_id = c.id
            ORDER BY al.created_at DESC
            LIMIT :limit";

    $stmt = $db->prepare($sql);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get statistics for today
    $today = date('Y-m-d');

    $stats = [];

    // Today's activity count
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM activity_logs WHERE DATE(created_at) = :today");
    $stmt->execute([':today' => $today]);
    $stats['today_activity'] = (int)$stmt->fetch(PDO::FETCH_ASSOC)['count'];

    // Active users today (unique users who logged in)
    $stmt = $db->prepare("SELECT COUNT(DISTINCT user_id) as count FROM activity_logs WHERE activity_type = 'login' AND DATE(created_at) = :today");
    $stmt->execute([':today' => $today]);
    $stats['active_users'] = (int)$stmt->fetch(PDO::FETCH_ASSOC)['count'];

    // Transactions created today
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM activity_logs WHERE activity_type = 'transaction' AND DATE(created_at) = :today");
    $stmt->execute([':today' => $today]);
    $stats['transactions_today'] = (int)$stmt->fetch(PDO::FETCH_ASSOC)['count'];

    // Voided transactions today
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM activity_logs WHERE activity_type = 'void' AND DATE(created_at) = :today");
    $stmt->execute([':today' => $today]);
    $stats['voided_today'] = (int)$stmt->fetch(PDO::FETCH_ASSOC)['count'];

    respond(true, 'Activity logs loaded', [
        'logs' => $logs,
        'stats' => $stats
    ], 200);

} catch (Throwable $e) {
    error_log('Admin activity logs error: ' . $e->getMessage());
    respond(false, 'Server error: ' . $e->getMessage(), null, 500);
}

