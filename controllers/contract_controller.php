<?php
/**
 * Contract Controller - CRUD operations for contracts
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

class ContractController {
    private $conn;
    
    public function __construct() {
        $this->conn = getDB();
    }
    
    public function getAll($filters = []) {
        $sql = "SELECT c.*, e.enterprise_name, d.name as department_name, 
                u.first_name as creator_first, u.last_name as creator_last
                FROM contracts c
                LEFT JOIN enterprises e ON c.enterprise_id = e.id
                LEFT JOIN departments d ON c.department_id = d.id
                LEFT JOIN users u ON c.created_by = u.id
                WHERE 1=1";
        
        $params = [];
        $types = "";
        
        if (!empty($filters['status'])) {
            $sql .= " AND c.status = ?";
            $params[] = $filters['status'];
            $types .= "s";
        }
        
        if (!empty($filters['enterprise_id'])) {
            $sql .= " AND c.enterprise_id = ?";
            $params[] = $filters['enterprise_id'];
            $types .= "i";
        }
        
        if (!empty($filters['department_id'])) {
            $sql .= " AND c.department_id = ?";
            $params[] = $filters['department_id'];
            $types .= "i";
        }
        
        if (!empty($filters['search'])) {
            $sql .= " AND (c.numero_contrat LIKE ? OR c.title LIKE ?)";
            $search = "%" . $filters['search'] . "%";
            $params[] = $search;
            $params[] = $search;
            $types .= "ss";
        }
        
        $sql .= " ORDER BY c.created_at DESC";
        
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
            SELECT c.*, e.enterprise_name, d.name as department_name,
                   u.first_name as creator_first, u.last_name as creator_last
            FROM contracts c
            LEFT JOIN enterprises e ON c.enterprise_id = e.id
            LEFT JOIN departments d ON c.department_id = d.id
            LEFT JOIN users u ON c.created_by = u.id
            WHERE c.id = ?
        ");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        return $result->fetch_assoc();
    }
    
    public function create($data) {
        $stmt = $this->conn->prepare("
            INSERT INTO contracts (numero_contrat, title, enterprise_id, department_id, 
                                 total_amount, start_date, end_date, status, created_by)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $status = $data['status'] ?? 'active';
        $created_by = $_SESSION['user_id'] ?? null;
        
        $stmt->bind_param(
            "ssiiisssi",
            $data['numero_contrat'],
            $data['title'],
            $data['enterprise_id'],
            $data['department_id'],
            $data['total_amount'],
            $data['start_date'],
            $data['end_date'],
            $status,
            $created_by
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
        
        $updatable = ['numero_contrat', 'title', 'enterprise_id', 'department_id', 
                     'total_amount', 'start_date', 'end_date', 'status'];
        
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
        
        $sql = "UPDATE contracts SET " . implode(", ", $fields) . " WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param($types, ...$params);
        
        return $stmt->execute();
    }
    
    public function delete($id) {
        $stmt = $this->conn->prepare("DELETE FROM contracts WHERE id = ?");
        $stmt->bind_param("i", $id);
        return $stmt->execute();
    }
    
    public function getODSByContract($contract_id) {
        $stmt = $this->conn->prepare("
            SELECT o.*, e.enterprise_name
            FROM ods o
            LEFT JOIN enterprises e ON o.enterprise_id = e.id
            WHERE o.contract_id = ?
            ORDER BY o.created_at DESC
        ");
        $stmt->bind_param("i", $contract_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        return $result->fetch_all(MYSQLI_ASSOC);
    }
    
    public function getDocumentsByContract($contract_id) {
        $stmt = $this->conn->prepare("
            SELECT d.*, dc.name as category_name
            FROM documents d
            LEFT JOIN document_categories dc ON d.category_id = dc.id
            WHERE d.contract_id = ?
            ORDER BY d.created_at DESC
        ");
        $stmt->bind_param("i", $contract_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        return $result->fetch_all(MYSQLI_ASSOC);
    }
    
    public function getStats() {
        $stats = [];
        
        $result = $this->conn->query("SELECT status, COUNT(*) as count FROM contracts GROUP BY status");
        while ($row = $result->fetch_assoc()) {
            $stats['by_status'][$row['status']] = $row['count'];
        }
        
        $result = $this->conn->query("SELECT SUM(total_amount) as total FROM contracts WHERE status = 'active'");
        $row = $result->fetch_assoc();
        $stats['total_active_amount'] = $row['total'] ?? 0;
        
        return $stats;
    }
}

if (php_sapi_name() === 'cli' || isset($_GET['action'])) {
    $controller = new ContractController();
    
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