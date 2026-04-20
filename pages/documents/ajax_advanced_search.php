<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../config/database.php';

header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    echo json_encode([]);
    exit;
}

$conn = getDB();
$results = [
    'contracts' => [],
    'ods' => [],
    'documents' => [],
    'enterprises' => []
];

$searchTerms = [];

// Search in extracted_json column of documents
if (!empty($input['contract_number']) || !empty($input['enterprise_name']) || !empty($input['year'])) {
    $searchTerms[] = $input['contract_number'] ?? '';
    $searchTerms[] = $input['enterprise_name'] ?? '';
    $searchTerms[] = $input['year'] ?? '';
    
    $extractedQuery = "SELECT id, title, reference_number, file_type, status, created_at, extracted_json FROM documents WHERE extracted_json IS NOT NULL AND extracted_json != ''";
    $conditions = [];
    
    if (!empty($input['contract_number'])) {
        $conditions[] = "extracted_json LIKE '%" . $conn->real_escape_string($input['contract_number']) . "%'";
    }
    if (!empty($input['enterprise_name'])) {
        $conditions[] = "extracted_json LIKE '%" . $conn->real_escape_string($input['enterprise_name']) . "%'";
    }
    if (!empty($input['year'])) {
        $conditions[] = "extracted_json LIKE '%" . $conn->real_escape_string($input['year']) . "%'";
    }
    if (!empty($input['amount_min'])) {
        $conditions[] = "extracted_json LIKE '%" . $conn->real_escape_string($input['amount_min']) . "%'";
    }
    
    if (!empty($conditions)) {
        $extractedQuery .= " AND (" . implode(" OR ", $conditions) . ")";
    }
    
    $extractedQuery .= " LIMIT 50";
    $res = $conn->query($extractedQuery);
    while ($row = $res->fetch_assoc()) {
        $matches = [];
        if (!empty($input['contract_number']) && stripos($row['extracted_json'], $input['contract_number']) !== false) {
            $matches[] = ['field' => 'N° Contrat', 'value' => $input['contract_number']];
        }
        if (!empty($input['enterprise_name']) && stripos($row['extracted_json'], $input['enterprise_name']) !== false) {
            $matches[] = ['field' => 'Entreprise', 'value' => $input['enterprise_name']];
        }
        
        $results['documents'][] = [
            'id' => $row['id'],
            'type' => 'Document',
            'number' => $row['reference_number'],
            'title' => $row['title'],
            'status' => $row['status'],
            'status_display' => ucfirst($row['status']),
            'date' => date('d/m/Y', strtotime($row['created_at'])),
            'url' => 'detail.php?id=' . $row['id'],
            'matches' => $matches
        ];
    }
}

// Search contracts table
$contractQuery = "SELECT c.*, CONCAT(ue.first_name, ' ', ue.last_name) as enterprise_name
                 FROM contracts c 
                 LEFT JOIN enterprises e ON c.enterprise_id = e.id
                 LEFT JOIN users ue ON e.user_id = ue.id
                 WHERE 1=1";
$conditions = [];

if (!empty($input['contract_number'])) {
    $contractQuery .= " AND c.contract_number LIKE '%" . $conn->real_escape_string($input['contract_number']) . "%'";
}
if (!empty($input['enterprise_name'])) {
    $contractQuery .= " AND CONCAT(ue.first_name, ' ', ue.last_name) LIKE '%" . $conn->real_escape_string($input['enterprise_name']) . "%'";
}
if (!empty($input['year'])) {
    $contractQuery .= " AND (YEAR(c.date_signature) = " . intval($input['year']) . " OR YEAR(c.created_at) = " . intval($input['year']) . ")";
}
if (!empty($input['status'])) {
    $contractQuery .= " AND c.status = '" . $conn->real_escape_string($input['status']) . "'";
}
if (!empty($input['amount_min'])) {
    $contractQuery .= " AND c.total_amount >= " . floatval($input['amount_min']);
}
if (!empty($input['amount_max'])) {
    $contractQuery .= " AND c.total_amount <= " . floatval($input['amount_max']);
}

