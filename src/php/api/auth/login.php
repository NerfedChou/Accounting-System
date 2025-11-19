<?php
/**
 * Simple Login API - School Project Version
 * NO PASSWORD HASHING - Plain text for simplicity
 * Reference.md: "we dont need a security because this is just a school project"
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  echo json_encode(['success'=>false,'message'=>'Method not allowed']);
  exit;
}

session_start();
require_once __DIR__ . '/../../config/database.php';

function respond($ok,$msg='',$data=null,$code=200){
  http_response_code($code);
  echo json_encode(['success'=>$ok,'message'=>$msg,'data'=>$data]);
  exit;
}

try {
  $raw = file_get_contents('php://input');
  $body = json_decode($raw, true) ?: [];
  $user = trim($body['username'] ?? '');
  $pass = $body['password'] ?? '';

  if ($user === '' || $pass === '') {
    respond(false,'Username and password are required',null,400);
  }

  // Get database connection
  $db = Database::getInstance()->getConnection();

  // Query user - SIMPLE: Compare plain text password
  $stmt = $db->prepare("
    SELECT
      u.id, u.company_id, u.username, u.email, u.password,
      u.full_name, u.role, u.is_active, u.registration_status,
      u.declined_reason, u.approved_at, u.created_at, u.last_login,
      u.deactivation_reason,
      c.company_name, c.currency_code, c.is_active as company_is_active
    FROM users u
    LEFT JOIN companies c ON u.company_id = c.id
    WHERE u.username = :username
    LIMIT 1
  ");
  $stmt->execute([':username' => $user]);
  $row = $stmt->fetch(PDO::FETCH_ASSOC);

  if (!$row) {
    respond(false,'Invalid username or password',null,401);
  }

  // SIMPLE PASSWORD CHECK - Plain text comparison (school project)
  if ($pass !== $row['password']) {
    respond(false,'Invalid username or password',null,401);
  }

  // Check registration status for tenants
  if ($row['role'] === 'tenant') {
    // Allow login regardless of status (pending/declined/approved/deactivated)
    // Frontend will redirect them to the appropriate page:
    // - is_active = 0 → /tenant/account-deactivated.html
    // - registration_status = 'pending' → /tenant/pending-approval.html
    // - registration_status = 'declined' → /tenant/registration-declined.html
    // - registration_status = 'approved' && is_active = 1 → /tenant/dashboard.html
  }

  // Only block admin accounts if deactivated
  if (!$row['is_active'] && $row['role'] === 'admin') {
    respond(false,'Account is deactivated by administrator',null,403);
  }

  // Update last login
  try {
    $db->prepare('UPDATE users SET last_login = NOW() WHERE id = :id')
       ->execute([':id' => $row['id']]);
  } catch (Throwable $e) {
    // Ignore error if last_login column doesn't exist
  }

  // Create session
  $_SESSION['user_id'] = (int)$row['id'];
  $_SESSION['username'] = $row['username'];
  $_SESSION['email'] = $row['email'];
  $_SESSION['full_name'] = $row['full_name'];
  $_SESSION['role'] = $row['role'];
  $_SESSION['company_id'] = $row['company_id'];
  $_SESSION['company_name'] = $row['company_name'];
  $_SESSION['currency_code'] = $row['currency_code'] ?? 'USD';
  $_SESSION['registration_status'] = $row['registration_status'] ?? 'approved';
  $_SESSION['declined_reason'] = $row['declined_reason'] ?? null;
  $_SESSION['approved_at'] = $row['approved_at'] ?? null;
  $_SESSION['created_at'] = $row['created_at'] ?? null;
  $_SESSION['last_login'] = $row['last_login'] ?? null;
  $_SESSION['deactivation_reason'] = $row['deactivation_reason'] ?? null;
  $_SESSION['company_is_active'] = $row['company_is_active'] ?? null;

  // Prepare response
  $data = [
    'user' => [
      'id' => (int)$row['id'],
      'username' => $row['username'],
      'email' => $row['email'],
      'full_name' => $row['full_name'],
      'role' => $row['role'],
      'is_active' => (int)$row['is_active'],
      'company_id' => $row['company_id'] ? (int)$row['company_id'] : null,
      'registration_status' => $row['registration_status'] ?? 'approved',
      'declined_reason' => $row['declined_reason'] ?? null,
      'approved_at' => $row['approved_at'] ?? null,
      'created_at' => $row['created_at'] ?? null,
      'last_login' => $row['last_login'] ?? null,
      'deactivation_reason' => $row['deactivation_reason'] ?? null,
    ],
    'company' => ($row['role'] === 'tenant' && $row['company_id']) ? [
      'id' => (int)$row['company_id'],
      'company_name' => $row['company_name'],
      'currency_code' => $row['currency_code'] ?? 'USD',
      'is_active' => (int)($row['company_is_active'] ?? 1),
    ] : null,
    'redirect' => $row['role'] === 'admin' ? '/admin/dashboard.html' : '/tenant/dashboard.html',
  ];

  respond(true, 'Login successful', $data, 200);

} catch (PDOException $e) {
  error_log('Login DB error: ' . $e->getMessage());
  respond(false, 'Database connection error: ' . $e->getMessage(), null, 500);
} catch (Throwable $e) {
  error_log('Login error: ' . $e->getMessage());
  respond(false, 'Server error: ' . $e->getMessage(), null, 500);
}

