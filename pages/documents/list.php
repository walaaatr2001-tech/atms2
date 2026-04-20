<?php
require_once '../../includes/auth.php';
$pageTitle = 'Documents';
require_once '../../includes/header.php';

$user = getUser();
$role = $user['role'];
$dept_id = $user['department_id'];
$user_id = $user['id'];

// Detect schema
$schema_check = @$conn->query("SHOW COLUMNS FROM contracts LIKE 'contract_number'");
$new_schema = ($schema_check && $schema_check->num_rows > 0);
$contract_col = $new_schema ? 'c.contract_number' : 'c.numero_contrat';

$status_filter = $_GET['status'] ?? '';
$dept_filter = $_GET['department'] ?? '';
$contract_filter = $_GET['contract'] ?? '';

$where = "1=1";
if ($role === 'dept_admin') {
    $where .= " AND d.department_id = " . intval($dept_id);
} elseif ($role === 'internal_staff' || $role === 'enterprise') {
    $where .= " AND d.uploaded_by = " . intval($user_id);
}
if ($status_filter) {
    $where .= " AND d.status = '" . $conn->real_escape_string($status_filter) . "'";
}
if ($dept_filter) {
    $where .= " AND d.department_id = " . intval($dept_filter);
}
if ($contract_filter) {
    $where .= " AND d.contract_id = " . intval($contract_filter);
}

$sql = "SELECT d.*, u.first_name, u.last_name, dept.name as dept_name, cat.name as cat_name,
        $contract_col as contract_num
        FROM documents d 
        LEFT JOIN users u ON d.uploaded_by = u.id 
        LEFT JOIN departments dept ON d.department_id = dept.id
        LEFT JOIN document_categories cat ON d.category_id = cat.id
        LEFT JOIN contracts c ON d.contract_id = c.id
        WHERE $where
        ORDER BY d.created_at DESC";

$documents = [];
$result = $conn->query($sql);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $documents[] = $row;
    }
}

$departments = [];
$result = $conn->query("SELECT * FROM departments ORDER BY name");
while ($row = $result->fetch_assoc()) {
    $departments[] = $row;
}

