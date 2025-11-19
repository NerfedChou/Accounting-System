<?php
/**
 * Toggle Company Status API
 * Activate or deactivate a company
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

    $input = json_decode(file_get_contents('php://input'), true);

    $companyId = isset($input['id']) ? (int)$input['id'] : 0;
    if ($companyId <= 0) respond(false, 'Invalid company ID', null, 400);

    $isActive = isset($input['is_active']) ? (bool)$input['is_active'] : false;

    $db = Database::getInstance()->getConnection();

    // Start transaction
    $db->beginTransaction();

    try {
        // Update company status
        $sql = "UPDATE companies SET is_active = :is_active WHERE id = :id";
        $stmt = $db->prepare($sql);
        $stmt->execute([
            ':is_active' => $isActive ? 1 : 0,
            ':id' => $companyId
        ]);

        // If deactivating company, also deactivate all tenants in that company
        if (!$isActive) {
            $deactivationReason = "Company deactivated by administrator";
            $sqlTenants = "UPDATE users
                          SET is_active = 0,
                              deactivation_reason = :reason,
                              updated_at = CURRENT_TIMESTAMP
                          WHERE company_id = :company_id
                          AND role = 'tenant'
                          AND is_active = 1";
            $stmtTenants = $db->prepare($sqlTenants);
            $stmtTenants->execute([
                ':reason' => $deactivationReason,
                ':company_id' => $companyId
            ]);
            $affectedTenants = $stmtTenants->rowCount();

            // Log the bulk deactivation
            if ($affectedTenants > 0) {
                $logStmt = $db->prepare("
                    INSERT INTO activity_logs (user_id, username, user_role, activity_type, details, ip_address, company_id)
                    VALUES (:user_id, :username, :role, 'company', :details, :ip, :company_id)
                ");
                $logStmt->execute([
                    ':user_id' => $_SESSION['user_id'],
                    ':username' => $_SESSION['username'],
                    ':role' => 'admin',
                    ':details' => "Company ID $companyId deactivated - $affectedTenants tenant(s) automatically deactivated",
                    ':ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                    ':company_id' => $companyId
                ]);
            }
        }

        // Commit transaction
        $db->commit();

        respond(true, 'Company status updated', ['id' => $companyId, 'is_active' => $isActive], 200);

    } catch (Exception $e) {
        $db->rollBack();
        throw $e;
    }

} catch (Throwable $e) {
    error_log('Toggle company error: ' . $e->getMessage());
    respond(false, 'Server error: ' . $e->getMessage(), null, 500);
}

