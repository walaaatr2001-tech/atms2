<?php
/**
 * SECURE Configuration - AT-AMS
 * Password hashing + CSRF protection added
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ==================== PATHS ====================
define('BASE_PATH', __DIR__ . '/..');
define('UPLOAD_PATH', BASE_PATH . '/uploads/');
define('APP_NAME', 'AT-AMS - Algérie Télécom');

// ==================== DATABASE ====================
require_once __DIR__ . '/database.php';

// ==================== SECURITY FUNCTIONS ====================

// Generate CSRF Token
function generateCSRF() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Validate CSRF Token
function validateCSRF($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// ==================== SECURE LOGIN FUNCTION ====================
function login($email_or_username, $password) {
    $conn = getDB();
    $email_or_username = $conn->real_escape_string(trim($email_or_username));

    $result = $conn->query("
        SELECT * FROM users 
        WHERE (email = '$email_or_username' OR username = '$email_or_username')
        AND status = 'active'
    ");

    if ($result && $result->num_rows > 0) {
        $user = $result->fetch_assoc();

        if (password_verify($password, $user['password']) || $user['password'] === $password) {
            if ($user['password'] === $password) {
                $new_hash = password_hash($password, PASSWORD_DEFAULT);
                $conn->query("UPDATE users SET password = '$new_hash' WHERE id = " . $user['id']);
            }

            $_SESSION['user_id']       = $user['id'];
            $_SESSION['user_role']     = $user['role'];
            $_SESSION['user_name']     = $user['first_name'] . ' ' . $user['last_name'];
            $_SESSION['department_id'] = $user['department_id'] ?? null;

            session_regenerate_id(true);
            return true;
        }
    }
    return false;
}

// ==================== OTHER FUNCTIONS ====================
function redirect($url) {
    header("Location: $url");
    exit;
}

function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function generateRefNumber() {
    return 'AT-' . date('Y') . '-' . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
}

function logAction($action, $type, $type_id) {
    $conn = getDB();
    $user_id = $_SESSION['user_id'] ?? 0;
    $action_esc = $conn->real_escape_string($action);
    $type_esc = $conn->real_escape_string($type);
    $conn->query("INSERT INTO audit_logs (user_id, action, entity_type, entity_id, created_at) 
                  VALUES ($user_id, '$action_esc', '$type_esc', $type_id, NOW())");
}