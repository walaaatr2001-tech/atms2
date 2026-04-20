<?php
require_once '../../includes/auth.php';
$pageTitle = 'Paramètres';
require_once '../../includes/header.php';

$user = getUser();
if ($user['role'] !== 'super_admin') {
    echo "<div class='p-8 text-red-600'>Accès restreint.</div>";
    require_once '../../includes/footer.php';
    exit;
}

$conn = getDB();
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['save_settings'])) {
        $settings = ['site_name', 'site_description', 'admin_email', 'allowed_extensions', 'max_file_size'];
        foreach ($settings as $key) {
            if (isset($_POST[$key])) {
                $value = $conn->real_escape_string($_POST[$key]);
                $conn->query("INSERT INTO settings (`key`, `value`) VALUES ('$key', '$value') ON DUPLICATE KEY UPDATE `value` = '$value'");
            }
        }
        $message = 'Paramètres enregistrés avec succès!';
    }
    
    if (isset($_POST['save_ai_key'])) {
        $api_key = $conn->real_escape_string($_POST['api_key']);
        $conn->query("INSERT INTO settings (`key`, `value`) VALUES ('ai_api_key', '$api_key') ON DUPLICATE KEY UPDATE `value` = '$api_key'");
        $message = 'Clé API enregistrée!';
    }
}

$settings = [];
$result = $conn->query("SELECT `key`, `value` FROM settings");
while ($row = $result->fetch_assoc()) {
    $settings[$row['key']] = $row['value'];
}
?>
<style>
:root { --bg: #0b0f0e; --surface: #111615; --surface2: #161d1b; --border: rgba(255,255,255,.07); --border2: rgba(0,191,165,.18); --teal: #00BFA5; --teal-dim: rgba(0,191,165,.12); --teal-glow: rgba(0,191,165,.25); --amber: #F59E0B; --red: #EF4444; --blue: #3B82F6; --text: #E8EDEC; --text-muted: #6B7A78; --text-dim: #9AAFAD; --radius: 14px; --radius-sm: 8px; }
.page { padding: 28px; }
.page-header { margin-bottom:28px; }
.page-title { font-family:'Syne',sans-serif; font-size:26px; font-weight:800; color:var(--text); }
.page-subtitle { font-size:13px; color:var(--text-muted); margin-top:4px; }
.alert { padding:14px 18px; border-radius:var(--radius-sm); margin-bottom:20px; font-size:13px; }
.alert-success { background:rgba(0,191,165,.15); color:var(--teal); border:1px solid var(--teal); }
.alert-error { background:rgba(239,68,68,.15); color:var(--red); border:1px solid var(--red); }
.card { background:var(--surface); border:1px solid var(--border); border-radius:var(--radius); margin-bottom:24px; }
.card-header { padding:16px 20px; border-bottom:1px solid var(--border); }
.card-title { font-family:'Syne',sans-serif; font-size:15px; font-weight:700; color:var(--text); }
.card-body { padding:20px; }
.form-group { margin-bottom:16px; }
.form-label { display:block; font-size:12px; font-weight:600; color:var(--text-muted); margin-bottom:6px; }
.form-input, .form-textarea { width:100%; background:var(--surface2); border:1px solid var(--border); color:var(--text); padding:10px 14px; border-radius:var(--radius-sm); font-size:13px; }
.form-input:focus, .form-textarea:focus { outline:none; border-color:var(--border2); }
.btn { display:inline-flex; align-items:center; gap:8px; padding:10px 20px; border-radius:var(--radius-sm); font-size:13px; font-weight:600; cursor:pointer; transition:all .2s; }
.btn-primary { background:var(--teal); color:#000; box-shadow:0 4px 14px var(--teal-glow); }
.btn-outline { background:transparent; color:var(--text); border:1px solid var(--border); }
.file-types { display:flex; flex-wrap:wrap; gap:8px; }
.file-type { padding:6px 12px; background:var(--surface2); border-radius:var(--radius-sm); font-size:12px; color:var(--text-dim); }
.toggle { display:flex; align-items:center; gap:12px; }
.toggle-switch { width:44px; height:24px; background:var(--surface2); border-radius:12px; position:relative; cursor:pointer; transition:all .2s; }
.toggle-switch.active { background:var(--teal); }
.toggle-switch::after { content:''; position:absolute; width:18px; height:18px; background:var(--text); border-radius:50%; top:3px; left:3px; transition:all .2s; }
.toggle-switch.active::after { left:23px; }
</style>

<div class="page">
    <div class="page-header">
        <h1 class="page-title">Paramètres Système</h1>
        <p class="page-subtitle">Configuration generale et API</p>
    </div>

    <?php if ($message): ?>
    <div class="alert alert-success"><i class="fa-solid fa-check-circle"></i> <?= $message ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
    <div class="alert alert-error"><i class="fa-solid fa-triangle-exclamation"></i> <?= $error ?></div>
    <?php endif; ?>

    <div class="card">
        <div class="card-header"><div class="card-title"><i class="fa-solid fa-globe"></i> Parametres Generaux</div></div>
        <div class="card-body">
            <form method="POST">
                <input type="hidden" name="save_settings" value="1">
                <div class="form-group">
                    <label class="form-label">Nom du site</label>
                    <input type="text" name="site_name" class="form-input" value="<?= htmlspecialchars($settings['site_name'] ?? 'AT-AMS') ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">Description</label>
                    <textarea name="site_description" class="form-textarea"><?= htmlspecialchars($settings['site_description'] ?? '') ?></textarea>
                </div>
                <div class="form-group">
                    <label class="form-label">Email administrateur</label>
                    <input type="email" name="admin_email" class="form-input" value="<?= htmlspecialchars($settings['admin_email'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">Extensions autorisees (separateur virgule)</label>
                    <input type="text" name="allowed_extensions" class="form-input" value="<?= htmlspecialchars($settings['allowed_extensions'] ?? 'pdf,docx,xlsx,jpg,png,zip,rar') ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">Taille max fichier (bytes)</label>
                    <input type="number" name="max_file_size" class="form-input" value="<?= htmlspecialchars($settings['max_file_size'] ?? '52428800') ?>">
                </div>
                <button type="submit" class="btn btn-primary"><i class="fa-solid fa-save"></i> Enregistrer</button>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><div class="card-title"><i class="fa-solid fa-key"></i> API IA</div></div>
        <div class="card-body">
            <form method="POST">
                <input type="hidden" name="save_ai_key" value="1">
                <div class="form-group">
                    <label class="form-label">Cle API (Gemini/OpenAI)</label>
                    <input type="password" name="api_key" class="form-input" placeholder="sk-..." value="<?= htmlspecialchars($settings['ai_api_key'] ?? '') ?>">
                </div>
                <button type="submit" class="btn btn-primary"><i class="fa-solid fa-save"></i> Enregistrer la cle</button>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><div class="card-title"><i class="fa-solid fa-file"></i> Types de fichiers</div></div>
        <div class="card-body">
            <div class="file-types">
                <span class="file-type">PDF</span>
                <span class="file-type">DOCX</span>
                <span class="file-type">XLSX</span>
                <span class="file-type">JPG</span>
                <span class="file-type">PNG</span>
                <span class="file-type">ZIP</span>
                <span class="file-type">RAR</span>
            </div>
        </div>
    </div>
</div>
<?php require_once '../../includes/footer.php'; ?>