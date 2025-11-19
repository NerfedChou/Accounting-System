<?php
/**
 * Approve Registration API
 * Approves tenant and assigns to company
 * Aligned with: QUERIES.md Query 7.5
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
    if (empty($input['company_id'])) respond(false, 'Company ID required', null, 400);

    $userId = $input['user_id'];
    $companyId = $input['company_id'];
    $adminId = $_SESSION['user_id'];

    $db = Database::getInstance()->getConnection();
    $db->beginTransaction();

    try {
        // Verify user is pending
        $sql = "SELECT registration_status FROM users WHERE id = :id AND role = 'tenant'";
        $stmt = $db->prepare($sql);
        $stmt->execute([':id' => $userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) respond(false, 'User not found', null, 404);
        if ($user['registration_status'] !== 'pending') {
            respond(false, 'User is not pending approval', null, 400);
        }

        // Verify company exists
        $sql = "SELECT id FROM companies WHERE id = :id AND is_active = 1";
        $stmt = $db->prepare($sql);
        $stmt->execute([':id' => $companyId]);
        if (!$stmt->fetch()) respond(false, 'Company not found or inactive', null, 404);

        // Approve user (Query 7.5)
        $sql = "UPDATE users SET
                    registration_status = 'approved',
                    company_id = :company_id,
                    is_active = 1,
                    approved_by = :admin_id,
                    approved_at = NOW(),
                    updated_at = NOW()
                WHERE id = :user_id";

        $stmt = $db->prepare($sql);
        $stmt->execute([
            ':company_id' => $companyId,
            ':admin_id' => $adminId,
            ':user_id' => $userId
        ]);

        // Log activity
        $sql = "INSERT INTO activity_logs (user_id, username, user_role, company_id, activity_type, details, created_at)
                VALUES (:admin_id, :username, 'admin', :company_id, 'user', :details, NOW())";

        $stmt = $db->prepare($sql);
        $stmt->execute([
            ':admin_id' => $adminId,
            ':username' => $_SESSION['username'],
            ':company_id' => $companyId,
            ':details' => "Approved tenant registration (User ID: $userId) and assigned to company ID $companyId"
        ]);

        $db->commit();

        respond(true, 'Registration approved successfully', ['user_id' => $userId, 'company_id' => $companyId], 200);

    } catch (Exception $e) {
        $db->rollBack();
        throw $e;
    }

} catch (Throwable $e) {
    error_log('Approve registration error: ' . $e->getMessage());
    respond(false, 'Server error: ' . $e->getMessage(), null, 500);
}

