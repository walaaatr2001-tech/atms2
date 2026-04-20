<?php
/**
 * AJAX Save ODS
 */
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../config/database.php';

header('Content-Type: application/json');

$conn = getDB();

$ods_number = trim($_POST['ods_number'] ?? '');
$contract_id = intval($_POST['contract_id'] ?? 0);
$description = trim($_POST['description'] ?? '');
$amount = floatval($_POST['amount'] ?? 0);
$issue_date = trim($_POST['issue_date'] ?? '');
$bureau = trim($_POST['bureau'] ?? '');
$status = trim($_POST['status'] ?? 'pending');

if (empty($ods_number)) {
    echo json_encode(['success' => false, 'error' => 'Numéro ODS obligatoire']);
    exit;
}

$ods_number_esc = $conn->real_escape_string($ods_number);
$description_esc = $conn->real_escape_string($description);
$bureau_esc = $conn->real_escape_string($bureau);
$status_esc = $conn->real_escape_string($status);

$sql = "INSERT INTO ods (ods_number, contract_id, description, amount, issue_date, bureau, status)
        VALUES ('$ods_number_esc', " . ($contract_id > 0 ? $contract_id : 'NULL') . ", '$description_esc', " . ($amount > 0 ? $amount : 'NULL') . ", " . (!empty($issue_date) ? "'" . $conn->real_escape_string($issue_date) . "'" : 'NULL') . ", '$bureau_esc', '$status_esc')";

if ($conn->query($sql)) {
    $new_id = $conn->insert_id;
    echo json_encode(['success' => true, 'ods_id' => $new_id, 'message' => 'ODS créé avec succès']);
} else {
    echo json_encode(['success' => false, 'error' => $conn->error]);
}