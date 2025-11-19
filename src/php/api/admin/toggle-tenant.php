<?php
/**
 * Admin Toggle Tenant Status API
 * Enhanced with deactivation reason and company reassignment
 * Aligned with: USE-CASE-DIAGRAM.md UC-A02
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store, must-revalidate');
session_start();

require_once __DIR__ . '/../../config/database.php';

function respond($ok, $msg = '', $data = null, $code = 200) {
    http_response_code($code);
    echo json_encode(['success' => $ok, 'message' => $msg, 'data' => $data]);
    exit;
}

try {
    // Check authentication
    if (!isset($_SESSION['user_id'])) respond(false, 'Unauthorized', null, 401);
    if ($_SESSION['role'] !== 'admin') respond(false, 'Admin access required', null, 403);

    // Get input
    $input = json_decode(file_get_contents('php://input'), true);

    if (!$input) {
        respond(false, 'Invalid JSON input', null, 400);
    }

    $user_id = $input['user_id'] ?? null;
    $is_active = $input['is_active'] ?? null;
    $deactivation_reason = $input['deactivation_reason'] ?? null;
    $company_id = $input['company_id'] ?? null;
    $clear_deactivation_reason = $input['clear_deactivation_reason'] ?? false;

    // Validation
    if ($user_id === null || $is_active === null) {
        respond(false, 'Missing required fields: user_id, is_active', null, 400);
    }

    $is_active = (int)$is_active;

    // If deactivating, require reason
    if ($is_active === 0 && empty($deactivation_reason)) {
        respond(false, 'Deactivation reason is required', null, 400);
    }

    $db = Database::getInstance()->getConnection();

    // Build UPDATE query
    $updates = ['is_active = :is_active'];
    $params = [':is_active' => $is_active, ':user_id' => $user_id];

    // If deactivating, set reason
    if ($is_active === 0 && !empty($deactivation_reason)) {
        $updates[] = 'deactivation_reason = :deactivation_reason';
        $params[':deactivation_reason'] = $deactivation_reason;
    }

    // If activating and company_id provided, update company assignment
    if ($is_active === 1 && $company_id !== null) {
        $updates[] = 'company_id = :company_id';
        $params[':company_id'] = (int)$company_id;
    }

    // If activating, clear deactivation reason
    if ($is_active === 1 && $clear_deactivation_reason) {
        $updates[] = 'deactivation_reason = NULL';
    }

    $sql = "UPDATE users SET " . implode(', ', $updates) . ", updated_at = CURRENT_TIMESTAMP WHERE id = :user_id";

    $stmt = $db->prepare($sql);
    $stmt->execute($params);

    if ($stmt->rowCount() > 0) {
        // Log activity
        $action = $is_active === 1 ? 'activated' : 'deactivated';
        $details = "Tenant ID $user_id $action";

        if ($is_active === 0 && !empty($deactivation_reason)) {
            $details .= " - Reason: $deactivation_reason";
        }
        if ($is_active === 1 && $company_id !== null) {
            $details .= " - Assigned to company ID $company_id";
        }

        $logStmt = $db->prepare("
            INSERT INTO activity_logs (user_id, username, user_role, activity_type, details, ip_address)
            VALUES (:user_id, :username, :role, 'user', :details, :ip)
        ");
        $logStmt->execute([
            ':user_id' => $_SESSION['user_id'],
            ':username' => $_SESSION['username'],
            ':role' => 'admin',
            ':details' => $details,
            ':ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
        ]);

        respond(true, $is_active === 1 ? 'Tenant activated successfully' : 'Tenant deactivated successfully');
    } else {
        respond(false, 'No changes made or user not found', null, 404);
    }

} catch (Throwable $e) {
    error_log('Toggle tenant error: ' . $e->getMessage());
    respond(false, 'Server error: ' . $e->getMessage(), null, 500);
}

