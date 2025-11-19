<?php
/**
 * Decline Registration API
 * Declines tenant registration with reason
 * Aligned with: QUERIES.md Query 7.6
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

    $input = json_decode(file_get_contents('php://input'), true);

    if (empty($input['user_id'])) respond(false, 'User ID required', null, 400);
    if (empty($input['reason'])) respond(false, 'Decline reason required', null, 400);

    $userId = $input['user_id'];
    $reason = $input['reason'];
    $adminId = $_SESSION['user_id'];

    $db = Database::getInstance()->getConnection();
    $db->beginTransaction();

    try {
        // Verify user is pending
        $sql = "SELECT registration_status, username FROM users WHERE id = :id AND role = 'tenant'";
        $stmt = $db->prepare($sql);
        $stmt->execute([':id' => $userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) respond(false, 'User not found', null, 404);
        if ($user['registration_status'] !== 'pending') {
            respond(false, 'User is not pending approval', null, 400);
        }

        // Decline user (Query 7.6)
        $sql = "UPDATE users SET
                    registration_status = 'declined',
                    is_active = 0,
                    declined_reason = :reason,
                    approved_by = :admin_id,
                    approved_at = NOW(),
                    updated_at = NOW()
                WHERE id = :user_id";

        $stmt = $db->prepare($sql);
        $stmt->execute([
            ':reason' => $reason,
            ':admin_id' => $adminId,
            ':user_id' => $userId
        ]);

        // Log activity
        $sql = "INSERT INTO activity_logs (user_id, username, user_role, activity_type, details, created_at)
                VALUES (:admin_id, :username, 'admin', 'user', :details, NOW())";

        $stmt = $db->prepare($sql);
        $stmt->execute([
            ':admin_id' => $adminId,
            ':username' => $_SESSION['username'],
            ':details' => "Declined tenant registration for {$user['username']} (User ID: $userId). Reason: $reason"
        ]);

        $db->commit();

        respond(true, 'Registration declined', ['user_id' => $userId], 200);

    } catch (Exception $e) {
        $db->rollBack();
        throw $e;
    }

} catch (Throwable $e) {
    error_log('Decline registration error: ' . $e->getMessage());
    respond(false, 'Server error: ' . $e->getMessage(), null, 500);
}

