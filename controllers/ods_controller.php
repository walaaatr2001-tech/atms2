<?php
/**
 * ODS Controller - CRUD operations for ODS (Ordre de Service)
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

class ODSController {
    private $conn;
    
    public function __construct() {
        $this->conn = getDB();
    }
    
    public function getAll($filters = []) {
        $sql = "SELECT o.*, c.numero_contrat, c.title as contract_title, 
                e.enterprise_name, d.name as department_name
                FROM ods o
                LEFT JOIN contracts c ON o.contract_id = c.id
                LEFT JOIN enterprises e ON o.enterprise_id = e.id
                LEFT JOIN departments d ON c.department_id = d.id
                WHERE 1=1";
        
        $params = [];
        $types = "";
        
        if (!empty($filters['status'])) {
            $sql .= " AND o.status = ?";
            $params[] = $filters['status'];
            $types .= "s";
        }
        
        if (!empty($filters['contract_id'])) {
            $sql .= " AND o.contract_id = ?";
            $params[] = $filters['contract_id'];
            $types .= "i";
        }
        
        if (!empty($filters['enterprise_id'])) {
            $sql .= " AND o.enterprise_id = ?";
            $params[] = $filters['enterprise_id'];
            $types .= "i";
        }
        
        if (!empty($filters['bureau'])) {
            $sql .= " AND o.bureau LIKE ?";
            $params[] = "%" . $filters['bureau'] . "%";
            $types .= "s";
        }
        
        if (!empty($filters['search'])) {
            $sql .= " AND (o.numero_ods LIKE ? OR c.numero_contrat LIKE ?)";
            $search = "%" . $filters['search'] . "%";
            $params[] = $search;
            $params[] = $search;
            $types .= "ss";
        }
        
        $sql .= " ORDER BY o.created_at DESC";
        
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
            SELECT o.*, c.numero_contrat, c.title as contract_title, 
                   e.enterprise_name
            FROM ods o
            LEFT JOIN contracts c ON o.contract_id = c.id
            LEFT JOIN enterprises e ON o.enterprise_id = e.id
            WHERE o.id = ?
        ");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        return $result->fetch_assoc();
    }
    
    public function create($data) {
        $stmt = $this->conn->prepare("
            INSERT INTO ods (numero_ods, contract_id, enterprise_id, bureau, lot_number,
                           amount, issue_date, description, status, document_id)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $status = $data['status'] ?? 'pending';
        
        $stmt->bind_param(
            "siissssssi",
            $data['numero_ods'],
            $data['contract_id'],
            $data['enterprise_id'],
            $data['bureau'],
            $data['lot_number'],
            $data['amount'],
            $data['issue_date'],
            $data['description'],
            $status,
            $data['document_id']
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
        
        $updatable = ['numero_ods', 'contract_id', 'enterprise_id', 'bureau', 
                     'lot_number', 'amount', 'issue_date', 'description', 'status'];
        
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
        
        $sql = "UPDATE ods SET " . implode(", ", $fields) . " WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param($types, ...$params);
        
        return $stmt->execute();
    }
    
    public function delete($id) {
        $stmt = $this->conn->prepare("DELETE FROM ods WHERE id = ?");
        $stmt->bind_param("i", $id);
        return $stmt->execute();
    }
    
    public function getDocumentsByODS($ods_id) {
        $stmt = $this->conn->prepare("
            SELECT d.*, dc.name as category_name
            FROM documents d
            LEFT JOIN document_categories dc ON d.category_id = dc.id
            WHERE d.ods_id = ?
            ORDER BY d.created_at DESC
        ");
        $stmt->bind_param("i", $ods_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        return $result->fetch_all(MYSQLI_ASSOC);
    }
    
    public function getPaymentDossiersByODS($ods_id) {
        $stmt = $this->conn->prepare("
            SELECT * FROM payment_dossiers WHERE ods_id = ? ORDER BY created_at DESC
        ");
        $stmt->bind_param("i", $ods_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        return $result->fetch_all(MYSQLI_ASSOC);
    }
    
    public function getStats() {
        $stats = [];
        
        $result = $this->conn->query("SELECT status, COUNT(*) as count FROM ods GROUP BY status");
        while ($row = $result->fetch_assoc()) {
            $stats['by_status'][$row['status']] = $row['count'];
        }
        
        $result = $this->conn->query("SELECT SUM(amount) as total FROM ods WHERE status = 'completed'");
        $row = $result->fetch_assoc();
        $stats['total_completed_amount'] = $row['total'] ?? 0;
        
        return $stats;
    }
}

if (php_sapi_name() === 'cli' || isset($_GET['action'])) {
    $controller = new ODSController();
    
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
        }
    }
}