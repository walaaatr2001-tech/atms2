<?php
require_once '../../includes/auth.php';
$pageTitle = 'ODS - Ordre de Service';
require_once '../../includes/header.php';

$id = intval($_GET['id'] ?? 0);
$action = $_GET['action'] ?? '';

$ods = null;
$payments = [];

if ($action === 'new') {
    $pageTitle = 'Nouvel ODS';
} elseif ($id > 0) {
    $stmt = $conn->prepare("SELECT o.*, c.contract_number, e.rc as enterprise_rc,
                           CONCAT(ue.first_name, ' ', ue.last_name) as enterprise_name
                           FROM ods o
                           LEFT JOIN contracts c ON o.contract_id = c.id
                           LEFT JOIN enterprises e ON o.enterprise_id = e.id
                           LEFT JOIN users ue ON e.user_id = ue.id
                           WHERE o.id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $ods = $result->fetch_assoc();
    
    if ($ods) {
        $stmt = $conn->prepare("SELECT * FROM payment_dossiers WHERE ods_id = ? ORDER BY created_at DESC");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $payments[] = $row;
        }
    }
}

$contracts = [];
$result = $conn->query("SELECT id, contract_number FROM contracts ORDER BY contract_number DESC");
while ($row = $result->fetch_assoc()) {
    $contracts[] = $row;
}

