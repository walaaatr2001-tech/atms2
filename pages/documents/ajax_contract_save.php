<?php
/**
 * AJAX Save Contract
 */
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../config/database.php';

header('Content-Type: application/json');

$conn = getDB();
$user = getUser();

$contract_number = trim($_POST['contract_number'] ?? '');
$title = trim($_POST['title'] ?? '');
$contract_type = trim($_POST['contract_type'] ?? '');
$enterprise_id = intval($_POST['enterprise_id'] ?? 0);
$department_id = intval($_POST['department_id'] ?? 0);
$total_amount = floatval($_POST['total_amount'] ?? 0);
$start_date = trim($_POST['start_date'] ?? '');
$end_date = trim($_POST['end_date'] ?? '');
$status = trim($_POST['status'] ?? 'active');

if (empty($contract_number) || empty($title)) {
    echo json_encode(['success' => false, 'error' => 'Numéro de contrat et titre obligatoires']);
    exit;
}

$contract_number_esc = $conn->real_escape_string($contract_number);
$title_esc = $conn->real_escape_string($title);
$contract_type_esc = $conn->real_escape_string($contract_type);
$status_esc = $conn->real_escape_string($status);

$sql = "INSERT INTO contracts (contract_number, title, contract_type, enterprise_id, department_id, total_amount, start_date, end_date, status, created_by)
        VALUES ('$contract_number_esc', '$title_esc', '$contract_type_esc', " . ($enterprise_id > 0 ? $enterprise_id : 'NULL') . ", " . ($department_id > 0 ? $department_id : 'NULL') . ", " . ($total_amount > 0 ? $total_amount : 'NULL') . ", " . (!empty($start_date) ? "'" . $conn->real_escape_string($start_date) . "'" : 'NULL') . ", " . (!empty($end_date) ? "'" . $conn->real_escape_string($end_date) . "'" : 'NULL') . ", '$status_esc')";

if ($conn->query($sql)) {
    $new_id = $conn->insert_id;
    logAction('create_contract', 'contract', $new_id);
    echo json_encode(['success' => true, 'contract_id' => $new_id, 'message' => 'Contrat créé avec succès']);
} else {
    echo json_encode(['success' => false, 'error' => $conn->error]);
}