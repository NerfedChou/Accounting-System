<?php
/**
 * Admin Statistics API
 * Returns system-wide statistics for admin dashboard
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

    // Get total companies
    $sql = "SELECT COUNT(*) as total FROM companies WHERE is_active = 1";
    $stmt = $db->query($sql);
    $totalCompanies = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

    // Get total tenants
    $sql = "SELECT COUNT(*) as total FROM users WHERE role = 'tenant' AND is_active = 1";
    $stmt = $db->query($sql);
    $totalTenants = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

    // Get total posted transactions
    $sql = "SELECT COUNT(*) as total FROM transactions WHERE status_id = 2";
    $stmt = $db->query($sql);
    $totalTransactions = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

    // Get pending transactions count
    $sql = "SELECT COUNT(*) as total FROM transactions WHERE status_id = 1";
    $stmt = $db->query($sql);
    $pendingTransactions = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

    // Get pending registrations count
    $sql = "SELECT COUNT(*) as total FROM users WHERE role = 'tenant' AND registration_status = 'pending'";
    $stmt = $db->query($sql);
    $pendingRegistrations = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

    $stats = [
        'total_companies' => (int)$totalCompanies,
        'total_tenants' => (int)$totalTenants,
        'total_transactions' => (int)$totalTransactions,
        'pending_transactions' => (int)$pendingTransactions,
        'pending_registrations' => (int)$pendingRegistrations
    ];

    respond(true, 'Statistics loaded', $stats, 200);

} catch (Throwable $e) {
    error_log('Admin stats error: ' . $e->getMessage());
    respond(false, 'Server error: ' . $e->getMessage(), null, 500);
}

