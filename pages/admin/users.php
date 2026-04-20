<?php
/**
 * AT-AMS — pages/admin/users.php
 * User management for super_admin and dept_admin.
 * super_admin : sees ALL users, full CRUD
 * dept_admin  : sees ONLY their department users
 * Self-contained — no broken includes.
 * Drop at: C:\xampp\htdocs\AT-AMS\pages\admin\users.php
 */

if (session_status() === PHP_SESSION_NONE) session_start();

/* ── Auth guard ─────────────────────────────────────────────── */
if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php'); exit;
}
$role    = $_SESSION['user_role'] ?? '';
$dept_id = (int)($_SESSION['department_id'] ?? 0);
$me_id   = (int)$_SESSION['user_id'];

if (!in_array($role, ['super_admin', 'dept_admin'])) {
    header('Location: ../auth/login.php'); exit;
}

/* ── Load DB ────────────────────────────────────────────────── */
$conn  = null;
$db_ok = false;
$cfg   = __DIR__ . '/../../config/config.php';
if (file_exists($cfg)) {
    try {
        require_once $cfg;
        if (function_exists('getDB')) { $conn = getDB(); $db_ok = true; }
    } catch (Throwable $e) {}
}

$is_super = ($role === 'super_admin');

/* ── Helper ─────────────────────────────────────────────────── */
function qrows(string $sql, $conn): array {
    if (!$conn) return [];
    try { $r = $conn->query($sql); if ($r) return $r->fetch_all(MYSQLI_ASSOC); }
    catch (Throwable $e) {}
    return [];
}
function qone(string $sql, $conn): ?array {
    if (!$conn) return null;
    try { $r = $conn->query($sql); if ($r) return $r->fetch_assoc(); }
    catch (Throwable $e) {}
    return null;
}
function qexec(string $sql, $conn): bool {
    if (!$conn) return false;
    try { return (bool)$conn->query($sql); }
    catch (Throwable $e) {}
    return false;
}

/* ── Handle POST actions ────────────────────────────────────── */
$action_msg  = '';
$action_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $conn) {
    $act     = $_POST['action']  ?? '';
    $uid     = (int)($_POST['user_id'] ?? 0);
    $new_role= $conn->real_escape_string($_POST['new_role'] ?? '');
    $new_dept= (int)($_POST['new_dept'] ?? 0);
    $new_pass= $_POST['new_password'] ?? '';

    // Security: dept_admin can only act on users in their dept
    if (!$is_super && $uid) {
        $check = qone("SELECT id FROM users WHERE id=$uid AND department_id=$dept_id", $conn);
        if (!$check) { $action_msg = 'Action non autorisée.'; $action_type = 'error'; goto done; }
    }
    // Prevent acting on yourself
    if ($uid === $me_id) {
        $action_msg = 'Vous ne pouvez pas modifier votre propre compte ici.';
        $action_type = 'error'; goto done;
    }

    switch ($act) {
        case 'approve':
            if (qexec("UPDATE users SET status='active' WHERE id=$uid", $conn)) {
                $action_msg = 'Compte approuvé avec succès.'; $action_type = 'success';
            }
            break;
        case 'reject':
            if (qexec("UPDATE users SET status='rejected' WHERE id=$uid", $conn)) {
                $action_msg = 'Compte rejeté.'; $action_type = 'warning';
            }
            break;
        case 'activate':
            if (qexec("UPDATE users SET status='active' WHERE id=$uid", $conn)) {
                $action_msg = 'Utilisateur activé.'; $action_type = 'success';
            }
            break;
        case 'deactivate':
            if (qexec("UPDATE users SET status='pending' WHERE id=$uid", $conn)) {
                $action_msg = 'Utilisateur désactivé.'; $action_type = 'warning';
            }
            break;
        case 'delete':
            if ($is_super) {
                qexec("UPDATE documents SET uploaded_by=1 WHERE uploaded_by=$uid", $conn);
                if (qexec("DELETE FROM users WHERE id=$uid", $conn)) {
                    $action_msg = 'Utilisateur supprimé.'; $action_type = 'error';
                }
            }
            break;
        case 'change_role_dept':
            if ($is_super) {
                $set = [];
                if ($new_role) $set[] = "role='$new_role'";
                if ($new_dept) $set[] = "department_id=$new_dept";
                if ($set && qexec("UPDATE users SET ".implode(',',$set)." WHERE id=$uid", $conn)) {
                    $action_msg = 'Rôle / département mis à jour.'; $action_type = 'success';
                }
            }
            break;
        case 'reset_password':
            if ($new_pass && strlen($new_pass) >= 6) {
                $hash = password_hash($new_pass, PASSWORD_DEFAULT);
                $hash = $conn->real_escape_string($hash);
                if (qexec("UPDATE users SET password='$hash' WHERE id=$uid", $conn)) {
                    $action_msg = 'Mot de passe réinitialisé.'; $action_type = 'success';
                }
            } else {
                $action_msg = 'Mot de passe trop court (min 6 caractères).'; $action_type = 'error';
            }
            break;
    }
    done:;
}

/* ── Filters ────────────────────────────────────────────────── */
$filter_status = $_GET['status'] ?? 'all';
$filter_role   = $_GET['role']   ?? 'all';
$search        = trim($_GET['q'] ?? '');

/* ── Build WHERE ────────────────────────────────────────────── */
$where = ['u.id != ' . $me_id];
if (!$is_super)              $where[] = "u.department_id = $dept_id";
if ($filter_status !== 'all') $where[] = "u.status = '" . ($conn ? $conn->real_escape_string($filter_status) : $filter_status) . "'";
if ($filter_role   !== 'all') $where[] = "u.role = '"   . ($conn ? $conn->real_escape_string($filter_role)   : $filter_role)   . "'";
if ($search)                  $where[] = "(u.first_name LIKE '%$search%' OR u.last_name LIKE '%$search%' OR u.email LIKE '%$search%' OR u.username LIKE '%$search%')";
$where_sql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

