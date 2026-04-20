<?php
require_once '../../includes/auth.php';
$pageTitle = 'Archives Centrales';
require_once '../../includes/header.php';

$user = getUser();
$role = $user['role'];

$year_filter = $_GET['year'] ?? '';
$dept_filter = $_GET['department'] ?? '';

$where = "d.status = 'archived'";
if ($year_filter) {
    $where .= " AND YEAR(d.created_at) = " . intval($year_filter);
}
if ($dept_filter) {
    $where .= " AND d.department_id = " . intval($dept_filter);
}

$sql = "SELECT d.*, dept.name as dept_name, cat.name as cat_name, 
        u.first_name, u.last_name
        FROM documents d
        LEFT JOIN departments dept ON d.department_id = dept.id
        LEFT JOIN document_categories cat ON d.category_id = cat.id
        LEFT JOIN users u ON d.uploaded_by = u.id
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

$years = [];
$result = $conn->query("SELECT DISTINCT YEAR(created_at) as year FROM documents ORDER BY year DESC");
while ($row = $result->fetch_assoc()) {
    $years[] = $row['year'];
}

$dept_stats = [];
$result = $conn->query("
    SELECT d.department_id, dept.name, COUNT(*) as count 
    FROM documents d 
    LEFT JOIN departments dept ON d.department_id = dept.id 
    WHERE d.status = 'archived' 
    GROUP BY d.department_id
");
while ($row = $result->fetch_assoc()) {
    $dept_stats[] = $row;
}
?>
<style>
:root { --bg: #0b0f0e; --surface: #111615; --surface2: #161d1b; --border: rgba(255,255,255,.07); --border2: rgba(0,191,165,.18); --teal: #00BFA5; --teal-dim: rgba(0,191,165,.12); --teal-glow: rgba(0,191,165,.25); --amber: #F59E0B; --red: #EF4444; --blue: #3B82F6; --text: #E8EDEC; --text-muted: #6B7A78; --text-dim: #9AAFAD; --radius: 14px; --radius-sm: 8px; }
.page { padding: 28px; }
.page-header { display:flex; align-items:flex-start; justify-content:space-between; margin-bottom:28px; }
.page-title { font-family:'Syne',sans-serif; font-size:26px; font-weight:800; color:var(--text); }
.page-subtitle { font-size:13px; color:var(--text-muted); margin-top:4px; }
.filters { background:var(--surface); border:1px solid var(--border); border-radius:var(--radius); padding:20px; margin-bottom:24px; display:flex; gap:16px; flex-wrap:wrap; }
.filter-group { display:flex; flex-direction:column; gap:6px; }
.filter-label { font-size:11px; font-weight:700; letter-spacing:.15em; text-transform:uppercase; color:var(--text-muted); }
.filter-select { background:var(--surface2); border:1px solid var(--border); color:var(--text); padding:10px 14px; border-radius:var(--radius-sm); font-size:13px; min-width:180px; }
.stats-row { display:grid; grid-template-columns: repeat(auto-fit, minmax(200px,1fr)); gap:16px; margin-bottom:24px; }
.stat-card { background:var(--surface); border:1px solid var(--border); border-radius:var(--radius); padding:20px; }
.stat-value { font-family:'Syne',sans-serif; font-size:28px; font-weight:800; color:var(--text); }
.stat-label { font-size:12px; color:var(--text-muted); margin-top:4px; }
.dept-list { display:grid; grid-template-columns: repeat(auto-fit, minmax(240px,1fr)); gap:12px; margin-bottom:24px; }
.dept-item { display:flex; justify-content:space-between; align-items:center; padding:14px 18px; background:var(--surface); border:1px solid var(--border); border-radius:var(--radius-sm); }
.dept-name { font-weight:600; color:var(--text); }
.dept-count { font-family:'Syne',sans-serif; font-weight:700; color:var(--teal); }
.table-wrap { background:var(--surface); border:1px solid var(--border); border-radius:var(--radius); overflow:hidden; }
.archive-table { width:100%; border-collapse:collapse; }
.archive-table th { font-size:11px; font-weight:700; letter-spacing:.1em; text-transform:uppercase; color:var(--text-muted); padding:14px 16px; text-align:left; border-bottom:1px solid var(--border); background:var(--surface2); }
.archive-table td { padding:14px 16px; border-bottom:1px solid rgba(255,255,255,.04); font-size:13px; color:var(--text-dim); }
.archive-table tr:hover td { background:rgba(255,255,255,.02); }
.doc-ref { font-family:monospace; color:var(--teal); }
.badge { padding:4px 10px; border-radius:20px; font-size:11px; font-weight:600; background:rgba(59,130,246,.15); color:var(--blue); }
.ficon { display:inline-flex; font-size:9px; font-weight:800; padding:4px 8px; border-radius:6px; }
.ficon-pdf { background:rgba(239,68,68,.15); color:var(--red); }
.ficon-docx { background:rgba(59,130,246,.15); color:var(--blue); }
.ficon-xlsx { background:rgba(34,197,94,.15); color:#22C55E; }
.empty { text-align:center; padding:60px 20px; color:var(--text-muted); }
.empty i { font-size:48px; opacity:.5; margin-bottom:16px; }
@media(max-width:600px) { .page { padding:16px; } .filters { flex-direction:column; } }
</style>

<div class="page">
    <div class="page-header">
        <div>
            <h1 class="page-title">Archives Centrales</h1>
            <p class="page-subtitle"><?= count($documents) ?> document(s) archivé(s)</p>
        </div>
    </div>

    <div class="stats-row">
        <div class="stat-card">
            <div class="stat-value"><?= count($documents) ?></div>
            <div class="stat-label">Total Archives</div>
        </div>
        <div class="stat-card" style="--teal:var(--blue)">
            <div class="stat-value"><?= count($years) ?></div>
            <div class="stat-label">Années</div>
        </div>
    </div>

    <?php if (count($dept_stats) > 0): ?>
    <div class="dept-list">
        <?php foreach ($dept_stats as $stat): ?>
        <div class="dept-item">
            <span class="dept-name"><?= htmlspecialchars($stat['name'] ?? 'Inconnu') ?></span>
            <span class="dept-count"><?= $stat['count'] ?></span>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <div class="filters">
        <div class="filter-group">
            <label class="filter-label">Année</label>
            <select class="filter-select" onchange="window.location.href='?year='+this.value+'&department=<?= $dept_filter ?>'">
                <option value="">Toutes les années</option>
                <?php foreach ($years as $year): ?>
                <option value="<?= $year ?>" <?= $year_filter == $year ? 'selected' : '' ?>><?= $year ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <?php if ($role === 'super_admin'): ?>
        <div class="filter-group">
            <label class="filter-label">Département</label>
            <select class="filter-select" onchange="window.location.href='?year=<?= $year_filter ?>&department='+this.value">
                <option value="">Tous les départements</option>
                <?php foreach ($departments as $dept): ?>
                <option value="<?= $dept['id'] ?>" <?= $dept_filter == $dept['id'] ? 'selected' : '' ?>><?= htmlspecialchars($dept['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <?php endif; ?>
    </div>

    <div class="table-wrap">
        <table class="archive-table">
            <thead>
                <tr>
                    <th>Type</th>
                    <th>Référence</th>
                    <th>Titre</th>
                    <th>Département</th>
                    <th>Archivé le</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($documents) > 0): ?>
                    <?php foreach ($documents as $doc): ?>
                    <tr>
                        <td><span class="ficon ficon-<?= $doc['file_type'] ?>"><?= strtoupper($doc['file_type']) ?></span></td>
                        <td><span class="doc-ref"><?= htmlspecialchars($doc['reference_number']) ?></span></td>
                        <td><?= htmlspecialchars($doc['title']) ?></td>
                        <td><?= htmlspecialchars($doc['dept_name'] ?? '-') ?></td>
                        <td><?= date('d/m/Y', strtotime($doc['created_at'])) ?></td>
                        <td><a href="detail.php?id=<?= $doc['id'] ?>" class="action-btn" style="padding:6px 10px;color:var(--text-muted);"><i class="fa-regular fa-eye"></i></a></td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="6"><div class="empty"><i class="fa-solid fa-archive"></i><p>Aucun document archivé</p></div></td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php require_once '../../includes/footer.php'; ?>