<?php
/**
 * AJAX Upload Document with AI extraction
 */
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../config/database.php';

header('Content-Type: application/json');

$conn = getDB();
$user = getUser();

$title = trim($_POST['title'] ?? '');
$process_ai = isset($_POST['process_ai']) && $_POST['process_ai'] == '1';

if (!isset($_FILES['file']) || $_FILES['file']['error'] !== 0) {
    echo json_encode(['success' => false, 'error' => 'Fichier non sélectionné']);
    exit;
}

$file = $_FILES['file'];
$file_name = $file['name'];
$file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
$allowed_ext = ['pdf', 'docx', 'xlsx', 'jpg', 'jpeg', 'png'];

if (!in_array($file_ext, $allowed_ext)) {
    echo json_encode(['success' => false, 'error' => 'Type de fichier non autorisé']);
    exit;
}

$file_size = $file['size'];
if ($file_size > 52428800) {
    echo json_encode(['success' => false, 'error' => 'Fichier trop volumineux (max 50 MB)']);
    exit;
}

$ref = $conn->real_escape_string(generateRefNumber());
$title_esc = $conn->real_escape_string($title ?: $file_name);

$upload_dir = '../../uploads/' . date('Y') . '/';
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

$new_file_name = $ref . '.' . $file_ext;
$file_path = $upload_dir . $new_file_name;

if (move_uploaded_file($file['tmp_name'], $file_path)) {
    $sql = "INSERT INTO documents (reference_number, title, file_path, file_type, file_size, department_id, uploaded_by, status)
            VALUES ('$ref', '$title_esc', '$file_path', '$file_ext', " . intval($file_size) . ", " . intval($user['department_id']) . ", " . intval($user['id']) . ", 'submitted')";
    
    if ($conn->query($sql)) {
        $doc_id = $conn->insert_id;
        logAction('upload_document', 'document', $doc_id);
        
        $extracted = null;
        if ($process_ai) {
            require_once __DIR__ . '/../../includes/ai_helper.php';
            $extracted = callGeminiAI($file_path);
            
            if ($extracted && !isset($extracted['error'])) {
                $json_esc = $conn->real_escape_string(json_encode($extracted, JSON_UNESCAPED_UNICODE));
                $conn->query("UPDATE documents SET extracted_json = '$json_esc', ai_processed = 1 WHERE id = $doc_id");
                
                if (!empty($extracted['contract_number'])) {
                    $contract_num = $extracted['contract_number'];
                    $check = $conn->query("SELECT id FROM contracts WHERE contract_number = '$contract_num'");
                    if ($row = $check->fetch_assoc()) {
                        $conn->query("UPDATE documents SET contract_id = " . intval($row['id']) . " WHERE id = $doc_id");
                    }
                }
            }
        }
        
        echo json_encode([
            'success' => true,
            'document_id' => $doc_id,
            'reference' => $ref,
            'extracted' => $extracted
        ]);
    } else {
        echo json_encode(['success' => false, 'error' => $conn->error]);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Erreur lors du téléchargement']);
}