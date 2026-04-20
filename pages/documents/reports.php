<?php
require_once '../../includes/auth.php';
$pageTitle = 'Rapports & Statistiques';
require_once '../../includes/header.php';

$dept_filter = $_GET['department'] ?? '';
$enterprise_filter = $_GET['enterprise'] ?? '';
$month_filter = $_GET['month'] ?? '';

$dept_where = $dept_filter ? "AND d.department_id = " . intval($dept_filter) : "";
$ent_where = $enterprise_filter ? "AND d.enterprise_id = " . intval($enterprise_filter) : "";

$doc_stats = [];
$result = $conn->query("SELECT status, COUNT(*) as count FROM documents d WHERE 1=1 $dept_where GROUP BY status");
while ($row = $result->fetch_assoc()) {
    $doc_stats[$row['status']] = $row['count'];
}

$dept_stats = [];
$result = $conn->query("SELECT d.department_id, dep.name, COUNT(*) as count 
                        FROM documents d 
                        LEFT JOIN departments dep ON d.department_id = dep.id 
                        WHERE 1=1 $dept_where 
                        GROUP BY d.department_id 
                        ORDER BY count DESC");
while ($row = $result->fetch_assoc()) {
    $dept_stats[] = $row;
}

$monthly_stats = [];
$result = $conn->query("
    SELECT MONTH(created_at) as month, COUNT(*) as count 
    FROM documents 
    WHERE YEAR(created_at) = YEAR(CURDATE())
    GROUP BY MONTH(created_at)
");
while ($row = $result->fetch_assoc()) {
    $monthly_stats[$row['month']] = $row['count'];
}

$enterprise_stats = [];
$result = $conn->query("SELECT CONCAT(u.first_name, ' ', u.last_name) as enterprise_name, COUNT(d.id) as doc_count
                        FROM users u
                        LEFT JOIN documents d ON u.id = d.uploaded_by
                        WHERE u.role = 'enterprise' AND u.status = 'active' $ent_where
                        GROUP BY u.id
                        ORDER BY doc_count DESC
                        LIMIT 10");
while ($row = $result->fetch_assoc()) {
    $enterprise_stats[] = $row;
}

$contract_stats = [];
$result = $conn->query("SELECT status, COUNT(*) as count FROM contracts GROUP BY status");
while ($row = $result->fetch_assoc()) {
    $contract_stats[$row['status']] = $row['count'];
}

$departments = [];
$result = $conn->query("SELECT * FROM departments ORDER BY name");
while ($row = $result->fetch_assoc()) {
    $departments[] = $row;
}

$enterprises = [];
$result = $conn->query("SELECT id, CONCAT(first_name, ' ', last_name) as name FROM users WHERE role = 'enterprise' AND status = 'active' ORDER BY first_name");
while ($row = $result->fetch_assoc()) {
    $enterprises[] = $row;
}

$months = ['', 'Jan', 'Fév', 'Mar', 'Avr', 'Mai', 'Juin', 'Juil', 'Août', 'Sept', 'Oct', 'Nov', 'Déc'];
?>
<style>
:root { --bg: #0b0f0e; --surface: #111615; --surface2: #161d1b; --border: rgba(255,255,255,.07); --border2: rgba(0,191,165,.18); --teal: #00BFA5; --teal-dim: rgba(0,191,165,.12); --teal-glow: rgba(0,191,165,.25); --amber: #F59E0B; --red: #EF4444; --blue: #3B82F6; --purple: #8B5CF6; --text: #E8EDEC; --text-muted: #6B7A78; --text-dim: #9AAFAD; --radius: 14px; --radius-sm: 8px; }
.page { padding: 28px; }
.page-header { display:flex; align-items:flex-start; justify-content:space-between; margin-bottom:28px; }
.page-title { font-family:'Syne',sans-serif; font-size:26px; font-weight:800; color:var(--text); }
.page-subtitle { font-size:13px; color:var(--text-muted); margin-top:4px; }
.filters { background:var(--surface); border:1px solid var(--border); border-radius:var(--radius); padding:20px; margin-bottom:24px; display:flex; gap:16px; flex-wrap:wrap; }
.filter-group { display:flex; flex-direction:column; gap:6px; }
.filter-label { font-size:11px; font-weight:700; letter-spacing:.15em; text-transform:uppercase; color:var(--text-muted); }
.filter-select { background:var(--surface2); border:1px solid var(--border); color:var(--text); padding:10px 14px; border-radius:var(--radius-sm); font-size:13px; min-width:180px; }
.stats-grid { display:grid; grid-template-columns: repeat(auto-fit, minmax(180px,1fr)); gap:16px; margin-bottom:24px; }
.stat-card { background:var(--surface); border:1px solid var(--border); border-radius:var(--radius); padding:20px; position:relative; }
.stat-card::before { content:''; position:absolute; top:0; left:0; right:0; height:2px; background:linear-gradient(90deg, transparent, var(--teal), transparent); opacity:.7; }
.stat-icon { width:40px; height:40px; background:var(--teal-dim); border-radius:10px; display:flex; align-items:center; justify-content:center; font-size:17px; color:var(--teal); margin-bottom:12px; }
.stat-value { font-family:'Syne',sans-serif; font-size:28px; font-weight:800; color:var(--text); }
.stat-label { font-size:12px; color:var(--text-muted); }
.charts-grid { display:grid; grid-template-columns: repeat(auto-fit, minmax(400px,1fr)); gap:24px; margin-bottom:24px; }
.chart-card { background:var(--surface); border:1px solid var(--border); border-radius:var(--radius); }
.chart-header { padding:16px 20px; border-bottom:1px solid var(--border); font-family:'Syne',sans-serif; font-size:15px; font-weight:700; color:var(--text); }
.chart-body { padding:20px; height:300px; }
.bar-item { display:flex; align-items:center; gap:12px; margin-bottom:12px; }
.bar-label { width:100px; font-size:12px; color:var(--text-dim); white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.bar-track { flex:1; height:24px; background:var(--surface2); border-radius:6px; overflow:hidden; }
.bar-fill { height:100%; background:linear-gradient(90deg, var(--teal), var(--teal-glow)); border-radius:6px; transition:width .5s; }
.bar-value { width:40px; text-align:right; font-size:12px; font-weight:600; color:var(--text); }
.top-list { display:flex; flex-direction:column; gap:10px; }
.top-item { display:flex; justify-content:space-between; align-items:center; padding:12px; background:var(--surface2); border-radius:var(--radius-sm); }
.top-name { font-size:13px; color:var(--text); }
.top-count { font-family:'Syne',sans-serif; font-weight:700; color:var(--teal); }
@media(max-width:900px) { .charts-grid { grid-template-columns:1fr; } }
@media(max-width:600px) { .page { padding:16px; } .filters { flex-direction:column; } }
</style>

<div class="page">
    <div class="page-header">
        <div>
            <h1 class="page-title">Rapports & Statistiques</h1>
            <p class="page-subtitle">Vue d'ensemble du système</p>
        </div>
    </div>

    <?php
    $total_docs = array_sum($doc_stats);
    $validated = $doc_stats['validated'] ?? 0;
    $archived = $doc_stats['archived'] ?? 0;
    $total_contracts = array_sum($contract_stats);
    ?>
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon"><i class="fa-solid fa-file-lines"></i></div>
            <div class="stat-value"><?= $total_docs ?></div>
            <div class="stat-label">Total Documents</div>
        </div>
        <div class="stat-card" style="--teal:var(--teal)">
            <div class="stat-icon" style="background:rgba(0,191,165,.12);color:var(--teal)"><i class="fa-solid fa-circle-check"></i></div>
            <div class="stat-value"><?= $validated ?></div>
            <div class="stat-label">Validés</div>
        </div>
        <div class="stat-card" style="--teal:var(--blue)">
            <div class="stat-icon" style="background:rgba(59,130,246,.12);color:var(--blue)"><i class="fa-solid fa-archive"></i></div>
            <div class="stat-value"><?= $archived ?></div>
            <div class="stat-label">Archivés</div>
        </div>
        <div class="stat-card" style="--teal:var(--amber)">
            <div class="stat-icon" style="background:rgba(245,158,11,.12);color:var(--amber)"><i class="fa-solid fa-file-contract"></i></div>
            <div class="stat-value"><?= $total_contracts ?></div>
            <div class="stat-label">Contrats</div>
        </div>
    </div>

    <div class="filters">
        <div class="filter-group">
            <label class="filter-label">Département</label>
            <select class="filter-select" onchange="window.location.href='?department='+this.value+'&enterprise=<?= $enterprise_filter ?>'">
                <option value="">Tous les départements</option>
                <?php foreach ($departments as $dept): ?>
                <option value="<?= $dept['id'] ?>" <?= $dept_filter == $dept['id'] ? 'selected' : '' ?>><?= htmlspecialchars($dept['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div class="filter-group">
            <label class="filter-label">Entreprise</label>
            <select class="filter-select" onchange="window.location.href='?department=<?= $dept_filter ?>&enterprise='+this.value">
                <option value="">Toutes les entreprises</option>
                <?php foreach ($enterprises as $ent): ?>
                <option value="<?= $ent['id'] ?>" <?= $enterprise_filter == $ent['id'] ? 'selected' : '' ?>><?= htmlspecialchars($ent['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>

    <div class="charts-grid">
        <div class="chart-card">
            <div class="chart-header"><i class="fa-solid fa-chart-pie"></i> Documents par Statut</div>
            <div class="chart-body">
                <canvas id="statusChart"></canvas>
            </div>
        </div>
        
        <div class="chart-card">
            <div class="chart-header"><i class="fa-solid fa-chart-bar"></i> Documents par Département</div>
            <div class="chart-body">
                <?php if (count($dept_stats) > 0): ?>
                <?php $max = max(array_column($dept_stats, 'count')); ?>
                <?php foreach ($dept_stats as $stat): ?>
                <div class="bar-item">
                    <span class="bar-label"><?= htmlspecialchars($stat['name'] ?? 'Inconnu') ?></span>
                    <div class="bar-track"><div class="bar-fill" style="width:<?= $max ? ($stat['count'] / $max * 100) : 0 ?>%"></div></div>
                    <span class="bar-value"><?= $stat['count'] ?></span>
                </div>
                <?php endforeach; ?>
                <?php else: ?>
                <p style="color:var(--text-muted);text-align:center;padding:40px;">Aucune donnée</p>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="chart-card">
            <div class="chart-header"><i class="fa-solid fa-chart-line"></i> Documents par Mois (<?= date('Y') ?>)</div>
            <div class="chart-body">
                <canvas id="monthlyChart"></canvas>
            </div>
        </div>
        
        <div class="chart-card">
            <div class="chart-header"><i class="fa-solid fa-building"></i> Top Entreprises</div>
            <div class="chart-body">
                <?php if (count($enterprise_stats) > 0): ?>
                <div class="top-list">
                    <?php foreach ($enterprise_stats as $ent): ?>
                    <div class="top-item">
                        <span class="top-name"><?= htmlspecialchars($ent['enterprise_name']) ?></span>
                        <span class="top-count"><?= $ent['doc_count'] ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                <p style="color:var(--text-muted);text-align:center;padding:40px;">Aucune donnée</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
Chart.defaults.color = '#9AAFAD';
Chart.defaults.borderColor = 'rgba(255,255,255,.07)';

const statusData = {
    labels: ['Validés', 'Soumis', 'Archivés', 'Rejetés', 'Brouillon'],
    datasets: [{
        data: [<?= $doc_stats['validated'] ?? 0 ?>, <?= $doc_stats['submitted'] ?? 0 ?>, <?= $doc_stats['archived'] ?? 0 ?>, <?= $doc_stats['rejected'] ?? 0 ?>, <?= $doc_stats['draft'] ?? 0 ?>],
        backgroundColor: ['#00BFA5', '#F59E0B', '#3B82F6', '#EF4444', '#6B7280']
    }]
};

new Chart(document.getElementById('statusChart'), {
    type: 'doughnut',
    data: statusData,
    options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'right' } } }
});

const monthlyData = {
    labels: ['Jan', 'Fév', 'Mar', 'Avr', 'Mai', 'Juin', 'Juil', 'Août', 'Sept', 'Oct', 'Nov', 'Déc'],
    datasets: [{
        label: 'Documents',
        data: [<?php for ($i = 1; $i <= 12; $i++) { echo ($monthly_stats[$i] ?? 0) . ($i < 12 ? ',' : ''); } ?>],
        backgroundColor: 'rgba(0, 191, 165, 0.8)',
        borderRadius: 6
    }]
};

new Chart(document.getElementById('monthlyChart'), {
    type: 'bar',
    data: monthlyData,
    options: { responsive: true, maintainAspectRatio: false, scales: { y: { beginAtZero: true } } }
});
</script>
<?php require_once '../../includes/footer.php'; ?>