$contractQuery .= " LIMIT 50";
$res = $conn->query($contractQuery);
while ($row = $res->fetch_assoc()) {
    $matches = [];
    if (!empty($input['contract_number'])) $matches[] = ['field' => 'N° Contrat', 'value' => $row['contract_number']];
    if (!empty($input['year'])) $matches[] = ['field' => 'Année', 'value' => $input['year']];
    
    $results['contracts'][] = [
        'id' => $row['id'],
        'type' => 'Contrat',
        'number' => $row['contract_number'],
        'enterprise' => $row['enterprise_name'] ?? null,
        'amount' => $row['total_amount'],
        'status' => $row['status'],
        'status_display' => ucfirst($row['status']),
        'date' => $row['date_signature'] ? date('d/m/Y', strtotime($row['date_signature'])) : ($row['created_at'] ? date('d/m/Y', strtotime($row['created_at'])) : null),
        'url' => 'contract_detail.php?id=' . $row['id'],
        'matches' => $matches
    ];
}

// Search ODS table
$odsQuery = "SELECT o.*, c.contract_number, CONCAT(ue.first_name, ' ', ue.last_name) as enterprise_name 
             FROM ods o
             LEFT JOIN contracts c ON o.contract_id = c.id
             LEFT JOIN enterprises e ON o.enterprise_id = e.id
             LEFT JOIN users ue ON e.user_id = ue.id
             WHERE 1=1";
$conditions = [];

if (!empty($input['ods_number'])) {
    $odsQuery .= " AND o.ods_number LIKE '%" . $conn->real_escape_string($input['ods_number']) . "%'";
}
if (!empty($input['contract_number'])) {
    $odsQuery .= " AND c.contract_number LIKE '%" . $conn->real_escape_string($input['contract_number']) . "%'";
}
if (!empty($input['bureau'])) {
    $odsQuery .= " AND o.bureau = '" . $conn->real_escape_string($input['bureau']) . "'";
}
if (!empty($input['year'])) {
    $odsQuery .= " AND (YEAR(o.issue_date) = " . intval($input['year']) . " OR YEAR(o.created_at) = " . intval($input['year']) . ")";
}
if (!empty($input['status'])) {
    $odsQuery .= " AND o.status = '" . $conn->real_escape_string($input['status']) . "'";
}
if (!empty($input['amount_min'])) {
    $odsQuery .= " AND o.amount >= " . floatval($input['amount_min']);
}
if (!empty($input['amount_max'])) {
    $odsQuery .= " AND o.amount <= " . floatval($input['amount_max']);
}

$odsQuery .= " LIMIT 50";
$res = $conn->query($odsQuery);
while ($row = $res->fetch_assoc()) {
    $matches = [];
    if (!empty($input['ods_number'])) $matches[] = ['field' => 'N° ODS', 'value' => $row['ods_number']];
    if (!empty($input['bureau'])) $matches[] = ['field' => 'Bureau', 'value' => $row['bureau']];
    if (!empty($input['year'])) $matches[] = ['field' => 'Année', 'value' => $input['year']];
    
    $results['ods'][] = [
        'id' => $row['id'],
        'type' => 'ODS',
        'number' => $row['ods_number'],
        'contract' => $row['contract_number'],
        'enterprise' => $row['enterprise_name'] ?? null,
        'amount' => $row['amount'],
        'status' => $row['status'],
        'status_display' => str_replace('_', ' ', ucfirst($row['status'])),
        'date' => $row['date_creation'] ? date('d/m/Y', strtotime($row['date_creation'])) : ($row['created_at'] ? date('d/m/Y', strtotime($row['created_at'])) : null),
        'url' => 'ods_detail.php?id=' . $row['id'],
        'matches' => $matches
    ];
}

// Search enterprises (users with role = enterprise)
$entQuery = "SELECT * FROM users WHERE role = 'enterprise' AND status = 'active'";
$conditions = [];

if (!empty($input['enterprise_name'])) {
    $entQuery .= " AND CONCAT(first_name, ' ', last_name) LIKE '%" . $conn->real_escape_string($input['enterprise_name']) . "%'";
}
if (!empty($input['rc_number'])) {
    // Search in enterprises table for rc
    $entQuery .= " AND id IN (SELECT user_id FROM enterprises WHERE rc LIKE '%" . $conn->real_escape_string($input['rc_number']) . "%')";
}
if (!empty($input['nif_number'])) {
    $entQuery .= " AND id IN (SELECT user_id FROM enterprises WHERE nif LIKE '%" . $conn->real_escape_string($input['nif_number']) . "%')";
}