$bureaus = ['DRT', 'SDT', 'DRT/SDT', 'Direction Centrale', 'DTC'];
$bureaus_list = ['DRT' => 'Division Réseaux Télécom', 'SDT' => 'Service Départemental Télécom', 'DRT/SDT' => 'DRT/SDT', 'Direction Centrale' => 'Direction Centrale', 'DTC' => 'Direction Technique Centrale'];
?>
<style>
:root { --bg: #0b0f0e; --surface: #111615; --surface2: #161d1b; --border: rgba(255,255,255,.07); --border2: rgba(0,191,165,.18); --teal: #00BFA5; --teal-dim: rgba(0,191,165,.12); --amber: #F59E0B; --red: #EF4444; --blue: #3B82F6; --green: #22C55E; --text: #E8EDEC; --text-muted: #6B7A78; --text-dim: #9AAFAD; --radius: 14px; --radius-sm: 8px; }
.page { padding: 24px; }
.page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; flex-wrap: wrap; gap: 16px; }
.page-title { font-size: 26px; font-weight: 800; color: var(--text); }
.page-subtitle { font-size: 13px; color: var(--text-muted); margin-top: 4px; }
.card { background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius); margin-bottom: 20px; }
.card-header { padding: 16px 20px; border-bottom: 1px solid var(--border); display: flex; align-items: center; justify-content: space-between; }
.card-title { font-size: 15px; font-weight: 700; color: var(--text); }
.card-body { padding: 20px; }
.grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
@media (max-width: 900px) { .grid-2 { grid-template-columns: 1fr; } }
.btn { display: inline-flex; align-items: center; gap: 8px; padding: 10px 20px; border-radius: var(--radius-sm); font-size: 13px; font-weight: 600; transition: all .2s; text-decoration: none; cursor: pointer; border: none; }
.btn-primary { background: var(--teal); color: #000; }
.btn-primary:hover { background: #00e6c4; }
.btn-outline { background: transparent; color: var(--text); border: 1px solid var(--border); }
.form-group { margin-bottom: 16px; }
.form-label { display: block; font-size: 10px; font-weight: 700; letter-spacing: .15em; text-transform: uppercase; color: var(--text-muted); margin-bottom: 6px; }
.form-input, .form-select { width: 100%; background: var(--surface2); border: 1px solid var(--border); color: var(--text); padding: 12px 14px; border-radius: var(--radius-sm); font-size: 14px; }
.form-input:focus, .form-select:focus { outline: none; border-color: var(--border2); }
.form-select option { background: #121212; }
.field { display: flex; justify-content: space-between; padding: 12px 0; border-bottom: 1px solid rgba(255,255,255,0.04); }
.field:last-child { border-bottom: none; }
.field-label { font-size: 13px; color: var(--text-muted); }
.field-value { font-size: 13px; font-weight: 600; color: var(--text); }
.field-value.money { color: var(--amber); font-weight: 700; }
.badge { font-size: 11px; font-weight: 600; padding: 4px 12px; border-radius: 20px; }
.badge-pending { background: rgba(245,158,11,0.15); color: var(--amber); }
.badge-in_progress { background: rgba(59,130,246,0.15); color: var(--blue); }
.badge-completed { background: rgba(0,191,165,0.15); color: var(--teal); }
.badge-cancelled { background: rgba(239,68,68,0.15); color: var(--red); }
.item-row { display: flex; align-items: center; justify-content: space-between; padding: 12px; background: var(--surface2); border-radius: var(--radius-sm); margin-bottom: 8px; }
.item-num { font-family: monospace; font-weight: 600; color: var(--teal); }
.list-empty { text-align: center; padding: 30px; color: var(--text-muted); }
.list-empty i { font-size: 32px; margin-bottom: 10px; opacity: 0.5; display: block; }
</style>

<div class="page">
    <div class="page-header">
        <div>
            <h1 class="page-title"><?= $action === 'new' ? 'Nouvel ODS' : 'Détails ODS' ?></h1>
            <p class="page-subtitle"><?= $ods ? htmlspecialchars($ods['ods_number']) : 'Créer un nouvel ordre de service' ?></p>
        </div>
        <?php if ($action !== 'new'): ?>
        <a href="ods.php" class="btn btn-outline"><i class="fa-solid fa-arrow-left"></i> Retour</a>
        <?php endif; ?>
    </div>

    <?php if ($action === 'new'): ?>
    <div class="card">
        <div class="card-body">
            <form method="POST" action="ajax_ods_save.php">
                <div class="grid-2">
                    <div>
                        <div class="form-group">
                            <label class="form-label">N° ODS</label>
                            <input type="text" name="ods_number" class="form-input" placeholder="ODS-2025-001" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Contrat lié</label>
                            <select name="contract_id" class="form-select">
                                <option value="">Sélectionner...</option>
                                <?php foreach ($contracts as $c): ?>
                                <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['contract_number']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Description</label>
                            <textarea name="description" class="form-input" rows="4" placeholder="Description de l'ODS"></textarea>
                        </div>
                    </div>
                    <div>
                        <div class="form-group">
                            <label class="form-label">Montant (DA)</label>
                            <input type="number" name="amount" class="form-input" placeholder="0.00" step="0.01">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Date emission</label>
                            <input type="date" name="issue_date" class="form-input">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Bureau</label>
                            <select name="bureau" class="form-select">
                                <option value="">Sélectionner...</option>
                                <?php foreach ($bureaus as $b): ?>
                                <option value="<?= $b ?>"><?= $bureau_list[$b] ?? $b ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Statut</label>
                            <select name="status" class="form-select">
                                <option value="pending">En attente</option>
                                <option value="in_progress">En cours</option>
                                <option value="completed">Terminé</option>
                                <option value="cancelled">Annulé</option>
                            </select>
                        </div>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary mt-4"><i class="fa-solid fa-save"></i> Enregistrer</button>
            </form>
        </div>
    </div>
    <?php else: ?>
    <div class="grid-2">
        <div>
            <div class="card">
                <div class="card-header">
                    <span class="card-title">Informations ODS</span>
                    <span class="badge badge-<?= $ods['status'] ?>"><?= str_replace('_', ' ', $ods['status']) ?></span>
                </div>
                <div class="card-body">
                    <div class="field"><span class="field-label">N° ODS</span><span class="field-value"><?= htmlspecialchars($ods['ods_number']) ?></span></div>
                    <div class="field"><span class="field-label">Contrat</span><span class="field-value"><?= htmlspecialchars($ods['contract_number'] ?? '-') ?></span></div>
                    <div class="field"><span class="field-label">Entreprise</span><span class="field-value"><?= htmlspecialchars($ods['enterprise_name'] ?? '-') ?></span></div>
                    <div class="field"><span class="field-label">Bureau</span><span class="field-value"><?= htmlspecialchars($ods['bureau'] ?? '-') ?></span></div>
                </div>
            </div>
            <div class="card">
                <div class="card-header"><span class="card-title">Paiements (<?= count($payments) ?>)</span></div>
                <div class="card-body">
                    <?php if (count($payments) > 0): ?>
                        <?php foreach ($payments as $pay): ?>
                        <div class="item-row">
                            <span class="item-num"><?= htmlspecialchars($pay['dossier_number']) ?></span>
                            <span class="badge badge-<?= $pay['status'] ?>"><?= str_replace('_', ' ', $pay['status']) ?></span>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                    <div class="list-empty"><i class="fa-solid fa-credit-card"></i><p>Aucun paiement</p></div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div>
            <div class="card">
                <div class="card-header"><span class="card-title">Montant</span></div>
                <div class="card-body">
                    <div class="field"><span class="field-label">Montant</span><span class="field-value money"><?= $ods['amount'] ? number_format($ods['amount'], 2, ',', ' ') . ' DA' : '-' ?></span></div>
                    <div class="field"><span class="field-label">Date emission</span><span class="field-value"><?= $ods['issue_date'] ? date('d/m/Y', strtotime($ods['issue_date'])) : '-' ?></span></div>
                    <div class="field"><span class="field-label">Créé le</span><span class="field-value"><?= $ods['created_at'] ? date('d/m/Y', strtotime($ods['created_at'])) : '-' ?></span></div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>
<?php require_once '../../includes/footer.php'; ?>