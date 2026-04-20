<?php
// pages/documents/process_ai.php
session_start();
require_once '../../config/database.php';
require_once '../../includes/ai_helper.php';

if (!isset($_POST['document_id'])) {
    header("Location: list.php");
    exit;
}

$doc_id = intval($_POST['document_id']);

// Get file path
$stmt = $conn->prepare("SELECT file_path FROM documents WHERE id = ?");
$stmt->bind_param("i", $doc_id);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();

if ($row && file_exists($row['file_path'])) {
    $extracted = callGeminiAI($row['file_path']);
    
    $json = json_encode($extracted, JSON_UNESCAPED_UNICODE);
    
    $stmt = $conn->prepare("UPDATE documents SET extracted_json = ?, ai_processed = 1 WHERE id = ?");
    $stmt->bind_param("si", $json, $doc_id);
    $stmt->execute();
    
    $_SESSION['success'] = "✅ Analyse IA terminée avec succès !";
    header("Location: extracted_view.php?id=$doc_id");
    exit;
}

$_SESSION['error'] = "❌ Fichier non trouvé.";
header("Location: list.php");
exit;
?>