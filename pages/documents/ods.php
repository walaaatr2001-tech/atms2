<?php
require_once '../../includes/auth.php';
$pageTitle = 'ODS - Ordres de Service';
require_once '../../includes/header.php';

// Detect schema
$schema_check = @$conn->query("SHOW COLUMNS FROM ods LIKE 'ods_number'");
$new_schema = ($schema_check && $schema_check->num_rows > 0);

$status_filter = $_GET['status'] ?? '';
$contract_filter = $_GET['contract'] ?? '';
$bureau_filter = $_GET['bureau'] ?? '';

$num_col = $new_schema ? 'o.ods_number' : 'o.numero_ods';
$amount_col = $new_schema ? 'o.amount' : 'o.montant';
$date_col = $new_schema ? 'o.issue_date' : 'o.date_creation';
$contract_num = $new_schema ? 'c.contract_number' : 'c.numero_contrat';
$ent_join = $new_schema ? ' LEFT JOIN enterprises e ON o.enterprise_id = e.id LEFT JOIN users ue ON e.user_id = ue.id' : ' LEFT JOIN users u ON o.entreprise_id = u.id';

$where = "1=1";
if ($status_filter) {
    $where .= " AND o.status = '" . $conn->real_escape_string($status_filter) . "'";
}
if ($contract_filter) {
    $where .= " AND o.contract_id = " . intval($contract_filter);
}
if ($bureau_filter) {
    $where .= " AND o.bureau = '" . $conn->real_escape_string($bureau_filter) . "'";
}

$sql = "SELECT o.*, $num_col as ods_num, $amount_col as amount, $date_col as issue_date,
        c.$contract_num as contract_num, e.rc as enterprise_rc
        FROM ods o
        LEFT JOIN contracts c ON o.contract_id = c.id
        $ent_join
        WHERE $where
        ORDER BY o.created_at DESC";

$ods_list = [];
$result = $conn->query($sql);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $ods_list[] = $row;
    }
}

$contracts = [];
$result = $conn->query("SELECT id, contract_number FROM contracts ORDER BY contract_number DESC");
while ($row = $result->fetch_assoc()) {
    $contracts[] = $row;
}

