<?php
require_once '../../includes/auth.php';
$pageTitle = 'Départements';
require_once '../../includes/header.php';

$user = getUser();
if ($user['role'] !== 'super_admin') {
    echo "<div class='p-8 text-red-600'>Accès restreint.</div>";
    require_once '../../includes/footer.php';
    exit;
}

$conn = getDB();
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_dept'])) {
        $name = $conn->real_escape_string($_POST['name']);
        $code = $conn->real_escape_string($_POST['code']);
        $desc = $conn->real_escape_string($_POST['description']);
        
        if ($name && $code) {
            $conn->query("INSERT INTO departments (name, code, description) VALUES ('$name', '$code', '$desc')");
            $message = 'Département ajouté avec succès!';
        }
    }
    
    if (isset($_POST['update_dept'])) {
        $id = intval($_POST['id']);
        $name = $conn->real_escape_string($_POST['name']);
        $code = $conn->real_escape_string($_POST['code']);
        $desc = $conn->real_escape_string($_POST['description']);
        
        $conn->query("UPDATE departments SET name='$name', code='$code', description='$desc' WHERE id=$id");
        $message = 'Département mis à jour!';
    }
    
    if (isset($_GET['delete'])) {
        $id = intval($_GET['delete']);
        $conn->query("DELETE FROM departments WHERE id=$id");
        $message = 'Département supprimé!';
    }
}

