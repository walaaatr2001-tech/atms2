<?php
require_once '../../includes/auth.php';
$pageTitle = 'Recherche Avancée';
require_once '../../includes/header.php';

$user = getUser();
?>
<style>
.search-page { padding: 28px; max-width: 1400px; margin: 0 auto; }
.search-card { background: rgba(13,13,13,0.85); backdrop-filter: blur(12px); border: 1px solid rgba(255,255,255,0.08); border-radius: 1rem; padding: 24px; margin-bottom: 24px; }
.search-title { font-size: 20px; font-weight: 700; color: #E8EDEC; margin-bottom: 20px; }
.form-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px,1fr)); gap: 16px; }
.form-group { display: flex; flex-direction: column; }
.form-label { font-size: 12px; font-weight: 600; color: #6B7A78; margin-bottom: 6px; text-transform: uppercase; letter-spacing: 0.05em; }
.form-input, .form-select { background: #161d1b; border: 1px solid rgba(255,255,255,0.07); color: #E8EDEC; padding: 12px 14px; border-radius: 8px; font-size: 14px; }
.form-input:focus, .form-select:focus { outline: none; border-color: #00BFA5; }
.btn { display: inline-flex; align-items: center; gap: 8px; padding: 12px 24px; border-radius: 8px; font-size: 14px; font-weight: 600; cursor: pointer; border: none; }
.btn-primary { background: #00BFA5; color: #000; }
.btn-outline { background: transparent; color: #E8EDEC; border: 1px solid rgba(255,255,255,0.1); }
.btn-group { display: flex; gap: 12px; margin-top: 20px; }
.result-tabs { display: flex; gap: 8px; margin-bottom: 20px; flex-wrap: wrap; }
.tab-btn { padding: 10px 20px; border-radius: 8px; font-size: 13px; font-weight: 600; background: rgba(22,29,27,0.8); color: #6B7A78; border: 1px solid rgba(255,255,255,0.05); cursor: pointer; }
.tab-btn.active { background: rgba(0,191,165,0.15); color: #00BFA5; border-color: #00BFA5; }
.tab-count { background: #00BFA5; color: #000; font-size: 10px; padding: 2px 6px; border-radius: 10px; margin-left: 6px; }
.results-container { min-height: 200px; }
.result-group { margin-bottom: 24px; }
.group-title { font-size: 14px; font-weight: 700; color: #00BFA5; margin-bottom: 12px; padding-bottom: 8px; border-bottom: 1px solid rgba(255,255,255,0.07); }
.result-card { background: rgba(13,13,13,0.85); border: 1px solid rgba(255,255,255,0.08); border-radius: 12px; padding: 16px; margin-bottom: 12px; transition: all 0.2s; }
.result-card:hover { border-color: rgba(0,191,165,0.3); transform: translateY(-2px); }
.result-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 12px; }
.result-type { font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.1em; color: #00BFA5; background: rgba(0,191,165,0.15); padding: 4px 10px; border-radius: 20px; }
.result-number { font-size: 16px; font-weight: 700; color: #E8EDEC; }
.result-meta { display: flex; gap: 20px; flex-wrap: wrap; margin: 8px 0; font-size: 13px; color: #9AAFAD; }
.result-meta span { display: flex; align-items: center; gap: 6px; }
.match-tag { font-size: 11px; background: rgba(245,158,11,0.15); color: #F59E0B; padding: 3px 8px; border-radius: 12px; margin-right: 6px; }
.badge { font-size: 11px; font-weight: 600; padding: 4px 10px; border-radius: 20px; }
.badge-active { background: rgba(0,191,165,0.15); color: #00BFA5; }
.badge-pending { background: rgba(245,158,11,0.15); color: #F59E0B; }
.badge-completed { background: rgba(59,130,246,0.15); color: #3B82F6; }
.badge-paid { background: rgba(34,197,94,0.15); color: #22C55E; }
.badge-approved { background: rgba(0,191,165,0.15); color: #00BFA5; }
.result-actions { margin-top: 12px; padding-top: 12px; border-top: 1px solid rgba(255,255,255,0.05); }
.btn-view { display: inline-flex; align-items: center; gap: 6px; padding: 8px 16px; background: #00BFA5; color: #000; border-radius: 6px; font-size: 12px; font-weight: 600; text-decoration: none; }
.empty-state { text-align: center; padding: 60px 20px; color: #6B7A78; }
.empty-state i { font-size: 48px; margin-bottom: 16px; display: block; opacity: 0.5; }
.loader { display: flex; justify-content: center; padding: 40px; }
.loader i { font-size: 32px; color: #00BFA5; animation: spin 1s linear infinite; }
@keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
.highlight { background: rgba(245,158,11,0.2); color: #F59E0B; padding: 1px 4px; border-radius: 3px; }
</style>

<div class="search-page">
    <div class="search-card">
        <h2 class="search-title"><i class="fa-solid fa-magnifying-glass"></i> Recherche Avancée</h2>
        <form id="advancedSearchForm">
            <div class="form-grid">
                <div class="form-group">
                    <label class="form-label">N° Contrat</label>
                    <input type="text" name="contract_number" class="form-input" placeholder="EX: AT/2025/001">
                </div>
                <div class="form-group">
                    <label class="form-label">N° ODS</label>
                    <input type="text" name="ods_number" class="form-input" placeholder="EX: ODS N°183/DRT/SDT/2025">
                </div>
                <div class="form-group">
                    <label class="form-label">RC Entreprise</label>
                    <input type="text" name="rc_number" class="form-input" placeholder="N° Registre de Commerce">
                </div>
                <div class="form-group">
                    <label class="form-label">NIF Entreprise</label>
                    <input type="text" name="nif_number" class="form-input" placeholder="N° Identification Fiscale">
                </div>
                <div class="form-group">
                    <label class="form-label">Nom Entreprise</label>
                    <input type="text" name="enterprise_name" class="form-input" placeholder="Nom de l'entreprise">
                </div>
                <div class="form-group">
                    <label class="form-label">Année</label>
                    <select name="year" class="form-select">
                        <option value="">Toutes les années</option>
                        <?php for($y = date('Y'); $y >= 2020; $y--): ?>
                        <option value="<?= $y ?>"><?= $y ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Type Document</label>
                    <select name="doc_type" class="form-select">
                        <option value="">Tous les types</option>
                        <option value="ods">ODS</option>
                        <option value="facture">Facture</option>
                        <option value="pv_reception">PV Réception</option>
                        <option value="attachement">Attachement</option>
                        <option value="bon_commande">Bon de Commande</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Bureau</label>
                    <select name="bureau" class="form-select">
                        <option value="">Tous les bureaux</option>
                        <option value="DRT">DRT</option>
                        <option value="SDT">SDT</option>
                        <option value="DRT/SDT">DRT/SDT</option>
                        <option value="DRT/AGL">DRT/AGL</option>
                        <option value="DRT/OST">DRT/OST</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Montant Min (DA)</label>
                    <input type="number" name="amount_min" class="form-input" placeholder="0">
                </div>
                <div class="form-group">
                    <label class="form-label">Montant Max (DA)</label>
                    <input type="number" name="amount_max" class="form-input" placeholder="999999999">
                </div>
                <div class="form-group">
                    <label class="form-label">Statut</label>
                    <select name="status" class="form-select">
                        <option value="">Tous les statuts</option>
                        <option value="active">Actif</option>
                        <option value="pending">En attente</option>
                        <option value="completed">Terminé</option>
                        <option value="paid">Payé</option>
                        <option value="approved">Approuvé</option>
                    </select>
                </div>
            </div>
            <div class="btn-group">
                <button type="submit" class="btn btn-primary">
                    <i class="fa-solid fa-search"></i> Rechercher
                </button>
                <button type="button" class="btn btn-outline" onclick="clearSearch()">
                    <i class="fa-solid fa-rotate-right"></i> Réinitialiser
                </button>
            </div>
        </form>
    </div>

    <div id="resultsArea">
        <div class="result-tabs" id="resultTabs">
            <button class="tab-btn active" data-type="all">Tous <span class="tab-count" id="countAll">0</span></button>
            <button class="tab-btn" data-type="contracts">Contrats <span class="tab-count" id="countContracts">0</span></button>
            <button class="tab-btn" data-type="ods">ODS <span class="tab-count" id="countODS">0</span></button>
            <button class="tab-btn" data-type="documents">Documents <span class="tab-count" id="countDocuments">0</span></button>
            <button class="tab-btn" data-type="enterprises">Entreprises <span class="tab-count" id="countEnterprises">0</span></button>
        </div>
        <div class="results-container" id="resultsContainer">
            <div class="empty-state">
                <i class="fa-solid fa-search"></i>
                <p>Effectuez une recherche pour voir les résultats</p>
            </div>
        </div>
    </div>
</div>

<script>
let currentResults = {};
let currentTab = 'all';

document.getElementById('advancedSearchForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    const data = {};
    for (let [key, value] of formData.entries()) {
        if (value) data[key] = value;
    }
    
    document.getElementById('resultsContainer').innerHTML = '<div class="loader"><i class="fa-solid fa-circle-notch"></i></div>';
    
    fetch('ajax_advanced_search.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data)
    })
    .then(res => res.json())
    .then(data => {
        currentResults = data;
        renderResults();
    })
    .catch(err => {
        document.getElementById('resultsContainer').innerHTML = '<div class="empty-state"><i class="fa-solid fa-triangle-exclamation"></i><p>Erreur de recherche</p></div>';
    });
});

document.querySelectorAll('.tab-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
        this.classList.add('active');
        currentTab = this.dataset.type;
        renderResults();
    });
});

function renderResults() {
    const container = document.getElementById('resultsContainer');
    const filtered = currentTab === 'all' ? currentResults : (currentResults[currentTab] || []);
    
    if (filtered.length === 0) {
        container.innerHTML = '<div class="empty-state"><i class="fa-solid fa-folder-open"></i><p>Aucun résultat trouvé</p></div>';
        return;
    }
    
    let html = '';
    const groups = currentTab === 'all' ? { contracts: currentResults.contracts || [], ods: currentResults.ods || [], documents: currentResults.documents || [], enterprises: currentResults.enterprises || [] } : { [currentTab]: filtered };
    
    for (const [type, items] of Object.entries(groups)) {
        if (items.length === 0) continue;
        html += `<div class="result-group"><div class="group-title">${type.charAt(0).toUpperCase() + type.slice(1)} (${items.length})</div>`;
        
        items.forEach(item => {
            const matches = item.matches || [];
            const url = item.url || '#';
            html += `
            <div class="result-card">
                <div class="result-header">
                    <div>
                        <span class="result-type">${item.type}</span>
                        <div class="result-number">${item.number}</div>
                    </div>
                    <span class="badge badge-${item.status || 'pending'}">${item.status_display || item.status || '—'}</span>
                </div>
                <div class="result-meta">
                    ${item.enterprise ? `<span><i class="fa-solid fa-building"></i> ${item.enterprise}</span>` : ''}
                    ${item.amount ? `<span><i class="fa-solid fa-money-bill"></i> ${formatAmount(item.amount)} DA</span>` : ''}
                    ${item.date ? `<span><i class="fa-solid fa-calendar"></i> ${item.date}</span>` : ''}
                </div>
                ${matches.length > 0 ? `<div style="margin-top:8px;">${matches.map(m => `<span class="match-tag">${m.field}: ${m.value}</span>`).join('')}</div>` : ''}
                <div class="result-actions">
                    <a href="${url}" class="btn-view"><i class="fa-regular fa-eye"></i> Voir détails</a>
                </div>
            </div>`;
        });
        html += '</div>';
    }
    
    container.innerHTML = html;
    updateCounts();
}

function updateCounts() {
    document.getElementById('countAll').textContent = (currentResults.contracts?.length || 0) + (currentResults.ods?.length || 0) + (currentResults.documents?.length || 0) + (currentResults.enterprises?.length || 0);
    document.getElementById('countContracts').textContent = currentResults.contracts?.length || 0;
    document.getElementById('countODS').textContent = currentResults.ods?.length || 0;
    document.getElementById('countDocuments').textContent = currentResults.documents?.length || 0;
    document.getElementById('countEnterprises').textContent = currentResults.enterprises?.length || 0;
}

function formatAmount(amount) {
    return new Intl.NumberFormat('fr-DZ').format(amount);
}

function clearSearch() {
    document.getElementById('advancedSearchForm').reset();
    document.getElementById('resultsContainer').innerHTML = '<div class="empty-state"><i class="fa-solid fa-search"></i><p>Effectuez une recherche pour voir les résultats</p></div>';
    currentResults = {};
    updateCounts();
}
</script>
<?php require_once '../../includes/footer.php'; ?>