$bureaus = ['DRT', 'SDT', 'DRT/SDT', 'Direction Centrale', 'DTC'];
?>
<style>
:root { --bg: #0b0f0e; --surface: #111615; --surface2: #161d1b; --border: rgba(255,255,255,.07); --teal: #00BFA5; --teal-dim: rgba(0,191,165,.12); --amber: #F59E0B; --red: #EF4444; --blue: #3B82F6; --text: #E8EDEC; --text-muted: #6B7A78; --text-dim: #9AAFAD; --radius: 14px; --radius-sm: 8px; }
.page { padding: 24px; }
.page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; flex-wrap: wrap; gap: 16px; }
.page-title { font-size: 26px; font-weight: 800; color: var(--text); }
.page-subtitle { font-size: 13px; color: var(--text-muted); margin-top: 4px; }
.card { background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius); padding: 20px; margin-bottom: 20px; }
.filters { display: flex; flex-wrap: wrap; gap: 16px; align-items: flex-end; }
.filter-group { display: flex; flex-direction: column; gap: 6px; }
.filter-label { font-size: 10px; font-weight: 700; letter-spacing: .15em; text-transform: uppercase; color: var(--text-muted); }
.filter-select { background: var(--surface2); border: 1px solid var(--border); color: var(--text); padding: 10px 14px; border-radius: var(--radius-sm); font-size: 13px; min-width: 160px; }
.filter-select option { background: #121212; }
.btn { display: inline-flex; align-items: center; gap: 8px; padding: 10px 20px; border-radius: var(--radius-sm); font-size: 13px; font-weight: 600; transition: all .2s; text-decoration: none; }
.btn-primary { background: var(--teal); color: #000; }
.btn-primary:hover { background: #00e6c4; }
.btn-outline { background: transparent; color: var(--text); border: 1px solid var(--border); }
.btn-outline:hover { border-color: var(--teal); color: var(--teal); }
.stat-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 16px; margin-bottom: 20px; }
.stat-card { background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius); padding: 20px; }
.stat-value { font-size: 28px; font-weight: 800; color: var(--text); }
.stat-label { font-size: 12px; color: var(--text-muted); margin-top: 4px; }
.table-wrap { overflow-x: auto; }
.data-table { width: 100%; border-collapse: collapse; }
.data-table th { font-size: 10px; font-weight: 700; letter-spacing: .15em; text-transform: uppercase; color: var(--text-muted); padding: 14px 16px; text-align: left; border-bottom: 1px solid var(--border); background: var(--surface2); }
.data-table td { padding: 14px 16px; border-bottom: 1px solid var(--border); color: var(--text); font-size: 13px; }
.data-table tr:hover { background: rgba(255,255,255,0.02); }
.ods-num { font-family: monospace; color: var(--teal); font-weight: 600; }
.amount { font-weight: 700; color: var(--amber); }
.badge { font-size: 11px; font-weight: 600; padding: 4px 12px; border-radius: 20px; }
.badge-pending { background: rgba(245,158,11,0.15); color: var(--amber); }
.badge-in_progress { background: rgba(59,130,246,0.15); color: var(--blue); }
.badge-completed { background: rgba(0,191,165,0.15); color: var(--teal); }
.badge-cancelled { background: rgba(239,68,68,0.15); color: var(--red); }
.action-btn { color: var(--text-muted); text-decoration: none; }
.action-btn:hover { color: var(--teal); }
.empty { text-align: center; padding: 60px; color: var(--text-muted); }
.empty i { font-size: 36px; margin-bottom: 12px; opacity: 0.5; }
</style>

<div class="page">
    <div class="page-header">
        <div>
            <h1 class="page-title">Ordres de Service (ODS)</h1>
            <p class="page-subtitle"><?= count($ods_list) ?> ODS trouve(s)</p>
        </div>
        <a href="ods_detail.php?action=new" class="btn btn-primary">
            <i class="fa-solid fa-plus"></i> Nouvel ODS
        </a>
    </div>

    <?php
    $total = count($ods_list);
    $pending = count(array_filter($ods_list, fn($o) => $o['status'] === 'pending'));
    $completed = count(array_filter($ods_list, fn($o) => $o['status'] === 'completed'));
    ?>
    <div class="stat-grid">
        <div class="stat-card">
            <div class="stat-value"><?= $total ?></div>
            <div class="stat-label">Total ODS</div>
        </div>
        <div class="stat-card">
            <div class="stat-value" style="color: var(--amber)"><?= $pending ?></div>
            <div class="stat-label">En attente</div>
        </div>
        <div class="stat-card">
            <div class="stat-value" style="color: var(--teal)"><?= $completed ?></div>
            <div class="stat-label">Termines</div>
        </div>
    </div>

    <div class="card">
        <div class="filters">
            <div class="filter-group">
                <label class="filter-label">Statut</label>
                <select class="filter-select" onchange="window.location.href='?status='+this.value">
                    <option value="">Tous</option>
                    <option value="pending" <?= $status_filter === 'pending' ? 'selected' : '' ?>>En attente</option>
                    <option value="in_progress" <?= $status_filter === 'in_progress' ? 'selected' : '' ?>>En cours</option>
                    <option value="completed" <?= $status_filter === 'completed' ? 'selected' : '' ?>>Termine</option>
                    <option value="cancelled" <?= $status_filter === 'cancelled' ? 'selected' : '' ?>>Annule</option>
                </select>
            </div>
            <div class="filter-group">
                <label class="filter-label">Contrat</label>
                <select class="filter-select" onchange="window.location.href='?contract='+this.value">
                    <option value="">Tous les contrats</option>
                    <?php foreach ($contracts as $c): ?>
                    <option value="<?= $c['id'] ?>" <?= $contract_filter == $c['id'] ? 'selected' : '' ?>><?= htmlspecialchars($c['contract_number']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <a href="ods.php" class="btn btn-outline">
                <i class="fa-solid fa-rotate-right"></i> Reinitialiser
            </a>
        </div>
    </div>

    <div class="card">
        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>N° ODS</th>
                        <th>Contrat</th>
                        <th>Entreprise</th>
                        <th>Montant</th>
                        <th>Statut</th>
                        <th>Date</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($ods_list) > 0): ?>
                        <?php foreach ($ods_list as $ods): ?>
                        <tr>
                            <td><span class="ods-num"><?= htmlspecialchars($ods['ods_num']) ?></span></td>
                            <td><?= htmlspecialchars($ods['contract_num'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($ods['enterprise_rc'] ?? '-') ?></td>
                            <td><?= $ods['amount'] ? '<span class="amount">' . number_format($ods['amount'], 2, ',', ' ') . ' DA</span>' : '-' ?></td>
                            <td><span class="badge badge-<?= $ods['status'] ?>"><?= str_replace('_', ' ', $ods['status']) ?></span></td>
                            <td>
                                <?php 
                                $dateStr = $ods['issue_date'] ?? $ods['created_at'] ?? null;
                                echo $dateStr ? date('d/m/Y', strtotime($dateStr)) : '-';
                                ?>
                            </td>
                            <td><a href="ods_detail.php?id=<?= $ods['id'] ?>" class="action-btn"><i class="fa-regular fa-eye"></i> Voir</a></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="7"><div class="empty"><i class="fa-solid fa-file-lines"></i><p>Aucun ODS trouve</p></div></td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php require_once '../../includes/footer.php'; ?>