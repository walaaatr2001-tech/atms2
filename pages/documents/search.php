<?php
// pages/documents/search.php - Search contracts with upload + filter
require_once '../../includes/auth.php';
$pageTitle = 'Recherche Contrats';
require_once '../../includes/header.php';

// Detect schema
$schema_check = @$conn->query("SHOW COLUMNS FROM contracts LIKE 'contract_number'");
$new_schema = ($schema_check && $schema_check->num_rows > 0);
?>
<style>
:root { --bg: #0b0f0e; --surface: #111615; --surface2: #161d1b; --border: rgba(255,255,255,.07); --border2: rgba(0,191,165,.18); --teal: #00BFA5; --teal-dim: rgba(0,191,165,.12); --teal-glow: rgba(0,191,165,.25); --amber: #F59E0B; --red: #EF4444; --green: #22C55E; --blue: #3B82F6; --text: #E8EDEC; --text-muted: #6B7A78; --text-dim: #9AAFAD; --radius: 14px; --radius-sm: 8px; }
.page { padding: 24px; }
.page-header { margin-bottom: 24px; }
.page-title { font-family: 'Syne',sans-serif; font-size: 26px; font-weight: 800; color: var(--text); }
.page-subtitle { font-size: 13px; color: var(--text-muted); margin-top: 4px; }
.card { background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius); padding: 20px; margin-bottom: 20px; }
.card-title { font-size: 14px; font-weight: 700; color: var(--text); margin-bottom: 16px; display: flex; align-items: center; gap: 8px; }
.form-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 12px; }
.form-group { position: relative; }
.form-label { display: block; font-size: 10px; font-weight: 700; letter-spacing: .15em; text-transform: uppercase; color: var(--text-muted); margin-bottom: 6px; }
.form-input, .form-select { width: 100%; background: var(--surface2); border: 1px solid var(--border); color: var(--text); padding: 12px 14px; border-radius: var(--radius-sm); font-size: 14px; font-family: 'Inter',sans-serif; }
.form-input:focus, .form-select:focus { outline: none; border-color: var(--border2); }
.form-input::placeholder { color: var(--text-muted); }
.form-select option { background: #121212; color: white; }
.search-btn { background: var(--teal); color: black; font-weight: 700; padding: 12px 24px; border-radius: var(--radius-sm); border: none; cursor: pointer; transition: all .2s; }
.search-btn:hover { background: #00e6c4; }
.clear-btn { background: transparent; border: 1px solid var(--border); color: var(--text-muted); padding: 12px 20px; border-radius: var(--radius-sm); cursor: pointer; transition: all .2s; }
.drop-zone { background: #0d0d0d; border: 2px dashed #2a2a2a; border-radius: var(--radius); padding: 24px; text-align: center; cursor: pointer; transition: all .25s; }
.drop-zone:hover, .drop-zone.dragover { border-color: var(--teal); background: rgba(0,191,165,0.04); }
.drop-zone.has-file { border-color: var(--teal); border-style: solid; background: rgba(0,191,165,0.05); }
.drop-text { color: var(--text-muted); font-size: 14px; }
.drop-sub { color: #444; font-size: 12px; margin-top: 4px; }
.file-pill { background: rgba(0,191,165,0.08); border: 1px solid rgba(0,191,165,0.2); border-radius: 12px; padding: 10px 14px; display: flex; align-items: center; gap: 10px; margin-top: 12px; }
.ext-badge { background: var(--teal); color: black; font-size: 10px; font-weight: 700; padding: 2px 7px; border-radius: 5px; text-transform: uppercase; }
.table-wrap { overflow-x: auto; }
.data-table { width: 100%; border-collapse: collapse; font-size: 13px; }
.data-table th { text-align: left; padding: 12px 14px; background: var(--surface2); color: var(--text-muted); font-size: 10px; font-weight: 700; letter-spacing: .15em; text-transform: uppercase; border-bottom: 1px solid var(--border); }
.data-table td { padding: 14px; border-bottom: 1px solid var(--border); color: var(--text); }
.data-table tr:hover { background: rgba(255,255,255,0.02); }
.data-table a { color: var(--teal); text-decoration: none; }
.data-table a:hover { text-decoration: underline; }
.status-badge { padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: 600; }
.status-active { background: rgba(0,191,165,0.15); color: var(--teal); }
.status-completed { background: rgba(59,130,246,0.15); color: var(--blue); }
.status-pending { background: rgba(245,158,11,0.15); color: var(--amber); }
.status-paid { background: rgba(34,197,94,0.15); color: var(--green); }
.status-cancelled { background: rgba(239,68,68,0.15); color: var(--red); }
.action-btn { padding: 6px 12px; border-radius: 6px; font-size: 12px; background: rgba(0,191,165,0.1); color: var(--teal); border: 1px solid rgba(0,191,165,0.2); cursor: pointer; }
.action-btn:hover { background: rgba(0,191,165,0.2); }
.empty { text-align: center; padding: 48px; color: var(--text-muted); }
.empty i { font-size: 36px; margin-bottom: 12px; opacity: 0.5; }
.loading { text-align: center; padding: 48px; color: var(--text-muted); }
.loading i { font-size: 24px; animation: spin 1s linear infinite; }
@keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
.ai-toggle { background: var(--surface2); border: 1px solid var(--border); border-radius: var(--radius-sm); padding: 14px; display: flex; align-items: center; gap: 12px; cursor: pointer; }
.ai-toggle:hover { border-color: var(--border2); }
.ai-toggle input { width: 18px; height: 18px; accent-color: var(--teal); }
.ai-text { flex: 1; }
.ai-title { font-size: 14px; font-weight: 600; color: var(--text); }
.ai-desc { font-size: 12px; color: var(--text-muted); }
@media(max-width: 768px) { .form-grid { grid-template-columns: 1fr; } }
</style>

<div class="page">
    <div class="page-header">
        <h1 class="page-title">Recherche Contrats</h1>
        <p class="page-subtitle">Recherchez et filtrez les contrats, ODS et dossiers de paiement</p>
    </div>

    <!-- Upload Zone -->
    <div class="card" id="upload-card">
        <div class="card-title">
            <i class="fa-solid fa-cloud-arrow-up text-teal-400"></i>
            Televerser un document
        </div>
        <input type="file" name="file" id="upload-file" accept=".pdf,.docx,.xlsx,.jpg,.jpeg,.png" class="hidden">
        <div class="drop-zone" id="drop-zone">
            <div id="drop-idle">
                <i class="fa-solid fa-cloud-arrow-up text-3xl text-teal-500 mb-3"></i>
                <p class="drop-text">Cliquez ou deposez votre fichier ici</p>
                <p class="drop-sub">PDF · DOCX · XLSX — max 50 MB</p>
            </div>
            <div id="drop-selected" class="hidden">
                <i class="fa-solid fa-circle-check text-teal-400 text-2xl mb-2"></i>
                <p class="text-teal-300 text-sm font-semibold" id="file-name">—</p>
                <p class="text-gray-500 text-xs" id="file-size">—</p>
            </div>
        </div>
        <div id="file-pill-container"></div>
        
        <div class="ai-toggle mt-4" onclick="document.getElementById('process-ai').click()">
            <input type="checkbox" name="process_ai" id="process-ai" value="1" checked>
            <div class="ai-text">
                <p class="ai-title">Analyse IA automatique</p>
                <p class="ai-desc">Extraction intelligente du contenu via Gemini AI</p>
            </div>
            <i class="fa-solid fa-robot text-teal-500 text-xl"></i>
        </div>
        
        <div class="flex gap-3 mt-4">
            <button type="button" onclick="uploadFile()" class="search-btn flex-1">
                <i class="fa-solid fa-upload mr-2"></i>Televerser
            </button>
        </div>
        
        <div id="upload-status" class="mt-3"></div>
    </div>

    <!-- Filter Card -->
    <div class="card">
        <div class="card-title">
            <i class="fa-solid fa-filter text-teal-400"></i>
            Filtres de recherche
        </div>
        <div class="form-grid">
            <div class="form-group">
                <label class="form-label">N° Contrat</label>
                <input type="text" id="filter-contract" class="form-input" placeholder="53/2025 or 2023-1476">
            </div>
            <div class="form-group">
                <label class="form-label">Annee</label>
                <select id="filter-year" class="form-select">
                    <option value="">Toutes les annees</option>
                    <?php for($y = 2026; $y >= 2020; $y--): ?>
                    <option value="<?= $y ?>"><?= $y ?></option>
                    <?php endfor; ?>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">RC ou NIF Entreprise</label>
                <input type="text" id="filter-rc" class="form-input" placeholder="18143110155117804300">
            </div>
            <div class="form-group">
                <label class="form-label">Type de contrat</label>
                <select id="filter-type" class="form-select">
                    <option value="">Tous les types</option>
                    <option value="Contrat d'adhesion a commandes">Contrat d'adhesion a commandes</option>
                    <option value="Marche a commandes">Marche a commandes</option>
                    <option value="Marche simple">Marche simple</option>
                    <option value="Marche a tranches conditionnelles">Marche a tranches conditionnelles</option>
                    <option value="Contrat programme">Contrat programme</option>
                    <option value="Coordination de commandes">Coordination de commandes</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Date debut</label>
                <input type="date" id="filter-date-from" class="form-input">
            </div>
            <div class="form-group">
                <label class="form-label">Date fin</label>
                <input type="date" id="filter-date-to" class="form-input">
            </div>
        </div>
        <div class="flex gap-3 mt-4">
            <button type="button" onclick="performSearch()" class="search-btn">
                <i class="fa-solid fa-magnifying-glass mr-2"></i>Rechercher
            </button>
            <button type="button" onclick="clearFilters()" class="clear-btn">
                <i class="fa-solid fa-xmark mr-1"></i>Effacer
            </button>
        </div>
    </div>

    <!-- Results Table -->
    <div class="card">
        <div class="card-title">
            <i class="fa-solid fa-list text-teal-400"></i>
            Resultats <span id="result-count" class="text-gray-500 text-xs font-normal normal-case ml-2"></span>
        </div>
        <div class="table-wrap">
            <table class="data-table" id="results-table">
                <thead>
                    <tr>
                        <th>N° Contrat</th>
                        <th>Type</th>
                        <th>Entreprise (RC)</th>
                        <th>Annee</th>
                        <th>Montant</th>
                        <th>Statut</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="results-body">
                </tbody>
            </table>
        </div>
        <div id="results-empty" class="empty">
            <i class="fa-regular fa-folder-open"></i>
            <p>Aucun resultat — Effectuez une recherche</p>
        </div>
        <div id="results-loading" class="loading hidden">
            <i class="fa-solid fa-circle-notch"></i>
            <p>Recherche en cours...</p>
        </div>
    </div>
</div>

<script>
let currentDocumentId = null;

function uploadFile() {
    const fileInput = document.getElementById('upload-file');
    if (!fileInput.files.length) {
        alert('Veuillez selectionner un fichier');
        return;
    }
    
    const formData = new FormData();
    formData.append('file', fileInput.files[0]);
    formData.append('title', fileInput.files[0].name);
    formData.append('process_ai', document.getElementById('process-ai').checked ? '1' : '0');
    
    const statusDiv = document.getElementById('upload-status');
    statusDiv.innerHTML = '<div class="loading"><i class="fa-solid fa-circle-notch"></i> Televersement en cours...</div>';
    
    fetch('ajax_upload.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            statusDiv.innerHTML = '<div class="bg-teal-900/20 border border-teal-500/40 text-teal-200 px-4 py-3 rounded-xl text-sm"><i class="fa-solid fa-check-circle mr-2"></i>Document televerse avec succes!</div>';
            currentDocumentId = data.document_id;
            
            if (data.extracted && data.extracted.contract_number) {
                document.getElementById('filter-contract').value = data.extracted.contract_number || '';
                
                setTimeout(() => performSearch(), 500);
            } else {
                performSearch();
            }
        } else {
            statusDiv.innerHTML = '<div class="bg-red-900/20 border border-red-500/40 text-red-200 px-4 py-3 rounded-xl text-sm"><i class="fa-solid fa-triangle-exclamation mr-2"></i>' + (data.error || 'Erreur') + '</div>';
        }
    })
    .catch(err => {
        statusDiv.innerHTML = '<div class="bg-red-900/20 border border-red-500/40 text-red-200 px-4 py-3 rounded-xl text-sm"><i class="fa-solid fa-triangle-exclamation mr-2"></i>Erreur: ' + err + '</div>';
    });
}

document.getElementById('drop-zone').addEventListener('click', () => document.getElementById('upload-file').click());
document.getElementById('drop-zone').addEventListener('dragover', e => { e.preventDefault(); document.getElementById('drop-zone').classList.add('dragover'); });
document.getElementById('drop-zone').addEventListener('dragleave', () => document.getElementById('drop-zone').classList.remove('dragover'));
document.getElementById('drop-zone').addEventListener('drop', e => {
    e.preventDefault();
    document.getElementById('drop-zone').classList.remove('dragover');
    if (e.dataTransfer.files.length) {
        document.getElementById('upload-file').files = e.dataTransfer.files;
        handleFileSelect(e.dataTransfer.files[0]);
    }
});
document.getElementById('upload-file').addEventListener('change', () => {
    if (document.getElementById('upload-file').files.length) handleFileSelect(document.getElementById('upload-file').files[0]);
});

function handleFileSelect(file) {
    document.getElementById('drop-idle').classList.add('hidden');
    document.getElementById('drop-selected').classList.remove('hidden');
    document.getElementById('file-name').textContent = file.name;
    document.getElementById('file-size').textContent = formatBytes(file.size);
    document.getElementById('drop-zone').classList.add('has-file');
    document.getElementById('file-pill-container').innerHTML = `
        <div class="file-pill">
            <span class="ext-badge">${file.name.split('.').pop().toUpperCase()}</span>
            <span class="text-gray-300 text-sm truncate flex-1">${file.name}</span>
            <span class="text-gray-500 text-xs">${formatBytes(file.size)}</span>
        </div>`;
}

function formatBytes(b) {
    if (b < 1024) return b + ' o';
    if (b < 1048576) return (b / 1024).toFixed(1) + ' Ko';
    return (b / 1048576).toFixed(2) + ' Mo';
}

function performSearch() {
    const contract = document.getElementById('filter-contract').value.trim();
    const year = document.getElementById('filter-year').value;
    const rc = document.getElementById('filter-rc').value.trim();
    const type = document.getElementById('filter-type').value;
    const dateFrom = document.getElementById('filter-date-from').value;
    const dateTo = document.getElementById('filter-date-to').value;
    
    document.getElementById('results-body').innerHTML = '';
    document.getElementById('results-empty').classList.add('hidden');
    document.getElementById('results-loading').classList.remove('hidden');
    
    fetch(`ajax_search.php?action=contract_search&contract=${encodeURIComponent(contract)}&year=${year}&rc=${encodeURIComponent(rc)}&type=${encodeURIComponent(type)}&date_from=${dateFrom}&date_to=${dateTo}`)
    .then(res => res.json())
    .then(data => {
        document.getElementById('results-loading').classList.add('hidden');
        
        if (data.count === 0 || data.results.length === 0) {
            document.getElementById('results-empty').classList.remove('hidden');
            document.getElementById('result-count').textContent = '(0)';
            return;
        }
        
        document.getElementById('result-count').textContent = '(' + data.count + ')';
        let html = '';
        data.results.forEach(item => {
            const statusClass = {
                'active': 'status-active',
                'completed': 'status-completed',
                'pending': 'status-pending',
                'in_progress': 'status-pending',
                'paid': 'status-paid',
                'suspended': 'status-cancelled',
                'cancelled': 'status-cancelled'
            }[item.status] || 'status-pending';
            
            const displayStatus = {
                'active': 'Actif',
                'completed': 'Termine',
                'pending': 'En attente',
                'in_progress': 'En cours',
                'paid': 'Payer',
                'suspended': 'Suspendu',
                'cancelled': 'Annule'
            }[item.status] || item.status || 'En attente';
            
            html += `<tr>
                <td><a href="contract_detail.php?id=${item.id}">${item.contract_number || '-'}</a></td>
                <td>${item.contract_type || '-'}</td>
                <td>${item.enterprise_rc || '-'}</td>
                <td>${item.year || '-'}</td>
                <td>${item.amount ? formatAmount(item.amount) + ' DA' : '-'}</td>
                <td><span class="status-badge ${statusClass}">${displayStatus}</span></td>
                <td><a href="contract_detail.php?id=${item.id}" class="action-btn">Voir</a></td>
            </tr>`;
        });
        document.getElementById('results-body').innerHTML = html;
    })
    .catch(err => {
        document.getElementById('results-loading').classList.add('hidden');
        document.getElementById('results-empty').classList.remove('hidden');
    });
}

function clearFilters() {
    document.getElementById('filter-contract').value = '';
    document.getElementById('filter-year').value = '';
    document.getElementById('filter-rc').value = '';
    document.getElementById('filter-type').value = '';
    document.getElementById('filter-date-from').value = '';
    document.getElementById('filter-date-to').value = '';
    document.getElementById('results-body').innerHTML = '';
    document.getElementById('results-empty').classList.remove('hidden');
    document.getElementById('result-count').textContent = '';
}

function formatAmount(amount) {
    return new Intl.NumberFormat('fr-DZ', { minimumFractionDigits: 2 }).format(amount);
}
</script>
<?php require_once '../../includes/footer.php'; ?>