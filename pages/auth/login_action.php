<?php
session_start();
require_once '../../config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = $conn->prepare("SELECT * FROM users WHERE (username = ? OR email = ?) AND status = 'active'");
    $stmt->bind_param("ss", $username, $username);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();

    if ($user) {
        $password_ok = false;

        // Check hashed password (normal case)
        if (password_verify($password, $user['password'])) {
            $password_ok = true;
        }
        // Temporary fallback for development (plain text passwords like 'admin123')
        elseif ($user['password'] === $password) {
            $password_ok = true;
        }

        if ($password_ok) {

            $_SESSION['user_id']       = $user['id'];
            $_SESSION['username']      = $user['username'];
            $_SESSION['role']          = $user['role'];
            $_SESSION['department_id'] = $user['department_id'];

            // === IMPORTANT REDIRECTION ===
            if (in_array($user['role'], ['super_admin', 'dept_admin'])) {
                header("Location: ../../pages/admin/dashboard.php");
                exit;
            } else {
                header("Location: ../../pages/dashboard/index.php");
                exit;
            }
        }
    }

    // Login failed
    $_SESSION['error'] = "Nom d'utilisateur ou mot de passe incorrect !";
    header("Location: ../../pages/auth/login.php");
    exit;
}

// Direct access protection
header("Location: ../../pages/auth/login.php");
exit;
?>