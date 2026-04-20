<?php
require_once '../../includes/auth.php';
$pageTitle = 'Mon Profil';
require_once '../../includes/header.php';

$user = getUser();
$conn = getDB();
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_profile'])) {
        $first_name = $conn->real_escape_string($_POST['first_name']);
        $last_name = $conn->real_escape_string($_POST['last_name']);
        $email = $conn->real_escape_string($_POST['email']);
        $phone = $conn->real_escape_string($_POST['phone']);
        $bureau = $conn->real_escape_string($_POST['bureau']);
        
        $user_id = $_SESSION['user_id'];
        $conn->query("UPDATE users SET first_name='$first_name', last_name='$last_name', email='$email', phone='$phone', bureau='$bureau' WHERE id=$user_id");
        
        $_SESSION['user_name'] = $first_name . ' ' . $last_name;
        $user = getUser();
        $message = 'Profil mis à jour avec succès!';
    }
    
    if (isset($_POST['change_password'])) {
        $current = $_POST['current_password'];
        $new = $_POST['new_password'];
        $confirm = $_POST['confirm_password'];
        
        $user_id = $_SESSION['user_id'];
        $result = $conn->query("SELECT password FROM users WHERE id = $user_id");
        $row = $result->fetch_assoc();
        
        if (password_verify($current, $row['password']) || $current === $row['password']) {
            if ($new === $confirm) {
                $hash = password_hash($new, PASSWORD_DEFAULT);
                $conn->query("UPDATE users SET password='$hash' WHERE id=$user_id");
                $message = 'Mot de passe changé!';
            } else {
                $error = 'Les mots de passe ne correspondent pas.';
            }
        } else {
            $error = 'Mot de passe actuel incorrect.';
        }
    }
}
?>
<style>
:root { --bg: #0b0f0e; --surface: #111615; --surface2: #161d1b; --border: rgba(255,255,255,.07); --border2: rgba(0,191,165,.18); --teal: #00BFA5; --teal-dim: rgba(0,191,165,.12); --teal-glow: rgba(0,191,165,.25); --amber: #F59E0B; --red: #EF4444; --blue: #3B82F6; --text: #E8EDEC; --text-muted: #6B7A78; --text-dim: #9AAFAD; --radius: 14px; --radius-sm: 8px; }
.page { padding: 28px; }
.page-header { margin-bottom:28px; }
.page-title { font-family:'Syne',sans-serif; font-size:26px; font-weight:800; color:var(--text); }
.page-subtitle { font-size:13px; color:var(--text-muted); margin-top:4px; }
.alert { padding:14px 18px; border-radius:var(--radius-sm); margin-bottom:20px; }
.alert-success { background:rgba(0,191,165,.15); color:var(--teal); border:1px solid var(--teal); }
.alert-error { background:rgba(239,68,68,.15); color:var(--red); border:1px solid var(--red); }
.grid-2 { display:grid; grid-template-columns: 1fr 1fr; gap:24px; }
.card { background:var(--surface); border:1px solid var(--border); border-radius:var(--radius); }
.card-header { padding:16px 20px; border-bottom:1px solid var(--border); }
.card-title { font-family:'Syne',sans-serif; font-size:15px; font-weight:700; color:var(--text); }
.card-body { padding:20px; }
.form-group { margin-bottom:16px; }
.form-label { display:block; font-size:12px; font-weight:600; color:var(--text-muted); margin-bottom:6px; }
.form-input { width:100%; background:var(--surface2); border:1px solid var(--border); color:var(--text); padding:10px 14px; border-radius:var(--radius-sm); font-size:13px; }
.form-input:focus { outline:none; border-color:var(--border2); }
.btn { display:inline-flex; align-items:center; gap:8px; padding:10px 20px; border-radius:var(--radius-sm); font-size:13px; font-weight:600; cursor:pointer; transition:all .2s; }
.btn-primary { background:var(--teal); color:#000; box-shadow:0 4px 14px var(--teal-glow); }
.user-info { display:flex; align-items:center; gap:16px; margin-bottom:20px; }
.avatar { width:60px; height:60px; background:linear-gradient(135deg, var(--teal), #007A6A); border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:24px; font-weight:700; color:#000; }
.user-details strong { display:block; font-size:16px; color:var(--text); }
.user-details span { font-size:12px; color:var(--teal); }
@media(max-width:900px) { .grid-2 { grid-template-columns:1fr; } }
@media(max-width:600px) { .page { padding:16px; } }
</style>

<div class="page">
    <div class="page-header">
        <h1 class="page-title">Mon Profil</h1>
        <p class="page-subtitle">Gestion de votre compte</p>
    </div>

    <?php if ($message): ?>
    <div class="alert alert-success"><i class="fa-solid fa-check-circle"></i> <?= $message ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
    <div class="alert alert-error"><i class="fa-solid fa-triangle-exclamation"></i> <?= $error ?></div>
    <?php endif; ?>

    <div class="user-info">
        <div class="avatar"><?= strtoupper(substr($user['first_name'], 0, 1)) ?></div>
        <div class="user-details">
            <strong><?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?></strong>
            <span><?= ucfirst($user['role']) ?></span>
        </div>
    </div>

    <div class="grid-2">
        <div class="card">
            <div class="card-header"><div class="card-title"><i class="fa-solid fa-user"></i> Informations</div></div>
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="update_profile" value="1">
                    <div class="form-group">
                        <label class="form-label">Prénom</label>
                        <input type="text" name="first_name" class="form-input" value="<?= htmlspecialchars($user['first_name']) ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Nom</label>
                        <input type="text" name="last_name" class="form-input" value="<?= htmlspecialchars($user['last_name']) ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-input" value="<?= htmlspecialchars($user['email']) ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Téléphone</label>
                        <input type="text" name="phone" class="form-input" value="<?= htmlspecialchars($user['phone'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Bureau</label>
                        <input type="text" name="bureau" class="form-input" value="<?= htmlspecialchars($user['bureau'] ?? '') ?>" placeholder="EX: DRT, SDT">
                    </div>
                    <button type="submit" class="btn btn-primary"><i class="fa-solid fa-save"></i> Enregistrer</button>
                </form>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header"><div class="card-title"><i class="fa-solid fa-lock"></i> Mot de passe</div></div>
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="change_password" value="1">
                    <div class="form-group">
                        <label class="form-label">Mot de passe actuel</label>
                        <input type="password" name="current_password" class="form-input" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Nouveau mot de passe</label>
                        <input type="password" name="new_password" class="form-input" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Confirmer</label>
                        <input type="password" name="confirm_password" class="form-input" required>
                    </div>
                    <button type="submit" class="btn btn-primary"><i class="fa-solid fa-key"></i> Changer</button>
                </form>
            </div>
        </div>
    </div>
</div>
<?php require_once '../../includes/footer.php'; ?>