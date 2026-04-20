<?php
require_once '../../includes/auth.php';
$pageTitle = 'Contrats';
require_once '../../includes/header.php';

$status_filter = $_GET['status'] ?? '';
$type_filter = $_GET['type'] ?? '';

// Detect schema
$schema_check = $conn->query("SHOW COLUMNS FROM contracts LIKE 'contract_number'");
$new_schema = ($schema_check && $schema_check->num_rows > 0);

$num_col = $new_schema ? 'c.contract_number' : 'c.numero_contrat';
$type_col = $new_schema ? 'c.contract_type' : 'c.type_contrat';
$amount_col = $new_schema ? 'c.total_amount' : 'c.montant';
$ent_join = $new_schema ? ' LEFT JOIN enterprises e ON c.enterprise_id = e.id LEFT JOIN users ue ON e.user_id = ue.id LEFT JOIN departments d ON c.department_id = d.id' : ' LEFT JOIN users u ON c.entreprise_id = u.id';

$where = "1=1";
if ($status_filter) {
    $where .= " AND c.status = '" . $conn->real_escape_string($status_filter) . "'";
}
if ($type_filter) {
    $where .= " AND $type_col = '" . $conn->real_escape_string($type_filter) . "'";
}

$sql = "SELECT c.*, $num_col as contract_num, $type_col as contract_type, $amount_col as total_amount,
        e.rc as enterprise_rc
        $ent_join
        WHERE $where
        ORDER BY c.created_at DESC";

$contracts = [];
$result = $conn->query($sql);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $contracts[] = $row;
    }
}
?>
<style>
:root { --bg: #0b0f0e; --surface: #111615; --surface2: #161d1b; --border: rgba(255,255,255,.07); --teal: #00BFA5; --text: #E8EDEC; --text-muted: #6B7A78; --radius: 14px; }
.page { padding: 24px; }
.card { background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius); padding: 20px; margin-bottom: 20px; }
.card-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; flex-wrap: wrap; gap: 12px; }
.card-title { font-size: 16px; font-weight: 700; color: var(--text); }
.stat-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 16px; margin-bottom: 20px; }
.stat-card { background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius); padding: 20px; }
.stat-value { font-size: 28px; font-weight: 800; color: var(--text); }
.stat-label { font-size: 12px; color: var(--text-muted); margin-top: 4px; }
.filter-select { background: var(--surface2); border: 1px solid var(--border); color: var(--text); padding: 10px 14px; border-radius: 8px; font-size: 13px; min-width: 140px; }
.btn { display: inline-flex; align-items: center; gap: 8px; padding: 10px 20px; border-radius: 8px; font-size: 13px; font-weight: 600; text-decoration: none; transition: all .2s; }
.btn-primary { background: var(--teal); color: #000; }
.btn-primary:hover { background: #00e6c4; }
.table-wrap { overflow-x: auto; }
.data-table { width: 100%; border-collapse: collapse; }
.data-table th { font-size: 10px; font-weight: 700; letter-spacing: .15em; text-transform: uppercase; color: var(--text-muted); padding: 14px 16px; text-align: left; border-bottom: 1px solid var(--border); background: var(--surface2); }
.data-table td { padding: 14px 16px; border-bottom: 1px solid var(--border); color: var(--text); }
.data-table tr:hover { background: rgba(255,255,255,0.02); }
.contract-num { font-family: monospace; color: var(--teal); font-weight: 600; }
.status-badge { font-size: 11px; font-weight: 600; padding: 4px 12px; border-radius: 20px; }
.status-active { background: rgba(0,191,165,0.15); color: var(--teal); }
.status-completed { background: rgba(59,130,246,0.15); color: #3B82F6; }
.status-pending { background: rgba(245,158,11,0.15); color: #F59E0B; }
.status-suspended { background: rgba(245,158,11,0.15); color: #F59E0B; }
.status-cancelled { background: rgba(239,68,68,0.15); color: #EF4444; }
.empty { text-align: center; padding: 60px; color: var(--text-muted); }
.empty i { font-size: 36px; margin-bottom: 12px; opacity: 0.5; }
</style>

<div class="page">
    <div class="card">
        <div class="card-header">
            <div class="flex gap-3 flex-wrap">
                <select class="filter-select" onchange="window.location.href='?status='+this.value">
                    <option value="">Tous les statuts</option>
                    <option value="active" <?= $status_filter==='active'?'selected':'' ?>>Actif</option>
                    <option value="completed" <?= $status_filter==='completed'?'selected':'' ?>>Terminé</option>
                    <option value="suspended" <?= $status_filter==='suspended'?'selected':'' ?>>Suspendu</option>
                    <option value="cancelled" <?= $status_filter==='cancelled'?'selected':'' ?>>Annulé</option>
                </select>
                <select class="filter-select" onchange="window.location.href='?type='+this.value">
                    <option value="">Tous les types</option>
                    <option value="Contrat d'adhésion à commandes">Contrat d'adhésion</option>
                    <option value="Marché à commandes">Marché à commandes</option>
                    <option value="Marché simple">Marché simple</option>
                    <option value="Marché à tranches conditionnelles">Tranches conditionnelles</option>
                    <option value="Contrat programme">Contrat programme</option>
                    <option value="Coordination de commandes">Coordination</option>
                </select>
            </div>
            <a href="contract_detail.php?action=new" class="btn btn-primary">
                <i class="fa-solid fa-plus"></i> Nouveau contrat
            </a>
        </div>
    </div>

    <div class="stat-grid">
        <div class="stat-card">
            <div class="stat-value"><?= count($contracts) ?></div>
            <div class="stat-label">Total Contrats</div>
        </div>
        <div class="stat-card">
            <div class="stat-value"><?= count(array_filter($contracts, fn($c)=>$c['status']==='active')) ?></div>
            <div class="stat-label">Actifs</div>
        </div>
        <div class="stat-card">
            <div class="stat-value"><?= count(array_filter($contracts, fn($c)=>$c['status']==='completed')) ?></div>
            <div class="stat-label">Terminés</div>
        </div>
    </div>

    <div class="card">
        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>N° Contrat</th>
                        <th>Type</th>
                        <th>Entreprise (RC)</th>
                        <th>Montant</th>
                        <th>Statut</th>
                        <th>Date</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($contracts) > 0): ?>
                        <?php foreach ($contracts as $contract): ?>
                        <tr>
                            <td><span class="contract-num"><?= htmlspecialchars($contract['contract_num']) ?></span></td>
                            <td><?= htmlspecialchars($contract['contract_type'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($contract['enterprise_rc'] ?? '-') ?></td>
                            <td><?= $contract['total_amount'] ? number_format($contract['total_amount'], 2, ',', ' ') . ' DA' : '-' ?></td>
                            <td><span class="status-badge status-<?= $contract['status'] ?>"><?= ucfirst($contract['status']) ?></span></td>
                            <td><?= $contract['created_at'] ? date('d/m/Y', strtotime($contract['created_at'])) : '-' ?></td>
                            <td><a href="contract_detail.php?id=<?= $contract['id'] ?>" class="text-teal-400 hover:underline"><i class="fa-regular fa-eye"></i> Voir</a></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="7"><div class="empty"><i class="fa-solid fa-file-contract"></i><p>Aucun contrat trouvé</p></div></td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php require_once '../../includes/footer.php'; ?>