<?php
/**
 * Logout API
 * Destroys session and clears cookies
 * Prevents back button access with cache headers
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Prevent caching
header('Cache-Control: no-cache, no-store, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: Thu, 01 Jan 1970 00:00:00 GMT');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  echo json_encode(['success'=>false,'message'=>'Method not allowed']);
  exit;
}

session_start();

// Clear all session variables
$_SESSION = array();

// Destroy session cookie
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time()-3600, '/');
}

// Destroy session
session_destroy();

echo json_encode(['success'=>true,'message'=>'Logged out successfully']);

