<?php
require_once '../../includes/auth.php';
$pageTitle = 'Dossiers de Paiement';
require_once '../../includes/header.php';

// Detect schema
$schema_check = @$conn->query("SHOW COLUMNS FROM payment_dossiers LIKE 'dossier_number'");
$new_schema = ($schema_check && $schema_check->num_rows > 0);

$status_filter = $_GET['status'] ?? '';
$contract_filter = $_GET['contract'] ?? '';

$where = "1=1";
if ($status_filter) {
    $where .= " AND pd.status = '" . $conn->real_escape_string($status_filter) . "'";
}
if ($contract_filter) {
    $where .= " AND pd.contract_id = " . intval($contract_filter);
}

$num_col = $new_schema ? 'pd.dossier_number' : 'pd.numero_dossier';
$ods_num = $new_schema ? 'o.ods_number' : 'o.numero_ods';
$contract_num = $new_schema ? 'c.contract_number' : 'c.numero_contrat';

$sql = "SELECT pd.*, $num_col as dossier_num, c.$contract_num as contract_num, o.$ods_num as ods_num, 
        e.rc as enterprise_rc
        FROM payment_dossiers pd
        LEFT JOIN contracts c ON pd.contract_id = c.id
        LEFT JOIN ods o ON pd.ods_id = o.id
        LEFT JOIN enterprises e ON pd.enterprise_id = e.id
        WHERE $where
        ORDER BY pd.created_at DESC";

$payments = [];
$result = $conn->query($sql);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $payments[] = $row;
    }
}

$contracts = [];
$result = $conn->query("SELECT id, contract_number FROM contracts ORDER BY contract_number DESC");
while ($row = $result->fetch_assoc()) {
    $contracts[] = $row;
}
?>
<style>
:root { --bg: #0b0f0e; --surface: #111615; --surface2: #161d1b; --border: rgba(255,255,255,.07); --teal: #00BFA5; --teal-dim: rgba(0,191,165,.12); --amber: #F59E0B; --red: #EF4444; --blue: #3B82F6; --green: #22C55E; --text: #E8EDEC; --text-muted: #6B7A78; --text-dim: #9AAFAD; --radius: 14px; --radius-sm: 8px; }
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
.stat-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 16px; margin-bottom: 20px; }
.stat-card { background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius); padding: 20px; }
.stat-value { font-size: 28px; font-weight: 800; color: var(--text); }
.stat-label { font-size: 12px; color: var(--text-muted); margin-top: 4px; }
.table-wrap { overflow-x: auto; }
.data-table { width: 100%; border-collapse: collapse; }
.data-table th { font-size: 10px; font-weight: 700; letter-spacing: .15em; text-transform: uppercase; color: var(--text-muted); padding: 14px 16px; text-align: left; border-bottom: 1px solid var(--border); background: var(--surface2); }
.data-table td { padding: 14px 16px; border-bottom: 1px solid var(--border); color: var(--text); font-size: 13px; }
.data-table tr:hover { background: rgba(255,255,255,0.02); }
.pay-num { font-family: monospace; font-weight: 700; color: var(--teal); }
.amount { font-weight: 700; color: var(--amber); }
.badge { font-size: 11px; font-weight: 600; padding: 4px 12px; border-radius: 20px; }
.badge-pending { background: rgba(245,158,11,0.15); color: var(--amber); }
.badge-in_progress { background: rgba(59,130,246,0.15); color: var(--blue); }
.badge-paid { background: rgba(34,197,94,0.15); color: var(--green); }
.badge-rejected { background: rgba(239,68,68,0.15); color: var(--red); }
.action-btn { color: var(--text-muted); text-decoration: none; }
.action-btn:hover { color: var(--teal); }
.empty { text-align: center; padding: 60px; color: var(--text-muted); }
.empty i { font-size: 36px; margin-bottom: 12px; opacity: 0.5; }
</style>

<div class="page">
    <div class="page-header">
        <div>
            <h1 class="page-title">Dossiers de Paiement</h1>
            <p class="page-subtitle"><?= count($payments) ?> dossier(s) trouve(s)</p>
        </div>
    </div>

    <?php
    $total = count($payments);
    $pending = count(array_filter($payments, fn($p) => $p['status'] === 'pending'));
    $paid = count(array_filter($payments, fn($p) => $p['status'] === 'paid'));
    ?>
    <div class="stat-grid">
        <div class="stat-card">
            <div class="stat-value"><?= $total ?></div>
            <div class="stat-label">Total Dossiers</div>
        </div>
        <div class="stat-card">
            <div class="stat-value" style="color: var(--amber)"><?= $pending ?></div>
            <div class="stat-label">En attente</div>
        </div>
        <div class="stat-card">
            <div class="stat-value" style="color: var(--green)"><?= $paid ?></div>
            <div class="stat-label">Payes</div>
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
                    <option value="paid" <?= $status_filter === 'paid' ? 'selected' : '' ?>>Payer</option>
                    <option value="rejected" <?= $status_filter === 'rejected' ? 'selected' : '' ?>>Rejete</option>
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
        </div>
    </div>

    <div class="card">
        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>N° Dossier</th>
                        <th>Contrat</th>
                        <th>ODS</th>
                        <th>Entreprise</th>
                        <th>Montant</th>
                        <th>Statut</th>
                        <th>Date</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($payments) > 0): ?>
                        <?php foreach ($payments as $pay): ?>
                        <tr>
                            <td><span class="pay-num"><?= htmlspecialchars($pay['dossier_num']) ?></span></td>
                            <td><?= htmlspecialchars($pay['contract_num'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($pay['ods_num'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($pay['enterprise_rc'] ?? '-') ?></td>
                            <td><?= $pay['amount'] ? '<span class="amount">' . number_format($pay['amount'], 2, ',', ' ') . ' DA</span>' : '-' ?></td>
                            <td><span class="badge badge-<?= $pay['status'] ?>"><?= str_replace('_', ' ', $pay['status']) ?></span></td>
                            <td><?= $pay['created_at'] ? date('d/m/Y', strtotime($pay['created_at'])) : '-' ?></td>
                            <td><a href="payments.php?id=<?= $pay['id'] ?>" class="action-btn"><i class="fa-regular fa-eye"></i> Voir</a></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="8"><div class="empty"><i class="fa-solid fa-credit-card"></i><p>Aucun dossier trouve</p></div></td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php require_once '../../includes/footer.php'; ?>