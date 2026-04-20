<?php
require_once '../../includes/auth.php';
$pageTitle = 'Détails du Contrat';
require_once '../../includes/header.php';

$id = intval($_GET['id'] ?? 0);
$action = $_GET['action'] ?? '';

$contract = null;
$ods_list = [];
$documents = [];

if ($action === 'new') {
    $pageTitle = 'Nouveau Contrat';
} elseif ($id > 0) {
    $stmt = $conn->prepare("SELECT c.*, e.rc as enterprise_rc, e.nif as enterprise_nif,
                           CONCAT(ue.first_name, ' ', ue.last_name) as enterprise_name, d.name as dept_name
                           FROM contracts c 
                           LEFT JOIN enterprises e ON c.enterprise_id = e.id
                           LEFT JOIN users ue ON e.user_id = ue.id
                           LEFT JOIN departments d ON c.department_id = d.id
                           WHERE c.id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $contract = $result->fetch_assoc();
    
    if ($contract) {
        $stmt = $conn->prepare("SELECT * FROM ods WHERE contract_id = ? ORDER BY created_at DESC");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $ods_list[] = $row;
        }
        
        $stmt = $conn->prepare("SELECT d.*, dc.name as cat_name FROM documents d 
                               LEFT JOIN document_categories dc ON d.category_id = dc.id
                               WHERE d.contract_id = ? ORDER BY d.created_at DESC");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $documents[] = $row;
        }
    }
}

$departments = [];
$result = $conn->query("SELECT * FROM departments ORDER BY name");
while ($row = $result->fetch_assoc()) {
    $departments[] = $row;
}