$contracts = [];
$contract_num_col = $new_schema ? 'contract_number' : 'numero_contrat';
$result = $conn->query("SELECT id, $contract_num_col as contract_number FROM contracts ORDER BY $contract_num_col DESC");
while ($row = $result->fetch_assoc()) {
    $contracts[] = $row;
}
?>
<style>
.table-wrap { background: rgba(13,13,13,0.85); backdrop-filter: blur(12px); border: 1px solid rgba(255,255,255,0.08); border-radius: 1rem; overflow: hidden; }
th { font-size: 11px; font-weight: 700; letter-spacing: .1em; text-transform: uppercase; color: #6B7A78; padding: 14px 16px; text-align: left; border-bottom: 1px solid rgba(255,255,255,0.07); background: rgba(22,29,27,0.5); }
td { padding: 14px 16px; border-bottom: 1px solid rgba(255,255,255,0.04); font-size: 13px; color: #9AAFAD; }
tr:hover td { background: rgba(255,255,255,0.02); }
.badge { display: inline-flex; font-size: 11px; font-weight: 600; padding: 4px 12px; border-radius: 20px; }
.badge-validated { background: rgba(0,191,165,.15); color: #00BFA5; }
.badge-submitted { background: rgba(245,158,11,.15); color: #F59E0B; }
.badge-archived { background: rgba(59,130,246,.15); color: #3B82F6; }
.badge-rejected { background: rgba(239,68,68,.15); color: #EF4444; }
.badge-draft { background: rgba(107,114,128,.15); color: #9CA3AF; }
.ficon { display: inline-flex; font-size: 9px; font-weight: 800; padding: 4px 8px; border-radius: 6px; }
.ficon-pdf { background: rgba(239,68,68,.15); color: #EF4444; }
.ficon-docx { background: rgba(59,130,246,.15); color: #3B82F6; }
.ficon-xlsx { background: rgba(34,197,94,.15); color: #22C55E; }
.filters { background: rgba(13,13,13,0.85); backdrop-filter: blur(12px); border: 1px solid rgba(255,255,255,0.08); border-radius: 1rem; padding: 20px; margin-bottom: 24px; }
.filter-select { background: #161d1b; border: 1px solid rgba(255,255,255,0.07); color: #E8EDEC; padding: 10px 14px; border-radius: 8px; font-size: 13px; min-width: 160px; }
.btn { display: inline-flex; align-items: center; gap: 8px; padding: 10px 20px; border-radius: 8px; font-size: 13px; font-weight: 600; text-decoration: none; }
.btn-primary { background: #00BFA5; color: #000; }
.btn-outline { background: transparent; color: #E8EDEC; border: 1px solid rgba(255,255,255,0.07); }
.empty { text-align: center; padding: 60px 20px; color: #6B7A78; }
.ref { font-family: monospace; color: #00BFA5; }
.contract-link { font-size: 12px; color: #F59E0B; }
.action-btn { color: #6B7A78; text-decoration: none; padding: 6px; }
.action-btn:hover { color: #00BFA5; }
</style>

<div class="mb-6">
    <div class="flex flex-wrap gap-4 items-center">
        <div>
            <label class="text-xs text-gray-500 mb-1 block">Statut</label>
            <select class="filter-select" onchange="window.location.href='?status='+this.value+'&department=<?= $dept_filter ?>&contract=<?= $contract_filter ?>'">
                <option value="">Tous les statuts</option>
                <option value="draft" <?= $status_filter === 'draft' ? 'selected' : '' ?>>Brouillon</option>
                <option value="submitted" <?= $status_filter === 'submitted' ? 'selected' : '' ?>>Soumis</option>
                <option value="validated" <?= $status_filter === 'validated' ? 'selected' : '' ?>>Validé</option>
                <option value="rejected" <?= $status_filter === 'rejected' ? 'selected' : '' ?>>Rejeté</option>
                <option value="archived" <?= $status_filter === 'archived' ? 'selected' : '' ?>>Archivé</option>
            </select>
        </div>
        
        <?php if ($role === 'super_admin'): ?>
        <div>
            <label class="text-xs text-gray-500 mb-1 block">Département</label>
            <select class="filter-select" onchange="window.location.href='?status=<?= $status_filter ?>&department='+this.value+'&contract=<?= $contract_filter ?>'">
                <option value="">Tous les départements</option>
                <?php foreach ($departments as $dept): ?>
                <option value="<?= $dept['id'] ?>" <?= $dept_filter == $dept['id'] ? 'selected' : '' ?>><?= htmlspecialchars($dept['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <?php endif; ?>
        
        <div>
            <label class="text-xs text-gray-500 mb-1 block">Contrat</label>
            <select class="filter-select" onchange="window.location.href='?status=<?= $status_filter ?>&department=<?= $dept_filter ?>&contract='+this.value">
                <option value="">Tous les contrats</option>
                <?php foreach ($contracts as $contract): ?>
                <option value="<?= $contract['id'] ?>" <?= $contract_filter == $contract['id'] ? 'selected' : '' ?>><?= htmlspecialchars($contract['contract_number']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <a href="upload.php" class="btn btn-primary ml-auto">
            <i class="fa-solid fa-plus"></i> Nouveau document
        </a>
    </div>
</div>

<div class="table-wrap">
    <table class="w-full">
        <thead>
            <tr>
                <th>Type</th>
                <th>Référence</th>
                <th>Titre</th>
                <th>Contrat</th>
                <th>Département</th>
                <th>Statut</th>
                <th>Date</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            <?php if (count($documents) > 0): ?>
                <?php foreach ($documents as $doc): ?>
                <tr>
                    <td><span class="ficon ficon-<?= $doc['file_type'] ?>"><?= strtoupper($doc['file_type']) ?></span></td>
                    <td><span class="ref"><?= htmlspecialchars($doc['reference_number']) ?></span></td>
                    <td><?= htmlspecialchars($doc['title']) ?></td>
                    <td><?php if ($doc['contract_num']): ?><span class="contract-link"><?= htmlspecialchars($doc['contract_num']) ?></span><?php else: ?><span class="text-gray-600">—</span><?php endif; ?></td>
                    <td><?= htmlspecialchars($doc['dept_name'] ?? '-') ?></td>
                    <td><span class="badge badge-<?= $doc['status'] ?>"><?= ucfirst($doc['status']) ?></span></td>
                    <td><?= date('d/m/Y', strtotime($doc['created_at'])) ?></td>
                    <td>
                        <a href="detail.php?id=<?= $doc['id'] ?>" class="action-btn"><i class="fa-regular fa-eye"></i></a>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="8">
                        <div class="empty">
                            <i class="fa-regular fa-folder-open text-4xl mb-4 block"></i>
                            <p>Aucun document trouvé</p>
                        </div>
                    </td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
<?php require_once '../../includes/footer.php'; ?>