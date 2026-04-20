<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['super_admin', 'dept_admin'])) {
    header("Location: ../pages/auth/login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['pdf_file'])) {

    $contract_id = intval($_POST['contract_id']);
    $uploaded_by = $_SESSION['user_id'];

    $upload_dir = "../uploads/contracts/";
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    $file = $_FILES['pdf_file'];
    $new_filename = time() . '_' . basename($file['name']);
    $target_path = $upload_dir . $new_filename;

    if (move_uploaded_file($file['tmp_name'], $target_path)) {

        $stmt = $conn->prepare("
            INSERT INTO documents 
            (reference_number, title, file_path, file_type, department_id, uploaded_by, contract_id, status)
            VALUES (?, ?, ?, 'pdf', 3, ?, ?, 'submitted')
        ");

        $reference_number = "CONTRAT-" . $contract_id;
        $title = "Document pour contrat " . $contract_id;

        $stmt->bind_param("sssii", $reference_number, $title, $target_path, $uploaded_by, $contract_id);
        $stmt->execute();

        $_SESSION['success'] = "✅ PDF uploadé avec succès !";
        header("Location: ../pages/admin/dashboard.php");
        exit;

    } else {
        $_SESSION['error'] = "❌ Erreur lors de l'upload.";
        header("Location: ../pages/admin/dashboard.php");
        exit;
    }
}

header("Location: ../pages/admin/dashboard.php");
exit;
?>