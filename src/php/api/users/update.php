<?php
/**
 * Update User API
 * Updates tenant user information
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
    if (!isset($_SESSION['user_id'])) respond(false, 'Unauthorized', null, 401);
    if ($_SESSION['role'] !== 'admin') respond(false, 'Admin access required', null, 403);

    $input = json_decode(file_get_contents('php://input'), true);

    $userId = isset($input['id']) ? (int)$input['id'] : 0;
    if ($userId <= 0) respond(false, 'Invalid user ID', null, 400);

    $email = isset($input['email']) ? trim($input['email']) : '';
    $fullName = isset($input['full_name']) ? trim($input['full_name']) : '';

    if (empty($email)) respond(false, 'Email is required', null, 400);
    if (empty($fullName)) respond(false, 'Full name is required', null, 400);

    $db = Database::getInstance()->getConnection();

    // Update user (plain text password if provided - SelfPrompt.md)
    if (!empty($input['password'])) {
        $sql = "UPDATE users SET
                    email = :email,
                    full_name = :full_name,
                    password = :password
                WHERE id = :id AND role = 'tenant'";
        $stmt = $db->prepare($sql);
        $stmt->execute([
            ':email' => $email,
            ':full_name' => $fullName,
            ':password' => $input['password'],
            ':id' => $userId
        ]);
    } else {
        $sql = "UPDATE users SET
                    email = :email,
                    full_name = :full_name
                WHERE id = :id AND role = 'tenant'";
        $stmt = $db->prepare($sql);
        $stmt->execute([
            ':email' => $email,
            ':full_name' => $fullName,
            ':id' => $userId
        ]);
    }

    respond(true, 'Tenant updated successfully', ['id' => $userId], 200);

} catch (Throwable $e) {
    error_log('Update user error: ' . $e->getMessage());
    respond(false, 'Server error: ' . $e->getMessage(), null, 500);
}
<?php
/**
 * Get Company Details API
 * Returns single company information
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
    if (!isset($_SESSION['user_id'])) respond(false, 'Unauthorized', null, 401);
    if ($_SESSION['role'] !== 'admin') respond(false, 'Admin access required', null, 403);

    $companyId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    if ($companyId <= 0) respond(false, 'Invalid company ID', null, 400);

    $db = Database::getInstance()->getConnection();

    $sql = "SELECT * FROM companies WHERE id = :id";
    $stmt = $db->prepare($sql);
    $stmt->execute([':id' => $companyId]);
    $company = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$company) respond(false, 'Company not found', null, 404);

    respond(true, 'Company loaded', $company, 200);

} catch (Throwable $e) {
    error_log('Get company error: ' . $e->getMessage());
    respond(false, 'Server error: ' . $e->getMessage(), null, 500);
}