$departments = [];
$result = $conn->query("SELECT d.*, (SELECT COUNT(*) FROM users WHERE department_id = d.id) as user_count FROM departments d ORDER BY d.name");
while ($row = $result->fetch_assoc()) {
    $departments[] = $row;
}
?>
<style>
:root { --bg: #0b0f0e; --surface: #111615; --surface2: #161d1b; --border: rgba(255,255,255,.07); --border2: rgba(0,191,165,.18); --teal: #00BFA5; --teal-dim: rgba(0,191,165,.12); --teal-glow: rgba(0,191,165,.25); --amber: #F59E0B; --red: #EF4444; --blue: #3B82F6; --text: #E8EDEC; --text-muted: #6B7A78; --text-dim: #9AAFAD; --radius: 14px; --radius-sm: 8px; }
.page { padding: 28px; }
.page-header { display:flex; align-items:flex-start; justify-content:space-between; margin-bottom:28px; }
.page-title { font-family:'Syne',sans-serif; font-size:26px; font-weight:800; color:var(--text); }
.page-subtitle { font-size:13px; color:var(--text-muted); margin-top:4px; }
.alert { padding:14px 18px; border-radius:var(--radius-sm); margin-bottom:20px; background:rgba(0,191,165,.15); color:var(--teal); }
.btn { display:inline-flex; align-items:center; gap:8px; padding:10px 20px; border-radius:var(--radius-sm); font-size:13px; font-weight:600; cursor:pointer; transition:all .2s; }
.btn-primary { background:var(--teal); color:#000; box-shadow:0 4px 14px var(--teal-glow); }
.btn-outline { background:transparent; color:var(--text); border:1px solid var(--border); }
.btn-sm { padding:6px 12px; font-size:12px; }
.btn-danger { background:rgba(239,68,68,.15); color:var(--red); border:1px solid var(--red); }
.card { background:var(--surface); border:1px solid var(--border); border-radius:var(--radius); margin-bottom:24px; }
.card-header { padding:16px 20px; border-bottom:1px solid var(--border); }
.card-title { font-family:'Syne',sans-serif; font-size:15px; font-weight:700; color:var(--text); }
.card-body { padding:20px; }
.form-group { margin-bottom:16px; }
.form-label { display:block; font-size:12px; font-weight:600; color:var(--text-muted); margin-bottom:6px; }
.form-input { width:100%; background:var(--surface2); border:1px solid var(--border); color:var(--text); padding:10px 14px; border-radius:var(--radius-sm); font-size:13px; }
.modal { display:none; position:fixed; inset:0; background:rgba(0,0,0,.6); z-index:1000; align-items:center; justify-content:center; }
.modal.open { display:flex; }
.modal-content { background:var(--surface); border:1px solid var(--border); border-radius:var(--radius); width:100%; max-width:500px; }
.table-wrap { background:var(--surface); border:1px solid var(--border); border-radius:var(--radius); overflow:hidden; }
.dept-table { width:100%; border-collapse:collapse; }
.dept-table th { font-size:11px; font-weight:700; letter-spacing:.1em; text-transform:uppercase; color:var(--text-muted); padding:14px 16px; text-align:left; border-bottom:1px solid var(--border); background:var(--surface2); }
.dept-table td { padding:14px 16px; border-bottom:1px solid rgba(255,255,255,.04); font-size:13px; color:var(--text-dim); }
.dept-code { font-family:monospace; font-weight:700; color:var(--teal); background:var(--teal-dim); padding:4px 8px; border-radius:4px; }
.action-btn { padding:6px 10px; color:var(--text-muted); text-decoration:none; }
.action-btn:hover { color:var(--teal); }
</style>

<div class="page">
    <div class="page-header">
        <div>
            <h1 class="page-title">Départements</h1>
            <p class="page-subtitle">Gestion des departements</p>
        </div>
        <button class="btn btn-primary" onclick="document.getElementById('addModal').classList.add('open')">
            <i class="fa-solid fa-plus"></i> Ajouter
        </button>
    </div>

    <?php if ($message): ?>
    <div class="alert"><i class="fa-solid fa-check-circle"></i> <?= $message ?></div>
    <?php endif; ?>

    <div class="table-wrap">
        <table class="dept-table">
            <thead>
                <tr>
                    <th>Code</th>
                    <th>Nom</th>
                    <th>Description</th>
                    <th>Utilisateurs</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($departments as $dept): ?>
                <tr>
                    <td><span class="dept-code"><?= htmlspecialchars($dept['code']) ?></span></td>
                    <td style="color:var(--text);font-weight:600;"><?= htmlspecialchars($dept['name']) ?></td>
                    <td><?= htmlspecialchars($dept['description'] ?? '-') ?></td>
                    <td><?= $dept['user_count'] ?></td>
                    <td>
                        <button class="action-btn" onclick="editDept(<?= $dept['id'] ?>, '<?= htmlspecialchars($dept['name']) ?>', '<?= htmlspecialchars($dept['code']) ?>', '<?= htmlspecialchars($dept['description'] ?? '') ?>')"><i class="fa-solid fa-pen"></i></button>
                        <a href="?delete=<?= $dept['id'] ?>" class="action-btn" onclick="return confirm('Supprimer?')"><i class="fa-solid fa-trash"></i></a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div id="addModal" class="modal">
    <div class="modal-content">
        <div class="card-header"><div class="card-title">Nouveau Departement</div></div>
        <div class="card-body">
            <form method="POST">
                <input type="hidden" name="add_dept" value="1">
                <div class="form-group">
                    <label class="form-label">Code</label>
                    <input type="text" name="code" class="form-input" required placeholder="EX: DFC">
                </div>
                <div class="form-group">
                    <label class="form-label">Nom</label>
                    <input type="text" name="name" class="form-input" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Description</label>
                    <input type="text" name="description" class="form-input">
                </div>
                <button type="submit" class="btn btn-primary">Ajouter</button>
                <button type="button" class="btn btn-outline" onclick="document.getElementById('addModal').classList.remove('open')">Annuler</button>
            </form>
        </div>
    </div>
</div>

<script>
function editDept(id, name, code, desc) {
    document.getElementById('editForm').innerHTML = `
        <form method="POST">
            <input type="hidden" name="update_dept" value="1">
            <input type="hidden" name="id" value="${id}">
            <div class="form-group"><label class="form-label">Code</label><input type="text" name="code" class="form-input" value="${code}"></div>
            <div class="form-group"><label class="form-label">Nom</label><input type="text" name="name" class="form-input" value="${name}"></div>
            <div class="form-group"><label class="form-label">Description</label><input type="text" name="description" class="form-input" value="${desc}"></div>
            <button type="submit" class="btn btn-primary">Mettre a jour</button>
            <button type="button" class="btn btn-outline" onclick="this.closest('.modal').classList.remove('open')">Annuler</button>
        </form>
    `;
    document.getElementById('editModal').classList.add('open');
}
</script>

<div id="editModal" class="modal">
    <div class="modal-content">
        <div class="card-header"><div class="card-title">Modifier Departement</div></div>
        <div class="card-body" id="editForm"></div>
    </div>
</div>
<?php require_once '../../includes/footer.php'; ?>