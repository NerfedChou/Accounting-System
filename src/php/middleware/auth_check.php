<?php
/**
 * Authentication and Authorization Middleware
 * Checks if user is logged in and account is active
 * Prevents deactivated users from accessing write-operation API endpoints
 */
class AuthCheck {
    /**
     * Verify user is authenticated and optionally active
     * @param string $required_role Optional role requirement ('admin' or 'tenant')
     * @param bool $require_active Whether to check is_active status (default: true)
     * @return array User data from database
     */
    public static function requireAuth($required_role = null, $require_active = true) {
        // Check if session exists
        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Unauthorized - Please login']);
            exit;
        }
        // Get real-time user data from database
        require_once __DIR__ . '/../config/database.php';
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("
            SELECT 
                u.id, u.username, u.role, u.company_id, u.is_active,
                u.registration_status, u.deactivation_reason,
                c.is_active as company_is_active
            FROM users u
            LEFT JOIN companies c ON u.company_id = c.id
            WHERE u.id = :user_id
            LIMIT 1
        ");
        $stmt->execute([':user_id' => $_SESSION['user_id']]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        // User not found in database
        if (!$user) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Invalid session - User not found']);
            exit;
        }
        // Check if user account is active (only if required)
        if ($require_active && $user['is_active'] != 1) {
            $reason = $user['deactivation_reason'] ?? 'Account has been deactivated';
            http_response_code(403);
            echo json_encode([
                'success' => false, 
                'message' => 'Account is deactivated: ' . $reason,
                'deactivated' => true
            ]);
            exit;
        }
        // Check role requirement
        if ($required_role !== null && $user['role'] !== $required_role) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => ucfirst($required_role) . ' access required']);
            exit;
        }
        // For tenants with company, check if company is active (only if checking active status)
        if ($require_active && $user['role'] === 'tenant' && $user['company_id']) {
            if ($user['company_is_active'] != 1) {
                http_response_code(403);
                echo json_encode([
                    'success' => false, 
                    'message' => 'Your company has been deactivated',
                    'company_deactivated' => true
                ]);
                exit;
            }
        }
        // For tenants, check registration status (only if checking active status)
        if ($require_active && $user['role'] === 'tenant') {
            $status = $user['registration_status'] ?? 'approved';
            if ($status === 'pending') {
                http_response_code(403);
                echo json_encode([
                    'success' => false, 
                    'message' => 'Registration is pending approval',
                    'pending' => true
                ]);
                exit;
            } elseif ($status === 'declined') {
                http_response_code(403);
                echo json_encode([
                    'success' => false, 
                    'message' => 'Registration has been declined',
                    'declined' => true
                ]);
                exit;
            }
        }
        return $user;
    }
    /**
     * Require tenant access with optional active check
     * @param bool $require_active Whether to check is_active (default: true for write ops, false for read ops)
     */
    public static function requireTenant($require_active = true) {
        return self::requireAuth('tenant', $require_active);
    }
    /**
     * Require admin access with active account
     */
    public static function requireAdmin() {
        return self::requireAuth('admin', true);
    }
}
