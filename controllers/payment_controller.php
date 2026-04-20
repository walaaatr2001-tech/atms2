<?php
/**
 * Payment Controller - CRUD operations for payment dossiers
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

class PaymentController {
    private $conn;
    
    public function __construct() {
        $this->conn = getDB();
    }
    
    public function getAll($filters = []) {
        $sql = "SELECT pd.*, c.numero_contrat, c.title as contract_title,
                o.numero_ods, e.enterprise_name
                FROM payment_dossiers pd
                LEFT JOIN contracts c ON pd.contract_id = c.id
                LEFT JOIN ods o ON pd.ods_id = o.id
                LEFT JOIN enterprises e ON pd.enterprise_id = e.id
                WHERE 1=1";
        
        $params = [];
        $types = "";
        
        if (!empty($filters['status'])) {
            $sql .= " AND pd.status = ?";
            $params[] = $filters['status'];
            $types .= "s";
        }
        
        if (!empty($filters['contract_id'])) {
            $sql .= " AND pd.contract_id = ?";
            $params[] = $filters['contract_id'];
            $types .= "i";
        }
        
        if (!empty($filters['ods_id'])) {
            $sql .= " AND pd.ods_id = ?";
            $params[] = $filters['ods_id'];
            $types .= "i";
        }
        
        if (!empty($filters['enterprise_id'])) {
            $sql .= " AND pd.enterprise_id = ?";
            $params[] = $filters['enterprise_id'];
            $types .= "i";
        }
        
        if (!empty($filters['search'])) {
            $sql .= " AND (pd.dossier_number LIKE ? OR c.numero_contrat LIKE ?)";
            $search = "%" . $filters['search'] . "%";
            $params[] = $search;
            $params[] = $search;
            $types .= "ss";
        }
        
        $sql .= " ORDER BY pd.created_at DESC";
        
        $stmt = $this->conn->prepare($sql);
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        
        return $result->fetch_all(MYSQLI_ASSOC);
    }
    
    public function getById($id) {
        $stmt = $this->conn->prepare("
            SELECT pd.*, c.numero_contrat, c.title as contract_title,
                   o.numero_ods, e.enterprise_name
            FROM payment_dossiers pd
            LEFT JOIN contracts c ON pd.contract_id = c.id
            LEFT JOIN ods o ON pd.ods_id = o.id
            LEFT JOIN enterprises e ON pd.enterprise_id = e.id
            WHERE pd.id = ?
        ");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        return $result->fetch_assoc();
    }
    
    public function create($data) {
        $stmt = $this->conn->prepare("
            INSERT INTO payment_dossiers (dossier_number, contract_id, ods_id, enterprise_id, amount, status)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        
        $status = $data['status'] ?? 'pending';
        
        $stmt->bind_param(
            "siiisd",
            $data['dossier_number'],
            $data['contract_id'],
            $data['ods_id'],
            $data['enterprise_id'],
            $data['amount'],
            $status
        );
        
        if ($stmt->execute()) {
            return $stmt->insert_id;
        }
        
        return false;
    }
    
    public function update($id, $data) {
        $fields = [];
        $params = [];
        $types = "";
        
        $updatable = ['dossier_number', 'contract_id', 'ods_id', 'enterprise_id', 'amount', 'status'];
        
        foreach ($updatable as $field) {
            if (isset($data[$field])) {
                $fields[] = "$field = ?";
                $params[] = $data[$field];
                $types .= is_int($data[$field]) ? "i" : "s";
            }
        }
        
        if (empty($fields)) return false;
        
        $params[] = $id;
        $types .= "i";
        
        $sql = "UPDATE payment_dossiers SET " . implode(", ", $fields) . " WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param($types, ...$params);
        
        return $stmt->execute();
    }
    
    public function delete($id) {
        $stmt = $this->conn->prepare("DELETE FROM payment_dossiers WHERE id = ?");
        $stmt->bind_param("i", $id);
        return $stmt->execute();
    }
    
    public function getDocuments($dossier_id) {
        $stmt = $this->conn->prepare("
            SELECT pdd.*, d.reference_number, d.title as doc_title, d.file_type
            FROM payment_dossier_docs pdd
            LEFT JOIN documents d ON pdd.document_id = d.id
            WHERE pdd.dossier_id = ?
            ORDER BY pdd.doc_type
        ");
        $stmt->bind_param("i", $dossier_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        return $result->fetch_all(MYSQLI_ASSOC);
    }
    
    public function addDocument($dossier_id, $doc_type, $document_id = null) {
        $stmt = $this->conn->prepare("
            INSERT INTO payment_dossier_docs (dossier_id, doc_type, document_id)
            VALUES (?, ?, ?)
        ");
        $stmt->bind_param("iii", $dossier_id, $doc_type, $document_id);
        return $stmt->execute();
    }
    
    public function updateDocumentStatus($id, $is_submitted) {
        $stmt = $this->conn->prepare("
            UPDATE payment_dossier_docs 
            SET is_submitted = ?, submitted_at = NOW()
            WHERE id = ?
        ");
        $stmt->bind_param("ii", $is_submitted, $id);
        return $stmt->execute();
    }
    
    public function getChecklist($dossier_id) {
        $required_types = ['ods_copy', 'pv_reception', 'facture', 'attachement', 'bon_commande', 'releve'];
        $checklist = [];
        
        foreach ($required_types as $type) {
            $stmt = $this->conn->prepare("
                SELECT * FROM payment_dossier_docs 
                WHERE dossier_id = ? AND doc_type = ?
            ");
            $stmt->bind_param("is", $dossier_id, $type);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $checklist[$type] = [
                'exists' => $result->num_rows > 0,
                'data' => $result->fetch_assoc()
            ];
        }
        
        return $checklist;
    }
    
    public function getStats() {
        $stats = [];
        
        $result = $this->conn->query("SELECT status, COUNT(*) as count FROM payment_dossiers GROUP BY status");
        while ($row = $result->fetch_assoc()) {
            $stats['by_status'][$row['status']] = $row['count'];
        }
        
        $result = $this->conn->query("SELECT SUM(amount) as total FROM payment_dossiers WHERE status = 'paid'");
        $row = $result->fetch_assoc();
        $stats['total_paid'] = $row['total'] ?? 0;
        
        return $stats;
    }
}

if (php_sapi_name() === 'cli' || isset($_GET['action'])) {
    $controller = new PaymentController();
    
    if (isset($_GET['action'])) {
        header('Content-Type: application/json');
        
        switch ($_GET['action']) {
            case 'list':
                $filters = $_GET;
                echo json_encode($controller->getAll($filters));
                break;
                
            case 'get':
                if (isset($_GET['id'])) {
                    echo json_encode($controller->getById($_GET['id']));
                }
                break;
                
            case 'create':
                if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                    $id = $controller->create($_POST);
                    echo json_encode(['success' => $id, 'id' => $id]);
                }
                break;
                
            case 'update':
                if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
                    $result = $controller->update($_POST['id'], $_POST);
                    echo json_encode(['success' => $result]);
                }
                break;
                
            case 'delete':
                if (isset($_GET['id'])) {
                    $result = $controller->delete($_GET['id']);
                    echo json_encode(['success' => $result]);
                }
                break;
                
            case 'checklist':
                if (isset($_GET['dossier_id'])) {
                    echo json_encode($controller->getChecklist($_GET['dossier_id']));
                }
                break;
        }
    }
}