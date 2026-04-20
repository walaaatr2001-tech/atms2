<?php
if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php'); exit;
}

switch ($_SESSION['user_role']) {
    case 'super_admin':
    case 'dept_admin':
        header('Location: ../admin/dashboard.php'); exit;
    case 'internal_staff':
        header('Location: internal_staff/index.php'); exit;
    case 'enterprise':
        header('Location: enterprise/index.php'); exit;
    default:
        header('Location: ../auth/login.php'); exit;
}