$entQuery .= " LIMIT 50";
$res = $conn->query($entQuery);
while ($row = $res->fetch_assoc()) {
    $matches = [];
    if (!empty($input['enterprise_name'])) $matches[] = ['field' => 'Nom', 'value' => $row['first_name'] . ' ' . $row['last_name']];
    
    // Get RC/NIF from enterprises table
    $rc_val = ''; $nif_val = '';
    $ent_detail = $conn->query("SELECT rc, nif FROM enterprises WHERE user_id = " . intval($row['id']));
    if ($ent_row = $ent_detail->fetch_assoc()) {
        $rc_val = $ent_row['rc'] ?? '';
        $nif_val = $ent_row['nif'] ?? '';
        if (!empty($input['rc_number'])) $matches[] = ['field' => 'RC', 'value' => $rc_val];
        if (!empty($input['nif_number'])) $matches[] = ['field' => 'NIF', 'value' => $nif_val];
    }
    
    $results['enterprises'][] = [
        'id' => $row['id'],
        'type' => 'Entreprise',
        'number' => $rc_val ?: $nif_val ?: 'N/A',
        'enterprise' => $row['first_name'] . ' ' . $row['last_name'],
        'status' => $row['status'],
        'status_display' => ucfirst($row['status']),
        'date' => $row['created_at'] ? date('d/m/Y', strtotime($row['created_at'])) : null,
        'url' => '../admin/enterprises-pending.php?id=' . $row['id'],
        'matches' => $matches
    ];
}

// Search documents table
$docQuery = "SELECT d.*, dc.name as cat_name, dept.name as dept_name 
             FROM documents d
             LEFT JOIN document_categories dc ON d.category_id = dc.id
             LEFT JOIN departments dept ON d.department_id = dept.id
             WHERE 1=1";
$conditions = [];

if (!empty($input['doc_type'])) {
    if ($input['doc_type'] === 'ods') {
        $docQuery .= " AND dc.name LIKE '%ODS%'";
    } elseif ($input['doc_type'] === 'facture') {
        $docQuery .= " AND dc.name LIKE '%facture%'";
    } elseif ($input['doc_type'] === 'pv_reception') {
        $docQuery .= " AND dc.name LIKE '%réception%'";
    } elseif ($input['doc_type'] === 'attachement') {
        $docQuery .= " AND dc.name LIKE '%attachement%'";
    } elseif ($input['doc_type'] === 'bon_commande') {
        $docQuery .= " AND dc.name LIKE '%commande%'";
    }
}
if (!empty($input['year'])) {
    $docQuery .= " AND YEAR(d.created_at) = " . intval($input['year']);
}
if (!empty($input['status'])) {
    $docQuery .= " AND d.status = '" . $conn->real_escape_string($input['status']) . "'";
}
if (!empty($input['contract_number'])) {
    $docQuery .= " AND (d.contract_number LIKE '%" . $conn->real_escape_string($input['contract_number']) . "%' OR d.reference_number LIKE '%" . $conn->real_escape_string($input['contract_number']) . "%')";
}

$docQuery .= " LIMIT 50";
$res = $conn->query($docQuery);
while ($row = $res->fetch_assoc()) {
    $matches = [];
    if (!empty($input['doc_type'])) $matches[] = ['field' => 'Type', 'value' => $input['doc_type']];
    if (!empty($input['year'])) $matches[] = ['field' => 'Année', 'value' => $input['year']];
    if (!empty($input['contract_number']) && (stripos($row['contract_number'] ?? '', $input['contract_number']) !== false || stripos($row['reference_number'], $input['contract_number']) !== false)) {
        $matches[] = ['field' => 'Contrat', 'value' => $input['contract_number']];
    }
    
    $results['documents'][] = [
        'id' => $row['id'],
        'type' => 'Document',
        'number' => $row['reference_number'],
        'title' => $row['title'],
        'status' => $row['status'],
        'status_display' => ucfirst($row['status']),
        'date' => date('d/m/Y', strtotime($row['created_at'])),
        'url' => 'detail.php?id=' . $row['id'],
        'matches' => $matches
    ];
}

// Remove duplicates based on id and type
$seen = [];
foreach ($results as $type => $items) {
    $unique = [];
    foreach ($items as $item) {
        $key = $type . '-' . $item['id'];
        if (!isset($seen[$key])) {
            $seen[$key] = true;
            $unique[] = $item;
        }
    }
    $results[$type] = $unique;
}

echo json_encode($results);