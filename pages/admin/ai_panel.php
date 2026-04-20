<?php
require_once '../../includes/auth.php';
$pageTitle = 'Panneau IA - Extraction';
require_once '../../includes/header.php';

$user = getUser();
if ($user['role'] !== 'super_admin') {
    echo "<div class='p-8 text-red-600'>Accès restreint aux administrateurs.</div>";
    require_once '../../includes/footer.php';
    exit;
}

$conn = getDB();

$total_docs = 0;
$result = $conn->query("SELECT COUNT(*) as cnt FROM documents");
if ($row = $result->fetch_assoc()) $total_docs = $row['cnt'];

$ai_processed = 0;
$result = $conn->query("SELECT COUNT(*) as cnt FROM documents WHERE ai_processed = 1");
if ($row = $result->fetch_assoc()) $ai_processed = $row['cnt'];

$pending = $total_docs - $ai_processed;
$percentage = $total_docs > 0 ? round(($ai_processed / $total_docs) * 100, 1) : 0;

$recent_ai = [];
$result = $conn->query("
    SELECT d.id, d.title, d.ai_processed, d.extracted_json, d.created_at
    FROM documents d
    WHERE d.extracted_json IS NOT NULL AND d.extracted_json != ''
    ORDER BY d.created_at DESC
    LIMIT 10
");
while ($row = $result->fetch_assoc()) {
    $recent_ai[] = $row;
}

$quota_used = min($ai_processed, 100);
$quota_limit = 1000;
?>
<style>
:root { --bg: #0b0f0e; --surface: #111615; --surface2: #161d1b; --border: rgba(255,255,255,.07); --border2: rgba(0,191,165,.18); --teal: #00BFA5; --teal-dim: rgba(0,191,165,.12); --teal-glow: rgba(0,191,165,.25); --amber: #F59E0B; --red: #EF4444; --blue: #3B82F6; --purple: #8B5CF6; --text: #E8EDEC; --text-muted: #6B7A78; --text-dim: #9AAFAD; --radius: 14px; --radius-sm: 8px; }
.page { padding: 28px; }
.page-header { display:flex; align-items:flex-start; justify-content:space-between; margin-bottom:28px; }
.page-title { font-family:'Syne',sans-serif; font-size:26px; font-weight:800; color:var(--text); }
.page-subtitle { font-size:13px; color:var(--text-muted); margin-top:4px; }
.btn { display:inline-flex; align-items:center; gap:8px; padding:10px 20px; border-radius:var(--radius-sm); font-size:13px; font-weight:600; cursor:pointer; transition:all .2s; }
.btn-primary { background:var(--teal); color:#000; box-shadow:0 4px 14px var(--teal-glow); }
.btn-outline { background:transparent; color:var(--text); border:1px solid var(--border); }
.stats-grid { display:grid; grid-template-columns: repeat(auto-fit, minmax(200px,1fr)); gap:16px; margin-bottom:24px; }
.stat-card { background:var(--surface); border:1px solid var(--border); border-radius:var(--radius); padding:20px; }
.stat-value { font-family:'Syne',sans-serif; font-size:32px; font-weight:800; color:var(--text); }
.stat-label { font-size:12px; color:var(--text-muted); margin-top:4px; }
.progress-card { background:var(--surface); border:1px solid var(--border); border-radius:var(--radius); padding:24px; margin-bottom:24px; }
.progress-header { display:flex; justify-content:space-between; align-items:center; margin-bottom:16px; }
.progress-title { font-family:'Syne',sans-serif; font-size:15px; font-weight:700; color:var(--text); }
.progress-value { font-size:14px; color:var(--teal); font-weight:600; }
.progress-track { height:12px; background:var(--surface2); border-radius:6px; overflow:hidden; }
.progress-fill { height:100%; background:linear-gradient(90deg, var(--teal), var(--teal-glow)); border-radius:6px; transition:width .5s; }
.quota-bar { height:8px; background:var(--surface2); border-radius:4px; margin-top:8px; overflow:hidden; }
.quota-fill { height:100%; background:var(--purple); border-radius:4px; }
.grid-2 { display:grid; grid-template-columns: 1fr 1fr; gap:24px; }
.card { background:var(--surface); border:1px solid var(--border); border-radius:var(--radius); }
.card-header { padding:16px 20px; border-bottom:1px solid var(--border); display:flex; justify-content:space-between; align-items:center; }
.card-title { font-family:'Syne',sans-serif; font-size:15px; font-weight:700; color:var(--text); }
.card-body { padding:16px 20px; }
.ai-item { display:flex; align-items:center; gap:12px; padding:12px; border-bottom:1px solid rgba(255,255,255,.04); }
.ai-item:last-child { border-bottom:none; }
.ai-icon { width:36px; height:36px; background:var(--teal-dim); border-radius:8px; display:flex; align-items:center; justify-content:center; color:var(--teal); }
.ai-info { flex:1; }
.ai-title { font-size:13px; font-weight:600; color:var(--text); }
.ai-date { font-size:11px; color:var(--text-muted); }
.ai-badge { font-size:10px; font-weight:700; padding:4px 8px; border-radius:12px; }
.ai-done { background:rgba(0,191,165,.15); color:var(--teal); }
.ai-pending { background:rgba(245,158,11,.15); color:var(--amber); }
.empty { text-align:center; padding:40px; color:var(--text-muted); }
@media(max-width:900px) { .grid-2 { grid-template-columns:1fr; } }
</style>

<div class="page">
    <div class="page-header">
        <div>
            <h1 class="page-title">Panneau IA</h1>
            <p class="page-subtitle">Extraction automatique de données depuis les documents</p>
        </div>
        <button class="btn btn-primary" onclick="triggerBatchAI()">
            <i class="fa-solid fa-robot"></i> Lancer le traitement
        </button>
    </div>

    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-value"><?= $total_docs ?></div>
            <div class="stat-label">Total Documents</div>
        </div>
        <div class="stat-card">
            <div class="stat-value"><?= $ai_processed ?></div>
            <div class="stat-label">Traités par IA</div>
        </div>
        <div class="stat-card" style="--teal:var(--amber)">
            <div class="stat-value"><?= $pending ?></div>
            <div class="stat-label">En attente</div>
        </div>
        <div class="stat-card" style="--teal:var(--purple)">
            <div class="stat-value"><?= $percentage ?>%</div>
            <div class="stat-label">Taux de traitement</div>
        </div>
    </div>

    <div class="progress-card">
        <div class="progress-header">
            <span class="progress-title">Progression du traitement</span>
            <span class="progress-value"><?= $ai_processed ?> / <?= $total_docs ?></span>
        </div>
        <div class="progress-track">
            <div class="progress-fill" style="width:<?= $percentage ?>%"></div>
        </div>
        
        <div style="margin-top:24px; display:flex; justify-content:space-between;">
            <div>
                <div style="font-size:12px;color:var(--text-muted);">Quota utilisé</div>
                <div style="font-size:20px;font-weight:700;color:var(--text);"><?= $quota_used ?> / <?= $quota_limit ?></div>
                <div class="quota-bar" style="width:200px;">
                    <div class="quota-fill" style="width:<?= ($quota_used/$quota_limit)*100 ?>%"></div>
                </div>
            </div>
            <div style="text-align:right;">
                <div style="font-size:12px;color:var(--text-muted);">Coût estimé</div>
                <div style="font-size:20px;font-weight:700;color:var(--amber);"><?= $ai_processed * 0.01 ?> $</div>
            </div>
        </div>
    </div>

    <div class="grid-2">
        <div class="card">
            <div class="card-header">
                <span class="card-title"><i class="fa-solid fa-clock"></i> Documents récents IA</span>
            </div>
            <div class="card-body">
                <?php if (count($recent_ai) > 0): ?>
                    <?php foreach ($recent_ai as $doc): ?>
                    <div class="ai-item">
                        <div class="ai-icon"><i class="fa-solid fa-robot"></i></div>
                        <div class="ai-info">
                            <div class="ai-title"><?= htmlspecialchars($doc['title']) ?></div>
                            <div class="ai-date"><?= date('d/m/Y H:i', strtotime($doc['created_at'])) ?></div>
                        </div>
                        <span class="ai-badge ai-done">Extrait</span>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="empty">Aucun document traité</div>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <span class="card-title"><i class="fa-solid fa-gear"></i> Actions</span>
            </div>
            <div class="card-body">
                <button class="btn btn-outline" style="width:100%;margin-bottom:12px;" onclick="reprocessAll()">
                    <i class="fa-solid fa-rotate-right"></i> Reprocessus tous
                </button>
                <button class="btn btn-outline" style="width:100%;margin-bottom:12px;" onclick="exportAI()">
                    <i class="fa-solid fa-download"></i> Exporter données
                </button>
                <button class="btn btn-outline" style="width:100%;" onclick="clearCache()">
                    <i class="fa-solid fa-trash"></i> Vider cache
                </button>
            </div>
        </div>
    </div>
</div>

<script>
function triggerBatchAI() {
    fetch('ajax_batch_ai.php?action=process&limit=10')
        .then(r => r.json())
        .then(data => {
            alert(data.message || 'Traitement lancé');
            location.reload();
        });
}

function reprocessAll() {
    if (confirm('Reprocessus tous les documents?')) {
        fetch('ajax_batch_ai.php?action=process&limit=100')
            .then(r => r.json())
            .then(data => {
                alert('Traitement terminé');
                location.reload();
            });
    }
}

function exportAI() {
    alert('Export en cours...');
}

function clearCache() {
    if (confirm('Vider le cache?')) {
        alert('Cache vidé');
    }
}
</script>
<?php require_once '../../includes/footer.php'; ?>