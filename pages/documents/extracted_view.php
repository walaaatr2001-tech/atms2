<?php
require_once '../../includes/auth.php';
$pageTitle = 'Extraction IA - Document';
require_once '../../includes/header.php';

$id = intval($_GET['id'] ?? 0);
$conn = getDB();
$stmt = $conn->prepare("SELECT * FROM documents WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$doc = $stmt->get_result()->fetch_assoc();

if (!$doc || empty($doc['extracted_json'])) {
    echo "<div class='p-8 text-red-600'>Aucune extraction IA disponible.</div>";
    require_once '../../includes/footer.php';
    exit;
}

$data = json_decode($doc['extracted_json'], true) ?? [];

function extractField($array, $keys) {
    foreach ($keys as $key) {
        if (isset($array[$key])) return $array[$key];
    }
    return null;
}

$amount = extractField($data, ['amount', 'montant', 'total', 'valeur']);
$enterprise = extractField($data, ['enterprise', 'entreprise', 'company', 'societe']);
$contractNum = extractField($data, ['contract_number', 'numero_contrat', 'numero', 'reference']);
$date = extractField($data, ['date', 'date_signature', 'date_creation']);
$status = extractField($data, ['status', 'statut', 'etat']);
?>
<style>
:root {
  --bg:           #0b0f0e;
  --surface:      #111615;
  --surface2:     #161d1b;
  --border:       rgba(255,255,255,.07);
  --border2:      rgba(0,191,165,.18);
  --teal:         #00BFA5;
  --teal-dim:     rgba(0,191,165,.12);
  --teal-glow:    rgba(0,191,165,.25);
  --amber:        #F59E0B;
  --red:          #EF4444;
  --blue:         #3B82F6;
  --purple:       #8B5CF6;
  --text:         #E8EDEC;
  --text-muted:   #6B7A78;
  --text-dim:     #9AAFAD;
  --radius:       14px;
  --radius-sm:    8px;
}
.ai-page { padding: 28px; }
.ai-header { display:flex; align-items:flex-start; justify-content:space-between; margin-bottom:28px; gap:16px; flex-wrap:wrap; }
.ai-title { font-family:'Syne',sans-serif; font-size:26px; font-weight:800; color:var(--text); }
.ai-subtitle { font-size:13px; color:var(--text-muted); margin-top:4px; }
.ai-badge { display:inline-flex; align-items:center; gap:6px; background:var(--teal-dim); color:var(--teal); padding:6px 14px; border-radius:20px; font-size:12px; font-weight:600; }
.ai-grid { display:grid; grid-template-columns: repeat(auto-fit, minmax(280px,1fr)); gap:16px; margin-bottom:24px; }
.ai-card { background:var(--surface); border:1px solid var(--border); border-radius:var(--radius); padding:20px; position:relative; overflow:hidden; }
.ai-card::before { content:''; position:absolute; top:0; left:0; right:0; height:2px; background: linear-gradient(90deg, transparent, var(--teal), transparent); opacity:.7; }
.ai-card-label { font-size:11px; font-weight:700; letter-spacing:.15em; text-transform:uppercase; color:var(--text-muted); margin-bottom:8px; }
.ai-card-value { font-size:20px; font-weight:700; color:var(--text); }
.ai-card-sub { font-size:12px; color:var(--text-dim); margin-top:4px; }
.ai-detail-card { background:var(--surface); border:1px solid var(--border); border-radius:var(--radius); }
.ai-detail-header { padding:16px 20px; border-bottom:1px solid var(--border); font-family:'Syne',sans-serif; font-size:15px; font-weight:700; color:var(--text); }
.ai-detail-body { padding:20px; }
.ai-field { display:flex; justify-content:space-between; padding:10px 0; border-bottom:1px solid rgba(255,255,255,.04); }
.ai-field:last-child { border-bottom:none; }
.ai-field-label { font-size:13px; color:var(--text-muted); }
.ai-field-value { font-size:13px; font-weight:600; color:var(--text); }
.ai-summary { background:linear-gradient(135deg, var(--surface2) 0%, var(--surface) 100%); border:1px solid var(--border); border-radius:var(--radius); padding:20px; margin-bottom:24px; }
.ai-summary-title { font-size:13px; font-weight:700; letter-spacing:.1em; text-transform:uppercase; color:var(--teal); margin-bottom:12px; }
.ai-summary-text { font-size:14px; line-height:1.7; color:var(--text); }
.ai-actions { display:flex; gap:12px; flex-wrap:wrap; }
.btn { display:inline-flex; align-items:center; gap:8px; padding:10px 20px; border-radius:var(--radius-sm); font-size:13px; font-weight:600; transition:all .2s; white-space:nowrap; text-decoration:none; }
.btn-primary { background:var(--teal); color:#000; box-shadow:0 4px 14px var(--teal-glow); }
.btn-primary:hover { background:#00e6c4; transform:translateY(-1px); }
.btn-outline { background:transparent; color:var(--text); border:1px solid var(--border); }
.btn-outline:hover { border-color:var(--border2); color:var(--teal); }
.empty-state { text-align:center; padding:40px; color:var(--text-muted); }
.empty-state i { font-size:48px; margin-bottom:16px; display:block; opacity:.5; }
@media(max-width:600px) { .ai-page { padding:16px; } .ai-header { flex-direction:column; } }
</style>

<div class="ai-page">
    <div class="ai-header anim-1">
        <div>
            <h1 class="ai-title">Extraction IA</h1>
            <p class="ai-subtitle"><?= htmlspecialchars($doc['title']) ?></p>
        </div>
        <span class="ai-badge">
            <i class="fa-solid fa-robot"></i>
            <?= $doc['ai_processed'] ? 'Traitée par IA' : 'En attente' ?>
        </span>
    </div>

    <?php if (!empty($data)): ?>
    <div class="ai-grid anim-2">
        <?php if ($amount): ?>
        <div class="ai-card">
            <div class="ai-card-label">Montant</div>
            <div class="ai-card-value"><?= is_numeric($amount) ? number_format($amount, 2, ',', ' ') . ' DA' : htmlspecialchars($amount) ?></div>
        </div>
        <?php endif; ?>
        
        <?php if ($enterprise): ?>
        <div class="ai-card">
            <div class="ai-card-label">Entreprise</div>
            <div class="ai-card-value"><?= htmlspecialchars($enterprise) ?></div>
        </div>
        <?php endif; ?>
        
        <?php if ($contractNum): ?>
        <div class="ai-card">
            <div class="ai-card-label">N° Contrat</div>
            <div class="ai-card-value"><?= htmlspecialchars($contractNum) ?></div>
        </div>
        <?php endif; ?>
        
        <?php if ($date): ?>
        <div class="ai-card">
            <div class="ai-card-label">Date</div>
            <div class="ai-card-value"><?= htmlspecialchars($date) ?></div>
        </div>
        <?php endif; ?>
        
        <?php if ($status): ?>
        <div class="ai-card">
            <div class="ai-card-label">Statut</div>
            <div class="ai-card-value"><?= htmlspecialchars(ucfirst($status)) ?></div>
        </div>
        <?php endif; ?>
    </div>

    <?php if (!empty($data['summary'])): ?>
    <div class="ai-summary anim-3">
        <div class="ai-summary-title"><i class="fa-solid fa-lightbulb"></i> Résumé IA</div>
        <div class="ai-summary-text"><?= nl2br(htmlspecialchars($data['summary'])) ?></div>
    </div>
    <?php endif; ?>

    <div class="ai-detail-card anim-4">
        <div class="ai-detail-header"><i class="fa-solid fa-list-ul"></i> Données extraites</div>
        <div class="ai-detail-body">
            <?php foreach ($data as $key => $value): ?>
            <?php if (!in_array($key, ['summary', 'companies']) && !empty($value)): ?>
            <div class="ai-field">
                <span class="ai-field-label"><?= htmlspecialchars(ucfirst(str_replace('_', ' ', $key))) ?></span>
                <span class="ai-field-value"><?= is_array($value) ? htmlspecialchars(implode(', ', $value)) : htmlspecialchars($value) ?></span>
            </div>
            <?php endif; ?>
            <?php endforeach; ?>
            
            <?php if (!empty($data['companies'])): ?>
            <div class="ai-field">
                <span class="ai-field-label">Entreprises détectées</span>
                <span class="ai-field-value"><?= htmlspecialchars(implode(', ', (array)$data['companies'])) ?></span>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <?php else: ?>
    <div class="empty-state">
        <i class="fa-solid fa-robot"></i>
        <p>Aucune donnée extraite</p>
    </div>
    <?php endif; ?>

    <div class="ai-actions" style="margin-top:24px;">
        <a href="list.php" class="btn btn-outline">
            <i class="fa-solid fa-arrow-left"></i> Retour à la liste
        </a>
        <?php if ($doc['file_path']): ?>
        <a href="../../<?= htmlspecialchars($doc['file_path']) ?>" target="_blank" class="btn btn-primary">
            <i class="fa-solid fa-file"></i> Voir le document
        </a>
        <?php endif; ?>
        <a href="detail.php?id=<?= $doc['id'] ?>" class="btn btn-outline">
            <i class="fa-solid fa-info-circle"></i> Détails du document
        </a>
    </div>
</div>
<?php require_once '../../includes/footer.php'; ?>