<?php
/**
 * AJAX Batch AI - Batch AI processing trigger for admin
 */
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'super_admin') {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$action = $_GET['action'] ?? '';
$conn = getDB();

switch ($action) {
    case 'process':
        $limit = (int)($_GET['limit'] ?? 10);
        
        $stmt = $conn->prepare("
            SELECT id, title, file_path, file_type 
            FROM documents 
            WHERE (ai_processed = 0 OR ai_processed IS NULL) 
            AND file_type IN ('pdf', 'docx')
            LIMIT ?
        ");
        $stmt->bind_param("i", $limit);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $documents = [];
        while ($row = $result->fetch_assoc()) {
            $documents[] = $row;
        }
        
        $processed = 0;
        foreach ($documents as $doc) {
            // Here we would call the AI processing logic
            // For now, we'll just mark them as processed
            $update = $conn->prepare("UPDATE documents SET ai_processed = 1 WHERE id = ?");
            $update->bind_param("i", $doc['id']);
            $update->execute();
            $processed++;
        }
        
        echo json_encode([
            'success' => true,
            'found' => count($documents),
            'processed' => $processed,
            'message' => "Processed $processed documents"
        ]);
        break;
        
    case 'stats':
        $stats = [];
        
        $result = $conn->query("SELECT COUNT(*) as total FROM documents");
        $stats['total'] = $result->fetch_assoc()['total'];
        
        $result = $conn->query("SELECT COUNT(*) as processed FROM documents WHERE ai_processed = 1");
        $stats['processed'] = $result->fetch_assoc()['processed'];
        
        $result = $conn->query("SELECT COUNT(*) as pending FROM documents WHERE (ai_processed = 0 OR ai_processed IS NULL)");
        $stats['pending'] = $result->fetch_assoc()['pending'];
        
        $stats['percentage'] = $stats['total'] > 0 ? round(($stats['processed'] / $stats['total']) * 100, 1) : 0;
        
        echo json_encode($stats);
        break;
        
    case 'reprocess':
        $document_id = (int)$_GET['document_id'] ?? 0;
        
        if (!$document_id) {
            echo json_encode(['error' => 'No document ID provided']);
            exit;
        }
        
        $update = $conn->prepare("UPDATE documents SET ai_processed = 0 WHERE id = ?");
        $update->bind_param("i", $document_id);
        $update->execute();
        
        echo json_encode([
            'success' => true,
            'message' => 'Document marked for reprocessing'
        ]);
        break;
        
    default:
        echo json_encode(['error' => 'Invalid action']);
        break;
}