/* ── Fetch users ────────────────────────────────────────────── */
$users = qrows("
    SELECT u.*, d.name AS dept_name, d.code AS dept_code
    FROM users u
    LEFT JOIN departments d ON u.department_id = d.id
    $where_sql
    ORDER BY
        CASE u.status WHEN 'pending' THEN 0 WHEN 'active' THEN 1 ELSE 2 END,
        u.created_at DESC
", $conn);

/* ── Counts for tabs ────────────────────────────────────────── */
$dept_filter = $is_super ? '' : "AND department_id=$dept_id";
function cnt($sql, $conn) {
    if (!$conn) return 0;
    try { $r = $conn->query($sql); if ($r) { $row = $r->fetch_row(); return (int)($row[0]??0); } }
    catch (Throwable $e) {}
    return 0;
}
$cnt_all      = cnt("SELECT COUNT(*) FROM users WHERE id!=$me_id $dept_filter", $conn);
$cnt_active   = cnt("SELECT COUNT(*) FROM users WHERE id!=$me_id AND status='active' $dept_filter", $conn);
$cnt_pending  = cnt("SELECT COUNT(*) FROM users WHERE id!=$me_id AND status='pending' $dept_filter", $conn);
$cnt_rejected = cnt("SELECT COUNT(*) FROM users WHERE id!=$me_id AND status='rejected' $dept_filter", $conn);

/* ── Departments for role/dept change modal ─────────────────── */
$departments = qrows("SELECT id, name, code FROM departments ORDER BY name", $conn);

/* ── Demo data ──────────────────────────────────────────────── */
if (empty($users)) {
    $users = [
        ['id'=>2,'first_name'=>'Nassim','last_name'=>'Ghanem','username'=>'nassim.ghanem','email'=>'nassim@at.dz','phone'=>'+213551111111','role'=>'dept_admin','status'=>'active','department_id'=>1,'dept_name'=>'Division Finances','dept_code'=>'DFC','created_at'=>date('Y-m-d',strtotime('-30 days'))],
        ['id'=>3,'first_name'=>'Samir','last_name'=>'Bouabdallah','username'=>'samir.b','email'=>'samir@at.dz','phone'=>'+213552222222','role'=>'dept_admin','status'=>'active','department_id'=>2,'dept_name'=>'Division Achats','dept_code'=>'DAMP','created_at'=>date('Y-m-d',strtotime('-25 days'))],
        ['id'=>4,'first_name'=>'Youssef','last_name'=>'Mansouri','username'=>'youssef.m','email'=>'youssef@at.dz','phone'=>'+213553333333','role'=>'dept_admin','status'=>'pending','department_id'=>3,'dept_name'=>'Direction Télécom','dept_code'=>'DOT','created_at'=>date('Y-m-d',strtotime('-5 days'))],
        ['id'=>5,'first_name'=>'Karim','last_name'=>'Bensalem','username'=>'karim.b','email'=>'karim@at.dz','phone'=>'+213554444444','role'=>'dept_admin','status'=>'pending','department_id'=>4,'dept_name'=>'Direction Juridique','dept_code'=>'DJ','created_at'=>date('Y-m-d',strtotime('-2 days'))],
        ['id'=>6,'first_name'=>'Entreprise','last_name'=>'Test','username'=>'ent_test','email'=>'ent@test.dz','phone'=>'+213600000001','role'=>'enterprise','status'=>'active','department_id'=>null,'dept_name'=>'—','dept_code'=>'—','created_at'=>date('Y-m-d',strtotime('-10 days'))],
    ];
    $cnt_all=5; $cnt_active=3; $cnt_pending=2; $cnt_rejected=0;
}

$user_name = $_SESSION['user_name'] ?? 'Admin';

/* ── Helpers ────────────────────────────────────────────────── */
function status_badge(string $s): string {
    return match($s) {
        'active'   => '<span class="badge b-teal"><i class="fa-solid fa-circle" style="font-size:6px"></i> Actif</span>',
        'pending'  => '<span class="badge b-amber"><i class="fa-solid fa-clock" style="font-size:9px"></i> En attente</span>',
        'rejected' => '<span class="badge b-red"><i class="fa-solid fa-xmark" style="font-size:9px"></i> Rejeté</span>',
        default    => '<span class="badge b-gray">—</span>',
    };
}
function role_badge(string $r): string {
    return match($r) {
        'super_admin' => '<span class="badge b-purple">Super Admin</span>',
        'dept_admin'  => '<span class="badge b-blue">Admin Dept.</span>',
        'enterprise'  => '<span class="badge b-teal">Entreprise</span>',
        default       => '<span class="badge b-gray">' . htmlspecialchars($r) . '</span>',
    };
}
function avatar_letter(array $u): string {
    return strtoupper(substr($u['first_name'], 0, 1) . substr($u['last_name'], 0, 1));
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Gestion Utilisateurs — AT-AMS</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
:root{
  --bg:#0b0f0e;--surf:#111615;--surf2:#161d1b;--surf3:#1c2422;
  --border:rgba(255,255,255,.07);--border2:rgba(0,191,165,.2);
  --teal:#00BFA5;--teal-d:rgba(0,191,165,.12);--teal-g:rgba(0,191,165,.22);
  --amber:#F59E0B;--red:#EF4444;--blue:#3B82F6;--purple:#8B5CF6;--green:#22C55E;
  --text:#E8EDEC;--muted:#6B7A78;--dim:#9AAFAD;
  --sw:256px;--th:64px;--rad:14px;--rad-s:8px;
  --fh:'Syne',sans-serif;--fb:'DM Sans',sans-serif;
  --tr:.2s cubic-bezier(.4,0,.2,1);
}
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
body{font-family:var(--fb);background:var(--bg);color:var(--text);min-height:100vh;overflow-x:hidden}
a{color:inherit;text-decoration:none}
button,input,select{font-family:inherit}
button{cursor:pointer;border:none;background:none}

/* ══ LAYOUT ══ */
.layout{display:flex;min-height:100vh}

/* ══ SIDEBAR ══ */
.sidebar{width:var(--sw);background:var(--surf);border-right:1px solid var(--border);display:flex;flex-direction:column;position:fixed;top:0;left:0;bottom:0;z-index:100;transition:transform var(--tr);overflow-y:auto;overflow-x:hidden}
.sidebar::-webkit-scrollbar{width:3px}
.sidebar::-webkit-scrollbar-thumb{background:var(--border);border-radius:3px}
.s-logo{display:flex;align-items:center;gap:12px;padding:22px 18px 18px;border-bottom:1px solid var(--border)}
.s-logo-icon{width:38px;height:38px;background:linear-gradient(135deg,var(--teal),#007A6A);border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:15px;color:#000;flex-shrink:0;box-shadow:0 0 16px var(--teal-g)}
.s-logo-txt strong{font-family:var(--fh);font-size:14px;font-weight:800;letter-spacing:-.3px;display:block}
.s-logo-txt span{font-size:10px;color:var(--teal);text-transform:uppercase;letter-spacing:.15em;font-weight:600}
.s-sec{padding:18px 10px 6px}
.s-lbl{font-size:10px;font-weight:700;letter-spacing:.18em;text-transform:uppercase;color:var(--muted);padding:0 8px 8px}
.nav-item{display:flex;align-items:center;gap:11px;padding:9px 10px;border-radius:var(--rad-s);font-size:13.5px;font-weight:500;color:var(--dim);transition:all var(--tr);position:relative;margin-bottom:2px}
.nav-item:hover{background:var(--teal-d);color:var(--text)}
.nav-item.active{background:var(--teal-d);color:var(--teal);font-weight:600}
.nav-item.active::before{content:'';position:absolute;left:0;top:50%;transform:translateY(-50%);width:3px;height:55%;background:var(--teal);border-radius:0 3px 3px 0}
.nav-item i{width:17px;text-align:center;font-size:14px}
.nb{margin-left:auto;background:var(--amber);color:#000;font-size:10px;font-weight:700;padding:2px 6px;border-radius:20px}
.s-foot{margin-top:auto;padding:14px 10px;border-top:1px solid var(--border)}
.s-user{display:flex;align-items:center;gap:9px;padding:9px 10px;background:var(--surf2);border-radius:var(--rad-s)}
.s-avatar{width:32px;height:32px;background:linear-gradient(135deg,#00BFA5,#007A6A);border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:700;color:#000;flex-shrink:0}
.s-uinfo{flex:1;min-width:0}
.s-uinfo strong{font-size:12px;font-weight:600;display:block;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.s-uinfo span{font-size:10px;color:var(--teal)}

/* ══ MAIN ══ */
.main{margin-left:var(--sw);flex:1;display:flex;flex-direction:column}

/* ══ TOPBAR ══ */
.topbar{height:var(--th);background:var(--surf);border-bottom:1px solid var(--border);display:flex;align-items:center;padding:0 26px;gap:14px;position:sticky;top:0;z-index:50}
.tb-toggle{display:none;color:var(--muted);font-size:19px;padding:6px;border-radius:6px}
.tb-search{flex:1;max-width:360px;position:relative}
.tb-search input{width:100%;background:var(--surf2);border:1px solid var(--border);color:var(--text);font-size:13px;padding:8px 14px 8px 36px;border-radius:9px;outline:none;transition:border var(--tr)}
.tb-search input:focus{border-color:var(--border2)}
.tb-search input::placeholder{color:var(--muted)}
.tb-search i{position:absolute;left:11px;top:50%;transform:translateY(-50%);color:var(--muted);font-size:13px}
.tb-actions{margin-left:auto;display:flex;align-items:center;gap:9px}
.tb-btn{width:36px;height:36px;background:var(--surf2);border:1px solid var(--border);border-radius:9px;display:flex;align-items:center;justify-content:center;color:var(--muted);font-size:14px;transition:all var(--tr)}
.tb-btn:hover{border-color:var(--border2);color:var(--teal)}

/* ══ CONTENT ══ */
.content{padding:26px;flex:1}
.pg-head{display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:22px;gap:14px;flex-wrap:wrap}
.pg-title{font-family:var(--fh);font-size:24px;font-weight:800;letter-spacing:-.5px}
.pg-sub{font-size:13px;color:var(--muted);margin-top:3px}

/* ══ BUTTONS ══ */
.btn{display:inline-flex;align-items:center;gap:7px;padding:9px 17px;border-radius:var(--rad-s);font-size:13px;font-weight:600;transition:all var(--tr);white-space:nowrap;cursor:pointer}
.btn-primary{background:var(--teal);color:#000;box-shadow:0 4px 14px var(--teal-g);border:none}
.btn-primary:hover{background:#00e6c4;transform:translateY(-1px)}
.btn-outline{background:transparent;color:var(--text);border:1px solid var(--border)}
.btn-outline:hover{border-color:var(--border2);color:var(--teal)}
.btn-danger{background:rgba(239,68,68,.12);color:var(--red);border:1px solid rgba(239,68,68,.2)}
.btn-danger:hover{background:rgba(239,68,68,.22)}
.btn-warn{background:rgba(245,158,11,.12);color:var(--amber);border:1px solid rgba(245,158,11,.2)}
.btn-warn:hover{background:rgba(245,158,11,.22)}
.btn-success{background:rgba(0,191,165,.12);color:var(--teal);border:1px solid rgba(0,191,165,.2)}
.btn-success:hover{background:rgba(0,191,165,.22)}
.btn-sm{padding:5px 11px;font-size:12px}
.btn-xs{padding:4px 9px;font-size:11px}

/* ══ ALERT ══ */
.alert{padding:12px 16px;border-radius:var(--rad-s);font-size:13px;margin-bottom:18px;display:flex;align-items:center;gap:10px}
.alert-success{background:rgba(0,191,165,.12);border:1px solid rgba(0,191,165,.25);color:var(--teal)}
.alert-warning{background:rgba(245,158,11,.12);border:1px solid rgba(245,158,11,.25);color:var(--amber)}
.alert-error{background:rgba(239,68,68,.12);border:1px solid rgba(239,68,68,.25);color:var(--red)}

/* ══ STATS ROW ══ */
.stats-row{display:flex;gap:12px;margin-bottom:22px;flex-wrap:wrap}
.stat-pill{display:flex;align-items:center;gap:10px;background:var(--surf);border:1px solid var(--border);border-radius:var(--rad-s);padding:12px 16px;min-width:120px;flex:1;transition:all var(--tr);cursor:pointer;text-decoration:none}
.stat-pill:hover,.stat-pill.active-filter{border-color:var(--border2);background:var(--teal-d)}
.stat-pill.active-filter .sp-val{color:var(--teal)}
.sp-val{font-family:var(--fh);font-size:22px;font-weight:800;line-height:1}
.sp-lbl{font-size:11px;color:var(--muted);margin-top:2px}
.sp-icon{width:34px;height:34px;border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:14px;flex-shrink:0}

/* ══ FILTER BAR ══ */
.filter-bar{display:flex;gap:10px;margin-bottom:18px;flex-wrap:wrap;align-items:center}
.filter-select{background:var(--surf2);border:1px solid var(--border);color:var(--text);font-size:13px;padding:8px 12px;border-radius:var(--rad-s);outline:none;cursor:pointer;transition:border var(--tr)}
.filter-select:focus{border-color:var(--border2)}
.filter-select option{background:var(--surf2)}
.search-input{background:var(--surf2);border:1px solid var(--border);color:var(--text);font-size:13px;padding:8px 14px 8px 36px;border-radius:var(--rad-s);outline:none;transition:border var(--tr);width:220px}
.search-input:focus{border-color:var(--border2)}
.search-wrap{position:relative}
.search-wrap i{position:absolute;left:11px;top:50%;transform:translateY(-50%);color:var(--muted);font-size:13px}

/* ══ TABLE ══ */
.card{background:var(--surf);border:1px solid var(--border);border-radius:var(--rad);overflow:hidden}
.card-hd{display:flex;align-items:center;justify-content:space-between;padding:15px 20px 12px;border-bottom:1px solid var(--border)}
.card-title{font-family:var(--fh);font-size:14px;font-weight:700}
.users-table{width:100%;border-collapse:collapse}
.users-table th{font-size:10px;font-weight:700;letter-spacing:.1em;text-transform:uppercase;color:var(--muted);padding:10px 16px;text-align:left;border-bottom:1px solid var(--border);white-space:nowrap}
.users-table td{padding:12px 16px;border-bottom:1px solid rgba(255,255,255,.04);font-size:13px;vertical-align:middle}
.users-table tr:last-child td{border-bottom:none}
.users-table tr:hover td{background:rgba(255,255,255,.02)}

/* user cell */
.u-cell{display:flex;align-items:center;gap:10px}
.u-av{width:34px;height:34px;border-radius:50%;background:linear-gradient(135deg,var(--teal),#007A6A);display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:700;color:#000;flex-shrink:0}
.u-av.av-pending{background:linear-gradient(135deg,var(--amber),#B45309)}
.u-av.av-rejected{background:linear-gradient(135deg,var(--red),#991B1B)}
.u-name{font-weight:600;font-size:13px}
.u-email{font-size:11px;color:var(--muted)}

/* dept tag */
.dept-tag{font-size:11px;font-weight:600;padding:3px 8px;background:var(--surf2);border-radius:5px;white-space:nowrap}

/* badges */
.badge{display:inline-flex;align-items:center;gap:4px;font-size:10.5px;font-weight:600;padding:3px 9px;border-radius:20px;white-space:nowrap}
.b-teal  {background:rgba(0,191,165,.15);color:var(--teal)}
.b-amber {background:rgba(245,158,11,.15);color:var(--amber)}
.b-red   {background:rgba(239,68,68,.15);color:var(--red)}
.b-blue  {background:rgba(59,130,246,.15);color:var(--blue)}
.b-purple{background:rgba(139,92,246,.15);color:var(--purple)}
.b-gray  {background:rgba(107,114,128,.15);color:#9CA3AF}

/* actions */
.action-group{display:flex;gap:5px;flex-wrap:nowrap}

/* ══ MODAL ══ */
.modal-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.7);z-index:200;align-items:center;justify-content:center;padding:20px}
.modal-overlay.open{display:flex}
.modal{background:var(--surf);border:1px solid var(--border2);border-radius:var(--rad);width:100%;max-width:440px;overflow:hidden;animation:slideUp .25s ease}
@keyframes slideUp{from{opacity:0;transform:translateY(20px)}to{opacity:1;transform:translateY(0)}}
.modal-hd{display:flex;align-items:center;justify-content:space-between;padding:18px 20px;border-bottom:1px solid var(--border)}
.modal-title{font-family:var(--fh);font-size:15px;font-weight:700}
.modal-close{width:28px;height:28px;border-radius:6px;display:flex;align-items:center;justify-content:center;color:var(--muted);transition:all var(--tr)}
.modal-close:hover{background:var(--surf2);color:var(--text)}
.modal-body{padding:20px}
.modal-footer{padding:14px 20px;border-top:1px solid var(--border);display:flex;gap:10px;justify-content:flex-end}

/* form */
.form-group{margin-bottom:16px}
.form-label{display:block;font-size:11px;font-weight:700;letter-spacing:.1em;text-transform:uppercase;color:var(--muted);margin-bottom:7px}
.form-input,.form-select{width:100%;background:var(--surf2);border:1px solid var(--border);color:var(--text);font-size:13px;padding:9px 13px;border-radius:var(--rad-s);outline:none;transition:border var(--tr)}
.form-input:focus,.form-select:focus{border-color:var(--border2)}
.form-input::placeholder{color:var(--muted)}
.form-select option{background:var(--surf2)}
.user-preview{display:flex;align-items:center;gap:12px;padding:12px;background:var(--surf2);border-radius:var(--rad-s);margin-bottom:18px}
.up-av{width:40px;height:40px;border-radius:50%;background:linear-gradient(135deg,var(--teal),#007A6A);display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:700;color:#000}
.up-name{font-weight:600;font-size:13px}
.up-email{font-size:11px;color:var(--muted)}

/* ══ EMPTY ══ */
.empty{text-align:center;padding:40px 20px;color:var(--muted);font-size:13px}
.empty i{font-size:32px;margin-bottom:10px;display:block}

/* ══ PENDING HIGHLIGHT ══ */
.row-pending td{background:rgba(245,158,11,.03)!important}

/* ══ OVERLAY ══ */
.mob-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.6);z-index:99}

/* ══ RESPONSIVE ══ */
@media(max-width:900px){
  .sidebar{transform:translateX(-100%)}
  .sidebar.open{transform:translateX(0)}
  .mob-overlay.open{display:block}
  .main{margin-left:0}
  .tb-toggle{display:flex!important}
}
@media(max-width:700px){
  .content{padding:14px}
  .topbar{padding:0 14px}
  .users-table th:nth-child(4),.users-table td:nth-child(4),
  .users-table th:nth-child(5),.users-table td:nth-child(5){display:none}
}
@media(max-width:500px){
  .stats-row{gap:8px}
  .stat-pill{min-width:80px}
  .filter-bar{flex-direction:column;align-items:stretch}
  .search-input{width:100%}
}

@keyframes fadeUp{from{opacity:0;transform:translateY(10px)}to{opacity:1;transform:translateY(0)}}
.a1{animation:fadeUp .35s ease both}
.a2{animation:fadeUp .35s .06s ease both}
.a3{animation:fadeUp .35s .12s ease both}
::-webkit-scrollbar{width:5px;height:5px}
::-webkit-scrollbar-thumb{background:rgba(255,255,255,.1);border-radius:5px}
</style>
</head>
<body>
<div class="layout">

<!-- ══ SIDEBAR ═══════════════════════════════════════════════ -->
<aside class="sidebar" id="sidebar">
  <div class="s-logo">
    <div class="s-logo-icon"><i class="fa-solid fa-box-archive"></i></div>
    <div class="s-logo-txt">
      <strong>AT-AMS</strong>
      <span><?= $is_super ? 'Super Admin' : 'Admin Dept.' ?></span>
    </div>
  </div>
  <div class="s-sec">
    <div class="s-lbl">Principal</div>
    <a href="dashboard.php" class="nav-item">
      <i class="fa-solid fa-grid-2"></i> Tableau de bord
    </a>
    <a href="../documents/list.php" class="nav-item">
      <i class="fa-solid fa-file-lines"></i> Documents
    </a>
    <a href="../documents/upload.php" class="nav-item">
      <i class="fa-solid fa-cloud-arrow-up"></i> Uploader
    </a>
  </div>
  <div class="s-sec">
    <div class="s-lbl">Administration</div>
    <a href="users.php" class="nav-item active">
      <i class="fa-solid fa-users"></i> Utilisateurs
      <?php if($cnt_pending > 0): ?>
      <span class="nb"><?= $cnt_pending ?></span>
      <?php endif; ?>
    </a>
    <a href="enterprises-pending.php" class="nav-item">
      <i class="fa-solid fa-building"></i> Entreprises
    </a>
    <?php if($is_super): ?>
    <a href="departments.php" class="nav-item">
      <i class="fa-solid fa-sitemap"></i> Départements
    </a>
    <a href="settings.php" class="nav-item">
      <i class="fa-solid fa-gear"></i> Paramètres
    </a>
    <?php endif; ?>
  </div>
  <div class="s-foot">
    <div class="s-user">
      <div class="s-avatar"><?= strtoupper(substr($user_name,0,1)) ?></div>
      <div class="s-uinfo">
        <strong><?= htmlspecialchars($user_name) ?></strong>
        <span><?= $is_super ? 'Super Admin' : 'Admin Dept.' ?></span>
      </div>
    </div>
    <a href="../auth/login.php?logout=1" class="nav-item" style="margin-top:6px;color:var(--red)">
      <i class="fa-solid fa-right-from-bracket"></i> Déconnexion
    </a>
  </div>
</aside>

<div class="mob-overlay" id="mob-overlay" onclick="closeSidebar()"></div>

<!-- ══ MAIN ══════════════════════════════════════════════════ -->
<main class="main">
  <header class="topbar">
    <button class="tb-toggle" onclick="toggleSidebar()">
      <i class="fa-solid fa-bars"></i>
    </button>
    <div class="tb-search">
      <i class="fa-solid fa-magnifying-glass"></i>
      <input type="text" id="live-search" placeholder="Rechercher un utilisateur…"
             value="<?= htmlspecialchars($search) ?>">
    </div>
    <div class="tb-actions">
      <a href="dashboard.php" class="tb-btn" title="Dashboard">
        <i class="fa-solid fa-arrow-left"></i>
      </a>
      <a href="users.php" class="tb-btn" title="Rafraîchir">
        <i class="fa-solid fa-rotate-right"></i>
      </a>
    </div>
  </header>

  <div class="content">

    <!-- Header -->
    <div class="pg-head a1">
      <div>
        <h1 class="pg-title">Gestion des Utilisateurs</h1>
        <p class="pg-sub">
          <?= $is_super ? 'Tous les comptes du système' : 'Comptes de votre département' ?>
          — <?= $cnt_all ?> utilisateur<?= $cnt_all > 1 ? 's' : '' ?>
        </p>
      </div>
    </div>

    <!-- Alert -->
    <?php if($action_msg): ?>
    <div class="alert alert-<?= $action_type === 'success' ? 'success' : ($action_type === 'warning' ? 'warning' : 'error') ?> a1" id="action-alert">
      <i class="fa-solid fa-<?= $action_type === 'success' ? 'circle-check' : ($action_type === 'warning' ? 'triangle-exclamation' : 'circle-xmark') ?>"></i>
      <?= htmlspecialchars($action_msg) ?>
    </div>
    <?php endif; ?>

    <!-- Stats pills -->
    <div class="stats-row a2">
      <a href="users.php?status=all" class="stat-pill <?= $filter_status==='all'?'active-filter':'' ?>">
        <div class="sp-icon" style="background:var(--teal-d);color:var(--teal)"><i class="fa-solid fa-users"></i></div>
        <div><div class="sp-val"><?= $cnt_all ?></div><div class="sp-lbl">Total</div></div>
      </a>
      <a href="users.php?status=active" class="stat-pill <?= $filter_status==='active'?'active-filter':'' ?>">
        <div class="sp-icon" style="background:rgba(0,191,165,.12);color:var(--teal)"><i class="fa-solid fa-circle-check"></i></div>
        <div><div class="sp-val"><?= $cnt_active ?></div><div class="sp-lbl">Actifs</div></div>
      </a>
      <a href="users.php?status=pending" class="stat-pill <?= $filter_status==='pending'?'active-filter':'' ?>">
        <div class="sp-icon" style="background:rgba(245,158,11,.12);color:var(--amber)"><i class="fa-solid fa-clock"></i></div>
        <div><div class="sp-val"><?= $cnt_pending ?></div><div class="sp-lbl">En attente</div></div>
      </a>
      <a href="users.php?status=rejected" class="stat-pill <?= $filter_status==='rejected'?'active-filter':'' ?>">
        <div class="sp-icon" style="background:rgba(239,68,68,.12);color:var(--red)"><i class="fa-solid fa-xmark-circle"></i></div>
        <div><div class="sp-val"><?= $cnt_rejected ?></div><div class="sp-lbl">Rejetés</div></div>
      </a>
    </div>

    <!-- Filter bar -->
    <div class="filter-bar a2">
      <div class="search-wrap">
        <i class="fa-solid fa-magnifying-glass"></i>
        <input type="text" class="search-input" id="table-search"
               placeholder="Nom, email, username…"
               value="<?= htmlspecialchars($search) ?>"
               onkeydown="if(event.key==='Enter'){applyFilter()}">
      </div>
      <?php if($is_super): ?>
      <select class="filter-select" id="role-filter" onchange="applyFilter()">
        <option value="all" <?= $filter_role==='all'?'selected':'' ?>>Tous les rôles</option>
        <option value="super_admin"  <?= $filter_role==='super_admin'?'selected':'' ?>>Super Admin</option>
        <option value="dept_admin"   <?= $filter_role==='dept_admin'?'selected':'' ?>>Admin Département</option>
        <option value="enterprise"   <?= $filter_role==='enterprise'?'selected':'' ?>>Entreprise</option>
      </select>
      <?php endif; ?>
      <select class="filter-select" id="status-filter" onchange="applyFilter()">
        <option value="all"      <?= $filter_status==='all'?'selected':'' ?>>Tous les statuts</option>
        <option value="active"   <?= $filter_status==='active'?'selected':'' ?>>Actifs</option>
        <option value="pending"  <?= $filter_status==='pending'?'selected':'' ?>>En attente</option>
        <option value="rejected" <?= $filter_status==='rejected'?'selected':'' ?>>Rejetés</option>
      </select>
      <a href="users.php" class="btn btn-outline btn-sm">
        <i class="fa-solid fa-rotate-left"></i> Réinitialiser
      </a>
    </div>

    <!-- Table -->
    <div class="card a3">
      <div class="card-hd">
        <div class="card-title">
          <?php if($filter_status === 'pending'): ?>
          <i class="fa-solid fa-clock" style="color:var(--amber);margin-right:6px"></i>
          Comptes en attente d'approbation
          <?php else: ?>
          Liste des utilisateurs
          <?php endif; ?>
        </div>
        <span style="font-size:12px;color:var(--muted)"><?= count($users) ?> résultat<?= count($users)>1?'s':'' ?></span>
      </div>
      <div style="overflow-x:auto">
        <?php if(empty($users)): ?>
        <div class="empty">
          <i class="fa-regular fa-user-slash"></i>
          Aucun utilisateur trouvé.
        </div>
        <?php else: ?>
        <table class="users-table" id="users-table">
          <thead>
            <tr>
              <th>Utilisateur</th>
              <th>Rôle</th>
              <th>Département</th>
              <th>Statut</th>
              <th>Inscrit le</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach($users as $u): ?>
            <tr class="<?= $u['status']==='pending' ? 'row-pending' : '' ?>" id="row-<?= $u['id'] ?>">
              <td>
                <div class="u-cell">
                  <div class="u-av <?= $u['status']==='pending'?'av-pending':($u['status']==='rejected'?'av-rejected':'') ?>">
                    <?= avatar_letter($u) ?>
                  </div>
                  <div>
                    <div class="u-name"><?= htmlspecialchars($u['first_name'].' '.$u['last_name']) ?></div>
                    <div class="u-email"><?= htmlspecialchars($u['email']) ?></div>
                  </div>
                </div>
              </td>
              <td><?= role_badge($u['role']) ?></td>
              <td>
                <?php if($u['dept_name'] && $u['dept_name'] !== '—'): ?>
                <span class="dept-tag"><?= htmlspecialchars($u['dept_code']) ?></span>
                <span style="font-size:12px;color:var(--dim);margin-left:5px"><?= htmlspecialchars($u['dept_name']) ?></span>
                <?php else: ?>
                <span style="color:var(--muted);font-size:12px">—</span>
                <?php endif; ?>
              </td>
              <td><?= status_badge($u['status']) ?></td>
              <td style="font-size:12px;color:var(--muted)">
                <?= date('d/m/Y', strtotime($u['created_at'])) ?>
              </td>
              <td>
                <div class="action-group">
                  <?php if($u['status'] === 'pending'): ?>
                    <button class="btn btn-success btn-xs"
                            onclick="confirmAction('approve', <?= $u['id'] ?>, '<?= htmlspecialchars($u['first_name'].' '.$u['last_name'], ENT_QUOTES) ?>')">
                      <i class="fa-solid fa-check"></i> Approuver
                    </button>
                    <button class="btn btn-danger btn-xs"
                            onclick="confirmAction('reject', <?= $u['id'] ?>, '<?= htmlspecialchars($u['first_name'].' '.$u['last_name'], ENT_QUOTES) ?>')">
                      <i class="fa-solid fa-xmark"></i>
                    </button>
                  <?php elseif($u['status'] === 'active'): ?>
                    <button class="btn btn-warn btn-xs"
                            onclick="confirmAction('deactivate', <?= $u['id'] ?>, '<?= htmlspecialchars($u['first_name'].' '.$u['last_name'], ENT_QUOTES) ?>')">
                      <i class="fa-solid fa-ban"></i>
                    </button>
                  <?php else: ?>
                    <button class="btn btn-success btn-xs"
                            onclick="confirmAction('activate', <?= $u['id'] ?>, '<?= htmlspecialchars($u['first_name'].' '.$u['last_name'], ENT_QUOTES) ?>')">
                      <i class="fa-solid fa-rotate-right"></i>
                    </button>
                  <?php endif; ?>

                  <?php if($is_super): ?>
                  <button class="btn btn-outline btn-xs"
                          onclick="openRoleModal(<?= $u['id'] ?>, '<?= htmlspecialchars($u['first_name'].' '.$u['last_name'], ENT_QUOTES) ?>', '<?= $u['email'] ?>', '<?= $u['role'] ?>', <?= $u['department_id'] ?? 0 ?>)">
                    <i class="fa-solid fa-user-pen"></i>
                  </button>
                  <button class="btn btn-outline btn-xs"
                          onclick="openPasswordModal(<?= $u['id'] ?>, '<?= htmlspecialchars($u['first_name'].' '.$u['last_name'], ENT_QUOTES) ?>', '<?= $u['email'] ?>')">
                    <i class="fa-solid fa-key"></i>
                  </button>
                  <button class="btn btn-danger btn-xs"
                          onclick="confirmAction('delete', <?= $u['id'] ?>, '<?= htmlspecialchars($u['first_name'].' '.$u['last_name'], ENT_QUOTES) ?>')">
                    <i class="fa-solid fa-trash"></i>
                  </button>
                  <?php endif; ?>
                </div>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
        <?php endif; ?>
      </div>
    </div>

  </div><!-- /content -->
</main>
</div><!-- /layout -->

<!-- ══ CONFIRM ACTION MODAL ══════════════════════════════════ -->
<div class="modal-overlay" id="confirm-modal">
  <div class="modal">
    <div class="modal-hd">
      <span class="modal-title" id="confirm-title">Confirmer l'action</span>
      <button class="modal-close" onclick="closeModal('confirm-modal')">
        <i class="fa-solid fa-xmark"></i>
      </button>
    </div>
    <form method="POST">
      <div class="modal-body">
        <div class="user-preview">
          <div class="up-av" id="confirm-av">US</div>
          <div>
            <div class="up-name" id="confirm-name">—</div>
            <div class="up-email" id="confirm-email">—</div>
          </div>
        </div>
        <p style="font-size:13px;color:var(--dim)" id="confirm-text">Êtes-vous sûr ?</p>
        <input type="hidden" name="action"  id="confirm-action-input">
        <input type="hidden" name="user_id" id="confirm-uid-input">
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline btn-sm" onclick="closeModal('confirm-modal')">Annuler</button>
        <button type="submit" class="btn btn-sm" id="confirm-submit-btn">Confirmer</button>
      </div>
    </form>
  </div>
</div>

<!-- ══ CHANGE ROLE / DEPT MODAL ══════════════════════════════ -->
<div class="modal-overlay" id="role-modal">
  <div class="modal">
    <div class="modal-hd">
      <span class="modal-title">Modifier rôle / département</span>
      <button class="modal-close" onclick="closeModal('role-modal')">
        <i class="fa-solid fa-xmark"></i>
      </button>
    </div>
    <form method="POST">
      <input type="hidden" name="action" value="change_role_dept">
      <input type="hidden" name="user_id" id="role-uid">
      <div class="modal-body">
        <div class="user-preview">
          <div class="up-av" id="role-av">US</div>
          <div>
            <div class="up-name" id="role-name">—</div>
            <div class="up-email" id="role-email">—</div>
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Rôle</label>
          <select name="new_role" id="role-select" class="form-select">
            <option value="">— Ne pas changer —</option>
            <option value="super_admin">Super Admin</option>
            <option value="dept_admin">Admin Département</option>
            <option value="enterprise">Entreprise</option>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Département</label>
          <select name="new_dept" class="form-select" id="dept-select">
            <option value="0">— Ne pas changer —</option>
            <?php foreach($departments as $d): ?>
            <option value="<?= $d['id'] ?>"><?= htmlspecialchars($d['code'].' — '.$d['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline btn-sm" onclick="closeModal('role-modal')">Annuler</button>
        <button type="submit" class="btn btn-primary btn-sm">Enregistrer</button>
      </div>
    </form>
  </div>
</div>

<!-- ══ RESET PASSWORD MODAL ═══════════════════════════════════ -->
<div class="modal-overlay" id="password-modal">
  <div class="modal">
    <div class="modal-hd">
      <span class="modal-title">Réinitialiser le mot de passe</span>
      <button class="modal-close" onclick="closeModal('password-modal')">
        <i class="fa-solid fa-xmark"></i>
      </button>
    </div>
    <form method="POST">
      <input type="hidden" name="action" value="reset_password">
      <input type="hidden" name="user_id" id="pass-uid">
      <div class="modal-body">
        <div class="user-preview">
          <div class="up-av" id="pass-av">US</div>
          <div>
            <div class="up-name" id="pass-name">—</div>
            <div class="up-email" id="pass-email">—</div>
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Nouveau mot de passe</label>
          <input type="password" name="new_password" class="form-input"
                 placeholder="Minimum 6 caractères" minlength="6" required>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline btn-sm" onclick="closeModal('password-modal')">Annuler</button>
        <button type="submit" class="btn btn-primary btn-sm">
          <i class="fa-solid fa-key"></i> Réinitialiser
        </button>
      </div>
    </form>
  </div>
</div>

<script>
/* ── Sidebar ── */
function toggleSidebar(){
  document.getElementById('sidebar').classList.toggle('open');
  document.getElementById('mob-overlay').classList.toggle('open');
}
function closeSidebar(){
  document.getElementById('sidebar').classList.remove('open');
  document.getElementById('mob-overlay').classList.remove('open');
}

/* ── Modals ── */
function closeModal(id){ document.getElementById(id).classList.remove('open') }
function openModal(id) { document.getElementById(id).classList.add('open')    }

/* ── Confirm action modal ── */
const confirmMessages = {
  approve:    { title:'Approuver le compte',     text:'Ce compte sera activé et l\'utilisateur pourra se connecter.',  btn:'btn-success', label:'Approuver' },
  reject:     { title:'Rejeter le compte',       text:'Ce compte sera marqué comme rejeté.',                           btn:'btn-danger',  label:'Rejeter'   },
  activate:   { title:'Activer le compte',       text:'Ce compte sera réactivé.',                                       btn:'btn-success', label:'Activer'   },
  deactivate: { title:'Désactiver le compte',    text:'L\'utilisateur ne pourra plus se connecter.',                   btn:'btn-warn',    label:'Désactiver'},
  delete:     { title:'Supprimer définitivement',text:'⚠️ Cette action est irréversible. Tous ses documents seront conservés mais réassignés.', btn:'btn-danger', label:'Supprimer' },
};

function confirmAction(action, uid, name) {
  const cfg = confirmMessages[action];
  document.getElementById('confirm-title').textContent    = cfg.title;
  document.getElementById('confirm-text').textContent     = cfg.text;
  document.getElementById('confirm-name').textContent     = name;
  document.getElementById('confirm-av').textContent       = name.split(' ').map(w=>w[0]).join('').toUpperCase().slice(0,2);
  document.getElementById('confirm-action-input').value   = action;
  document.getElementById('confirm-uid-input').value      = uid;
  const btn = document.getElementById('confirm-submit-btn');
  btn.className = 'btn btn-sm ' + cfg.btn;
  btn.textContent = cfg.label;
  openModal('confirm-modal');
}

/* ── Role modal ── */
function openRoleModal(uid, name, email, role, deptId) {
  document.getElementById('role-uid').value    = uid;
  document.getElementById('role-name').textContent  = name;
  document.getElementById('role-email').textContent = email;
  document.getElementById('role-av').textContent    = name.split(' ').map(w=>w[0]).join('').toUpperCase().slice(0,2);
  document.getElementById('role-select').value  = role;
  document.getElementById('dept-select').value  = deptId || '0';
  openModal('role-modal');
}

/* ── Password modal ── */
function openPasswordModal(uid, name, email) {
  document.getElementById('pass-uid').value    = uid;
  document.getElementById('pass-name').textContent  = name;
  document.getElementById('pass-email').textContent = email;
  document.getElementById('pass-av').textContent    = name.split(' ').map(w=>w[0]).join('').toUpperCase().slice(0,2);
  openModal('password-modal');
}

/* ── Filter ── */
function applyFilter() {
  const q      = document.getElementById('table-search').value;
  const role   = document.getElementById('role-filter')?.value   || 'all';
  const status = document.getElementById('status-filter')?.value || 'all';
  const params = new URLSearchParams();
  if (q)            params.set('q',      q);
  if (role!=='all') params.set('role',   role);
  if (status!=='all') params.set('status', status);
  window.location = 'users.php?' + params.toString();
}

/* ── Live search in table (client-side) ── */
document.getElementById('table-search')?.addEventListener('input', function() {
  const q = this.value.toLowerCase();
  document.querySelectorAll('#users-table tbody tr').forEach(row => {
    row.style.display = row.textContent.toLowerCase().includes(q) ? '' : 'none';
  });
});

/* ── Auto-hide alert ── */
const alert = document.getElementById('action-alert');
if (alert) setTimeout(() => { alert.style.transition='opacity .5s'; alert.style.opacity='0'; setTimeout(()=>alert.remove(),500); }, 3000);

/* ── Close modal on overlay click ── */
document.querySelectorAll('.modal-overlay').forEach(overlay => {
  overlay.addEventListener('click', e => { if(e.target===overlay) overlay.classList.remove('open'); });
});
</script>
</body>
</html>