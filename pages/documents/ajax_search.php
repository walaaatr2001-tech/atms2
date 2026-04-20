<?php
/**
 * AJAX Search - Contract and ODS search endpoint
 * Works with both old and new column name schemas
 */
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../config/database.php';

header('Content-Type: application/json');

$action = $_GET['action'] ?? '';
$query = trim($_GET['q'] ?? '');

// Detect schema - check if new columns exist
$schema_check = $conn->query("SHOW COLUMNS FROM contracts LIKE 'contract_number'");
$new_schema = ($schema_check && $schema_check->num_rows > 0);

// Handle contract_filter action (new search.php page)
if ($action === 'contract_search') {
    $contract = trim($_GET['contract'] ?? '');
    $year = $_GET['year'] ?? '';
    $rc = trim($_GET['rc'] ?? '');
    $type = trim($_GET['type'] ?? '');
    $dateFrom = $_GET['date_from'] ?? '';
    $dateTo = $_GET['date_to'] ?? '';
    
    $num_col = $new_schema ? 'c.contract_number' : 'c.numero_contrat';
    $amount_col = $new_schema ? 'c.total_amount' : 'c.montant';
    $ent_name = $new_schema ? 'CONCAT(ue.first_name, " ", ue.last_name)' : 'CONCAT(u.first_name, " ", u.last_name)';
    $ent_join = $new_schema ? ' LEFT JOIN enterprises e ON c.enterprise_id = e.id LEFT JOIN users ue ON e.user_id = ue.id' : ' LEFT JOIN users u ON c.entreprise_id = u.id';
    
    $sql = "SELECT c.id, $num_col as contract_num, c.contract_type, c.title, $amount_col as amount, c.start_date, c.status,
                   c.created_at, e.rc as enterprise_rc
            FROM contracts c
            $ent_join
            WHERE 1=1";
    
    $params = [];
    $types = '';
    
    if (!empty($contract)) {
        $sql .= " AND $num_col LIKE ?";
        $search = "%$contract%";
        $params[] = &$search;
        $types .= 's';
    }
    
    if (!empty($year)) {
        $sql .= " AND YEAR(c.created_at) = ?";
        $params[] = &$year;
        $types .= 'i';
    }
    
    if (!empty($rc)) {
        $sql .= " AND e.rc LIKE ?";
        $search = "%$rc%";
        $params[] = &$search;
        $types .= 's';
    }
    
    if (!empty($type)) {
        $sql .= " AND c.contract_type = ?";
        $params[] = &$type;
        $types .= 's';
    }
    
    if (!empty($dateFrom)) {
        $sql .= " AND c.start_date >= ?";
        $params[] = &$dateFrom;
        $types .= 's';
    }
    
    if (!empty($dateTo)) {
        $sql .= " AND c.start_date <= ?";
        $params[] = &$dateTo;
        $types .= 's';
    }
    
    $sql .= " ORDER BY c.created_at DESC LIMIT 100";
    
    $stmt = $conn->prepare($sql);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    
    $results = [];
    while ($row = $result->fetch_assoc()) {
        $results[] = [
            'id' => $row['id'],
            'contract_number' => $row['contract_num'],
            'contract_type' => $row['contract_type'] ?? $row['title'] ?? '-',
            'year' => $row['created_at'] ? date('Y', strtotime($row['created_at'])) : '-',
            'amount' => $row['amount'],
            'status' => $row['status'],
            'enterprise_rc' => $row['enterprise_rc'],
            'start_date' => $row['start_date']
        ];
    }
    
    echo json_encode([
        'query' => $contract,
        'count' => count($results),
        'results' => $results
    ]);
    exit;
}

echo json_encode(['error' => 'No query provided', 'action' => $action]);