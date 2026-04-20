<?php
/**
 * AJAX AI Status - Poll AI processing status
 */
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../config/database.php';

header('Content-Type: application/json');

$document_id = $_GET['document_id'] ?? 0;

if (!$document_id) {
    echo json_encode(['error' => 'No document ID provided']);
    exit;
}

$conn = getDB();
$stmt = $conn->prepare("
    SELECT id, title, ai_processed, extracted_json, status
    FROM documents 
    WHERE id = ?
");
$stmt->bind_param("i", $document_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['error' => 'Document not found']);
    exit;
}

$doc = $result->fetch_assoc();

$response = [
    'document_id' => $doc['id'],
    'title' => $doc['title'],
    'status' => $doc['status'],
    'ai_processed' => (bool)$doc['ai_processed'],
    'extracted' => !empty($doc['extracted_json']),
    'timestamp' => date('Y-m-d H:i:s')
];

if ($doc['extracted_json']) {
    $json = json_decode($doc['extracted_json'], true);
    $response['extracted_data'] = $json;
    $response['extracted_fields'] = is_array($json) ? array_keys($json) : [];
}

echo json_encode($response);