$enterprises = [];
$result = $conn->query("SELECT e.id, CONCAT(ue.first_name, ' ', ue.last_name) as name, e.rc 
                        FROM enterprises e 
                        LEFT JOIN users ue ON e.user_id = ue.id 
                        WHERE e.status = 'approved' 
                        ORDER BY ue.first_name");
while ($row = $result->fetch_assoc()) {
    $enterprises[] = $row;
}

$contract_types = [
    "Contrat d'adhésion à commandes",
    "Marché à commandes",
    "Marché simple",
    "Marché à tranches conditionnelles",
    "Contrat programme",
    "Coordination de commandes"
];
?>
<style>
:root { --bg: #0b0f0e; --surface: #111615; --surface2: #161d1b; --border: rgba(255,255,255,.07); --border2: rgba(0,191,165,.18); --teal: #00BFA5; --teal-dim: rgba(0,191,165,.12); --teal-glow: rgba(0,191,165,.25); --amber: #F59E0B; --red: #EF4444; --blue: #3B82F6; --purple: #8B5CF6; --text: #E8EDEC; --text-muted: #6B7A78; --text-dim: #9AAFAD; --radius: 14px; --radius-sm: 8px; }
.page { padding: 24px; }
.page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; flex-wrap: wrap; gap: 16px; }
.page-title { font-size: 26px; font-weight: 800; color: var(--text); }
.page-subtitle { font-size: 13px; color: var(--text-muted); margin-top: 4px; }
.card { background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius); margin-bottom: 20px; }
.card-header { padding: 16px 20px; border-bottom: 1px solid var(--border); display: flex; align-items: center; justify-content: space-between; }
.card-title { font-size: 15px; font-weight: 700; color: var(--text); }
.card-body { padding: 20px; }
.grid-2 { display: grid; grid-template-columns: 2fr 1fr; gap: 20px; }
@media (max-width: 900px) { .grid-2 { grid-template-columns: 1fr; } }
.btn { display: inline-flex; align-items: center; gap: 8px; padding: 10px 20px; border-radius: var(--radius-sm); font-size: 13px; font-weight: 600; transition: all .2s; text-decoration: none; cursor: pointer; border: none; }
.btn-primary { background: var(--teal); color: #000; }
.btn-primary:hover { background: #00e6c4; }
.btn-outline { background: transparent; color: var(--text); border: 1px solid var(--border); }
.form-group { margin-bottom: 16px; }
.form-label { display: block; font-size: 10px; font-weight: 700; letter-spacing: .15em; text-transform: uppercase; color: var(--text-muted); margin-bottom: 6px; }
.form-input, .form-select, .form-textarea { width: 100%; background: var(--surface2); border: 1px solid var(--border); color: var(--text); padding: 12px 14px; border-radius: var(--radius-sm); font-size: 14px; font-family: 'Inter', sans-serif; }
.form-input:focus, .form-select:focus, .form-textarea:focus { outline: none; border-color: var(--border2); }
.form-select option { background: #121212; }
.form-textarea { min-height: 100px; resize: vertical; }
.field { display: flex; justify-content: space-between; padding: 12px 0; border-bottom: 1px solid rgba(255,255,255,0.04); }
.field:last-child { border-bottom: none; }
.field-label { font-size: 13px; color: var(--text-muted); }
.field-value { font-size: 13px; font-weight: 600; color: var(--text); }
.field-value.money { color: var(--amber); font-weight: 700; }
.badge { font-size: 11px; font-weight: 600; padding: 4px 12px; border-radius: 20px; }
.badge-active { background: rgba(0,191,165,0.15); color: var(--teal); }
.badge-completed { background: rgba(59,130,246,0.15); color: var(--blue); }
.badge-suspended { background: rgba(245,158,11,0.15); color: var(--amber); }
.badge-cancelled { background: rgba(239,68,68,0.15); color: var(--red); }
.list-empty { text-align: center; padding: 30px; color: var(--text-muted); }
.list-empty i { font-size: 32px; margin-bottom: 10px; opacity: 0.5; display: block; }
.item-row { display: flex; align-items: center; justify-content: space-between; padding: 12px; background: var(--surface2); border-radius: var(--radius-sm); margin-bottom: 8px; }
.item-num { font-family: monospace; font-weight: 600; color: var(--teal); }
.item-amount { font-weight: 700; color: var(--amber); }
.item-date { font-size: 12px; color: var(--text-muted); }
</style>

<div class="page">
    <div class="page-header">
        <div>
            <h1 class="page-title"><?= $action === 'new' ? 'Nouveau Contrat' : 'Détails du Contrat' ?></h1>
            <p class="page-subtitle"><?= $contract ? htmlspecialchars($contract['contract_number']) : 'Créer un nouveau contrat' ?></p>
        </div>
        <?php if ($action !== 'new'): ?>
        <a href="contracts.php" class="btn btn-outline">
            <i class="fa-solid fa-arrow-left"></i> Retour
        </a>
        <?php endif; ?>
    </div>

    <?php if ($action === 'new'): ?>
    <div class="card">
        <div class="card-body">
            <form method="POST" action="ajax_contract_save.php">
                <div class="grid-2">
                    <div>
                        <div class="form-group">
                            <label class="form-label">N° Contrat</label>
                            <input type="text" name="contract_number" class="form-input" placeholder="53/2025" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Titre</label>
                            <input type="text" name="title" class="form-input" placeholder="Titre du contrat" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Type de contrat</label>
                            <select name="contract_type" class="form-select" required>
                                <option value="">Sélectionner...</option>
                                <?php foreach ($contract_types as $type): ?>
                                <option value="<?= $type ?>"><?= $type ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Entreprise</label>
                            <select name="enterprise_id" class="form-select">
                                <option value="">Sélectionner...</option>
                                <?php foreach ($enterprises as $ent): ?>
                                <option value="<?= $ent['id'] ?>"><?= htmlspecialchars($ent['name']) ?> (<?= htmlspecialchars($ent['rc']) ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div>
                        <div class="form-group">
                            <label class="form-label">Montant total (DA)</label>
                            <input type="number" name="total_amount" class="form-input" placeholder="0.00" step="0.01">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Date début</label>
                            <input type="date" name="start_date" class="form-input">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Date fin</label>
                            <input type="date" name="end_date" class="form-input">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Département</label>
                            <select name="department_id" class="form-select">
                                <option value="">Sélectionner...</option>
                                <?php foreach ($departments as $dept): ?>
                                <option value="<?= $dept['id'] ?>"><?= htmlspecialchars($dept['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Statut</label>
                            <select name="status" class="form-select">
                                <option value="active">Actif</option>
                                <option value="completed">Terminé</option>
                                <option value="suspended">Suspendu</option>
                                <option value="cancelled">Annulé</option>
                            </select>
                        </div>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary mt-4">
                    <i class="fa-solid fa-save"></i> Enregistrer
                </button>
            </form>
        </div>
    </div>
    <?php else: ?>
    <div class="grid-2">
        <div>
            <div class="card">
                <div class="card-header">
                    <span class="card-title">Informations du contrat</span>
                    <span class="badge badge-<?= $contract['status'] ?>"><?= ucfirst($contract['status']) ?></span>
                </div>
                <div class="card-body">
                    <div class="field"><span class="field-label">N° Contrat</span><span class="field-value"><?= htmlspecialchars($contract['contract_number']) ?></span></div>
                    <div class="field"><span class="field-label">Type</span><span class="field-value"><?= htmlspecialchars($contract['contract_type'] ?? '-') ?></span></div>
                    <div class="field"><span class="field-label">Entreprise</span><span class="field-value"><?= htmlspecialchars($contract['enterprise_name'] ?? '-') ?></span></div>
                    <div class="field"><span class="field-label">RC Entreprise</span><span class="field-value"><?= htmlspecialchars($contract['enterprise_rc'] ?? '-') ?></span></div>
                    <div class="field"><span class="field-label">Département</span><span class="field-value"><?= htmlspecialchars($contract['dept_name'] ?? '-') ?></span></div>
                </div>
            </div>
            <div class="card">
                <div class="card-header">
                    <span class="card-title">ODS liès (<?= count($ods_list) ?>)</span>
                </div>
                <div class="card-body">
                    <?php if (count($ods_list) > 0): ?>
                        <?php foreach ($ods_list as $ods): ?>
                        <div class="item-row">
                            <div>
                                <div class="item-num"><?= htmlspecialchars($ods['ods_number']) ?></div>
                                <div class="item-date"><?= $ods['issue_date'] ? date('d/m/Y', strtotime($ods['issue_date'])) : '' ?></div>
                            </div>
                            <div class="item-amount"><?= $ods['amount'] ? number_format($ods['amount'], 2, ',', ' ') . ' DA' : '-' ?></div>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                    <div class="list-empty"><i class="fa-solid fa-file-lines"></i><p>Aucun ODS</p></div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div>
            <div class="card">
                <div class="card-header">
                    <span class="card-title">Montant</span>
                </div>
                <div class="card-body">
                    <div class="field"><span class="field-label">Montant total</span><span class="field-value money"><?= $contract['total_amount'] ? number_format($contract['total_amount'], 2, ',', ' ') . ' DA' : '-' ?></span></div>
                    <div class="field"><span class="field-label">Date début</span><span class="field-value"><?= $contract['start_date'] ? date('d/m/Y', strtotime($contract['start_date'])) : '-' ?></span></div>
                    <div class="field"><span class="field-label">Date fin</span><span class="field-value"><?= $contract['end_date'] ? date('d/m/Y', strtotime($contract['end_date'])) : '-' ?></span></div>
                    <div class="field"><span class="field-label">Créé le</span><span class="field-value"><?= $contract['created_at'] ? date('d/m/Y', strtotime($contract['created_at'])) : '-' ?></span></div>
                </div>
            </div>
            <div class="card">
                <div class="card-header">
                    <span class="card-title">Documents (<?= count($documents) ?>)</span>
                </div>
                <div class="card-body">
                    <?php if (count($documents) > 0): ?>
                        <?php foreach ($documents as $doc): ?>
                        <div class="item-row">
                            <span class="item-num"><?= htmlspecialchars($doc['reference_number']) ?></span>
                            <span class="item-date"><?= htmlspecialchars($doc['file_type']) ?></span>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                    <div class="list-empty"><i class="fa-solid fa-file-pdf"></i><p>Aucun document</p></div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>
<?php require_once '../../includes/footer.php'; ?>