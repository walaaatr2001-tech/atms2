<?php
/**
 * Authentication & Authorization System - AT-AMS
 * Updated for new RBAC (roles + permissions)
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

function requireLogin() {
    if (!isset($_SESSION['user_id'])) {
        header("Location: ../pages/auth/login.php");
        exit();
    }
}

function getUser() {
    if (!isset($_SESSION['user_id'])) {
        return null;
    }

    $conn = getDB();
    $user_id = (int)$_SESSION['user_id'];
    
    $result = $conn->query("SELECT u.*, d.name as department_name 
            FROM users u 
            LEFT JOIN departments d ON u.department_id = d.id 
            WHERE u.id = $user_id");
    
    if (!$result || $result->num_rows === 0) {
        return null;
    }
    
    $user = $result->fetch_assoc();
    
    $stmt = $conn->prepare("SELECT r.name FROM user_roles ur JOIN roles r ON ur.role_id = r.id WHERE ur.user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $roles_result = $stmt->get_result();
    $roles = [];
    while ($row = $roles_result->fetch_assoc()) {
        $roles[] = $row['name'];
    }
    $user['roles'] = $roles;
    
    return $user;
}

// ======================
// 3. Check if user has a specific role
// ======================
function hasRole($roleName) {
    $user = getUser();
    if (!$user || empty($user['roles'])) {
        return false;
    }
    return in_array($roleName, $user['roles']);
}

// ======================
// 4. Check if user has a specific permission
// ======================
function hasPermission($permissionName) {
    if (!isset($_SESSION['user_id'])) {
        return false;
    }

    $conn = getDB();
    $user_id = (int)$_SESSION['user_id'];
    
    $stmt = $conn->prepare("SELECT 1 
            FROM user_roles ur
            JOIN role_permissions rp ON ur.role_id = rp.role_id
            JOIN permissions p ON rp.permission_id = p.id
            WHERE ur.user_id = ? 
              AND p.name = ?
            LIMIT 1");
    $stmt->bind_param("is", $user_id, $permissionName);
    $stmt->execute();
    $stmt->store_result();
    
    return $stmt->num_rows() > 0;
}

// ======================
// 5. Logout function
// ======================
function logout() {
    session_destroy();
    header("Location: ../pages/auth/login.php");
    exit();
}

// Make sure session is started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}