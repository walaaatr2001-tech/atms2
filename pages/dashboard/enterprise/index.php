<?php
/**
 * AT-AMS — pages/dashboard/enterprise/index.php
 * Dashboard for enterprise users.
 * Shows: submitted docs + status, enterprise info, dossier tracker, notifications.
 * Self-contained — no broken includes.
 * Drop at: C:\xampp\htdocs\AT-AMS\pages\dashboard\enterprise\index.php
 */

if (session_status() === PHP_SESSION_NONE) session_start();

/* ── Auth guard ─────────────────────────────────────────────── */
if (!isset($_SESSION['user_id'])) {
    header('Location: ../../auth/login.php'); exit;
}
if ($_SESSION['user_role'] !== 'enterprise') {
    header('Location: ../index.php'); exit;
}

/* ── Load DB safely ─────────────────────────────────────────── */
$conn  = null;
$db_ok = false;
$cfg   = __DIR__ . '/../../../config/config.php';
if (file_exists($cfg)) {
    try {
        require_once $cfg;
        if (function_exists('getDB')) { $conn = getDB(); $db_ok = true; }
    } catch (Throwable $e) {}
}

/* ── Session vars ───────────────────────────────────────────── */
$user_id   = (int)$_SESSION['user_id'];
$user_name = $_SESSION['user_name'] ?? 'Utilisateur';
$first     = explode(' ', $user_name)[0];

/* ── Safe helpers ───────────────────────────────────────────── */
function qone(string $sql, $conn): ?array {
    if (!$conn) return null;
    try { $r = $conn->query($sql); if ($r) return $r->fetch_assoc(); }
    catch (Throwable $e) {}
    return null;
}
function qcount(string $sql, $conn, int $fb = 0): int {
    if (!$conn) return $fb;
    try { $r = $conn->query($sql); if ($r) { $row = $r->fetch_row(); return (int)($row[0] ?? 0); } }
    catch (Throwable $e) {}
    return $fb;
}
function qrows(string $sql, $conn): array {
    if (!$conn) return [];
    try { $r = $conn->query($sql); if ($r) return $r->fetch_all(MYSQLI_ASSOC); }
    catch (Throwable $e) {}
    return [];
}

/* ── Enterprise info ────────────────────────────────────────── */
$ent = qone("
    SELECT e.*, w.name_fr AS wilaya_name, c.name_fr AS city_name
    FROM enterprises e
    LEFT JOIN wilayas w ON e.wilaya_id = w.id
    LEFT JOIN cities  c ON e.city_id   = c.id
    WHERE e.user_id = $user_id
    LIMIT 1
", $conn);

/* Demo enterprise if DB empty */
if (!$ent) {
    $ent = [
        'nif'         => '1976 43 03000 28 36',
        'rc'          => '43/00-1650775/11',
        'rib'         => '005 00321 400 212 732 0 90',
        'bank_name'   => 'BDL',
        'wilaya_name' => 'Mila',
        'city_name'   => 'Chelghoum Laïd',
        'address'     => 'Cité Djamaa Lakhdar, Chelghoum Laïd',
        'status'      => 'approved',
    ];
}

$ent_status = $ent['status'] ?? 'pending';

/* ── Document stats ─────────────────────────────────────────── */
$total   = qcount("SELECT COUNT(*) FROM documents WHERE uploaded_by=$user_id", $conn, 8);
$pending = qcount("SELECT COUNT(*) FROM documents WHERE uploaded_by=$user_id AND status='submitted'", $conn, 2);
$valid   = qcount("SELECT COUNT(*) FROM documents WHERE uploaded_by=$user_id AND status='validated'", $conn, 4);
$rejected= qcount("SELECT COUNT(*) FROM documents WHERE uploaded_by=$user_id AND status='rejected'", $conn, 1);
$archived= qcount("SELECT COUNT(*) FROM documents WHERE uploaded_by=$user_id AND status='archived'", $conn, 1);

/* ── Notifications ──────────────────────────────────────────── */
$notifs = qrows("
    SELECT message, created_at, is_read, type
    FROM notifications
    WHERE user_id=$user_id
    ORDER BY created_at DESC LIMIT 6
", $conn);
$unread = qcount("SELECT COUNT(*) FROM notifications WHERE user_id=$user_id AND is_read=0", $conn, 2);

/* Demo notifications */
if (empty($notifs)) {
    $notifs = [
        ['message'=>'Votre document "Dossier Paiement ROUACHED" a été validé.', 'created_at'=>date('Y-m-d H:i:s',strtotime('-2 hours')), 'is_read'=>0, 'type'=>'success'],
        ['message'=>'Votre entreprise a été approuvée par l\'administrateur.', 'created_at'=>date('Y-m-d H:i:s',strtotime('-1 day')),  'is_read'=>0, 'type'=>'success'],
        ['message'=>'Document "ODS N°183" en attente de validation.', 'created_at'=>date('Y-m-d H:i:s',strtotime('-3 days')), 'is_read'=>1, 'type'=>'info'],
    ];
    $unread = 2;
}

/* ── Recent documents ───────────────────────────────────────── */
$docs = qrows("
    SELECT d.id, d.reference_number, d.title, d.status,
           d.file_type, d.created_at, d.file_size,
           dep.name AS dept_name, dep.code AS dept_code
    FROM documents d
    LEFT JOIN departments dep ON d.department_id = dep.id
    WHERE d.uploaded_by = $user_id
    ORDER BY d.created_at DESC LIMIT 8
", $conn);

/* Demo docs */
if (empty($docs)) {
    $demo = [
        ['Dossier Paiement Polyclinique ROUACHED', 'submitted', 'pdf', 'DOT',  'AT-2025-0001', '2.4 MB'],
        ['ODS N°183/DRT/SDT/2025',                 'validated', 'pdf', 'DOT',  'AT-2025-0002', '1.1 MB'],
        ['Bon de Commande N°2500434',               'archived',  'pdf', 'DAMP', 'AT-2025-0003', '890 KB'],
        ['Facture N°10/2025',                       'validated', 'pdf', 'DFC',  'AT-2025-0004', '1.8 MB'],
        ['Attachement Travaux 07/09/2025',          'submitted', 'docx','DOT',  'AT-2025-0005', '650 KB'],
        ['PV Réception Provisoire ROUACHED',        'validated', 'pdf', 'DOT',  'AT-2025-0006', '3.2 MB'],
        ['Avenant Réajustement N°17/DRT/2025',      'rejected',  'pdf', 'DOT',  'AT-2025-0007', '2.1 MB'],
        ['Demande Achat N°2501106',                 'archived',  'pdf', 'DAMP', 'AT-2025-0008', '780 KB'],
    ];
    foreach ($demo as $i => [$t,$s,$ft,$dc,$ref,$sz]) {
        $docs[] = [
            'id'               => $i+1,
            'reference_number' => $ref,
            'title'            => $t,
            'status'           => $s,
            'file_type'        => $ft,
            'created_at'       => date('Y-m-d', strtotime("-$i days")),
            'file_size'        => $sz,
            'dept_name'        => $dc,
            'dept_code'        => $dc,
        ];
    }
}

/* ── Dossier de paiement tracker ────────────────────────────── */
$dossier_steps = [
    ['label'=>'Ordre de Service (ODS)',        'icon'=>'fa-file-signature',   'done'=>true],
    ['label'=>'PV Ouverture de Chantier',      'icon'=>'fa-hard-hat',         'done'=>true],
    ['label'=>'Attachement des Travaux',       'icon'=>'fa-ruler-combined',   'done'=>true],
    ['label'=>'PV Réception Provisoire',       'icon'=>'fa-clipboard-check',  'done'=>true],
    ['label'=>'Facture',                       'icon'=>'fa-file-invoice',     'done'=>true],
    ['label'=>'Attestation Service Fait',      'icon'=>'fa-certificate',      'done'=>false],
    ['label'=>'Demande de Paiement',           'icon'=>'fa-hand-holding-dollar','done'=>false],
    ['label'=>'Ordre de Virement',             'icon'=>'fa-building-columns', 'done'=>false],
];
$steps_done  = count(array_filter($dossier_steps, fn($s) => $s['done']));
$steps_total = count($dossier_steps);
$progress_pct = round($steps_done / $steps_total * 100);

/* ── Helpers ────────────────────────────────────────────────── */
function sbadge(string $s): string {
    return match($s) {
        'validated' => '<span class="badge b-teal">Validé</span>',
        'submitted' => '<span class="badge b-amber">En attente</span>',
        'archived'  => '<span class="badge b-blue">Archivé</span>',
        'rejected'  => '<span class="badge b-red">Rejeté</span>',
        default     => '<span class="badge b-gray">Brouillon</span>',
    };
}
function ficon(string $t): string {
    return match($t) {
        'pdf'  => '<span class="fi fi-pdf">PDF</span>',
        'docx' => '<span class="fi fi-doc">DOC</span>',
        'xlsx' => '<span class="fi fi-xls">XLS</span>',
        default=> '<span class="fi fi-img">IMG</span>',
    };
}
function ent_status_badge(string $s): string {
    return match($s) {
        'approved' => '<span class="badge b-teal"><i class="fa-solid fa-circle-check"></i> Approuvée</span>',
        'pending'  => '<span class="badge b-amber"><i class="fa-solid fa-clock"></i> En attente</span>',
        'rejected' => '<span class="badge b-red"><i class="fa-solid fa-xmark"></i> Rejetée</span>',
        default    => '<span class="badge b-gray">—</span>',
    };
}
function notif_icon(string $t): string {
    return match($t) {
        'success' => '<i class="fa-solid fa-circle-check" style="color:var(--teal)"></i>',
        'warning' => '<i class="fa-solid fa-triangle-exclamation" style="color:var(--amber)"></i>',
        'error'   => '<i class="fa-solid fa-circle-xmark" style="color:var(--red)"></i>',
        default   => '<i class="fa-solid fa-circle-info" style="color:var(--blue)"></i>',
    };
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Mon Espace Entreprise — AT-AMS</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
/* ══ TOKENS ══════════════════════════════════════════════════ */
:root{
  --bg:#0b0f0e; --surf:#111615; --surf2:#161d1b; --surf3:#1c2422;
  --border:rgba(255,255,255,.07); --border2:rgba(0,191,165,.2);
  --teal:#00BFA5; --teal-d:rgba(0,191,165,.12); --teal-g:rgba(0,191,165,.22);
  --amber:#F59E0B; --red:#EF4444; --blue:#3B82F6; --purple:#8B5CF6; --green:#22C55E;
  --text:#E8EDEC; --muted:#6B7A78; --dim:#9AAFAD;
  --sw:256px; --th:64px; --rad:14px; --rad-s:8px;
  --fh:'Syne',sans-serif; --fb:'DM Sans',sans-serif;
  --tr:.2s cubic-bezier(.4,0,.2,1);
}
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
body{font-family:var(--fb);background:var(--bg);color:var(--text);min-height:100vh;overflow-x:hidden}
a{color:inherit;text-decoration:none}
button{cursor:pointer;font-family:inherit;border:none;background:none}

/* ══ LAYOUT ══ */
.layout{display:flex;min-height:100vh}

/* ══ SIDEBAR ══ */
.sidebar{
  width:var(--sw);background:var(--surf);border-right:1px solid var(--border);
  display:flex;flex-direction:column;
  position:fixed;top:0;left:0;bottom:0;z-index:100;
  transition:transform var(--tr);overflow-y:auto;overflow-x:hidden;
}
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
.nb-teal{background:var(--teal);color:#000}
.s-foot{margin-top:auto;padding:14px 10px;border-top:1px solid var(--border)}
.s-user{display:flex;align-items:center;gap:9px;padding:9px 10px;background:var(--surf2);border-radius:var(--rad-s)}
.s-avatar{width:32px;height:32px;background:linear-gradient(135deg,#00BFA5,#007A6A);border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:700;color:#000;flex-shrink:0}
.s-uinfo{flex:1;min-width:0}
.s-uinfo strong{font-size:12px;font-weight:600;display:block;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.s-uinfo span{font-size:10px;color:var(--teal)}

/* ══ MAIN ══ */
.main{margin-left:var(--sw);flex:1;display:flex;flex-direction:column;min-height:100vh}

/* ══ TOPBAR ══ */
.topbar{height:var(--th);background:var(--surf);border-bottom:1px solid var(--border);display:flex;align-items:center;padding:0 26px;gap:14px;position:sticky;top:0;z-index:50}
.tb-toggle{display:none;color:var(--muted);font-size:19px;padding:6px;border-radius:6px}
.tb-search{flex:1;max-width:360px;position:relative}
.tb-search input{width:100%;background:var(--surf2);border:1px solid var(--border);color:var(--text);font-family:var(--fb);font-size:13px;padding:8px 14px 8px 36px;border-radius:9px;outline:none;transition:border var(--tr)}
.tb-search input:focus{border-color:var(--border2)}
.tb-search input::placeholder{color:var(--muted)}
.tb-search i{position:absolute;left:11px;top:50%;transform:translateY(-50%);color:var(--muted);font-size:13px}
.tb-actions{margin-left:auto;display:flex;align-items:center;gap:9px}
.tb-btn{width:36px;height:36px;background:var(--surf2);border:1px solid var(--border);border-radius:9px;display:flex;align-items:center;justify-content:center;color:var(--muted);font-size:14px;transition:all var(--tr);position:relative;cursor:pointer}
.tb-btn:hover{border-color:var(--border2);color:var(--teal)}
.notif-dot{position:absolute;top:7px;right:7px;width:6px;height:6px;background:var(--teal);border-radius:50%;border:2px solid var(--surf)}
.tb-date{font-size:12px;color:var(--muted);white-space:nowrap}

/* ══ CONTENT ══ */
.content{padding:26px;flex:1}

/* ══ PAGE HEADER ══ */
.pg-head{display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:24px;gap:14px;flex-wrap:wrap}
.pg-title{font-family:var(--fh);font-size:24px;font-weight:800;letter-spacing:-.5px}
.pg-sub{font-size:13px;color:var(--muted);margin-top:3px}
.pg-actions{display:flex;gap:9px;flex-wrap:wrap;align-items:center}

/* ══ BUTTONS ══ */
.btn{display:inline-flex;align-items:center;gap:7px;padding:9px 17px;border-radius:var(--rad-s);font-size:13px;font-weight:600;transition:all var(--tr);white-space:nowrap}
.btn-primary{background:var(--teal);color:#000;box-shadow:0 4px 14px var(--teal-g)}
.btn-primary:hover{background:#00e6c4;transform:translateY(-1px)}
.btn-outline{background:transparent;color:var(--text);border:1px solid var(--border)}
.btn-outline:hover{border-color:var(--border2);color:var(--teal)}
.btn-sm{padding:6px 13px;font-size:12px}

/* ══ WELCOME BANNER ══ */
.welcome{
  background:linear-gradient(135deg,var(--surf2) 0%,rgba(0,191,165,.08) 100%);
  border:1px solid var(--border2);border-radius:var(--rad);
  padding:22px 26px;margin-bottom:22px;
  display:flex;align-items:center;gap:18px;
  position:relative;overflow:hidden;
}
.welcome::before{content:'';position:absolute;right:-40px;top:-40px;width:160px;height:160px;background:radial-gradient(circle,var(--teal-g),transparent 70%);pointer-events:none}
.wb-av{width:48px;height:48px;background:linear-gradient(135deg,var(--teal),#007A6A);border-radius:50%;display:flex;align-items:center;justify-content:center;font-family:var(--fh);font-size:18px;font-weight:800;color:#000;flex-shrink:0}
.wb-txt h2{font-family:var(--fh);font-size:17px;font-weight:800;letter-spacing:-.3px}
.wb-txt p{font-size:12.5px;color:var(--dim);margin-top:3px}
.wb-right{margin-left:auto;display:flex;flex-direction:column;align-items:flex-end;gap:6px}
.ent-status-wrap{display:flex;align-items:center;gap:8px}

/* ══ STATS ══ */
.stats-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(155px,1fr));gap:13px;margin-bottom:22px}
.stat-card{background:var(--surf);border:1px solid var(--border);border-radius:var(--rad);padding:17px;position:relative;overflow:hidden;transition:all var(--tr);cursor:default}
.stat-card:hover{border-color:var(--border2);transform:translateY(-2px);box-shadow:0 12px 28px rgba(0,0,0,.3)}
.stat-card::before{content:'';position:absolute;top:0;left:0;right:0;height:2px;background:linear-gradient(90deg,transparent,var(--ac,var(--teal)),transparent);opacity:.7}
.stat-card.c-amber{--ac:var(--amber)}
.stat-card.c-blue{--ac:var(--blue)}
.stat-card.c-red{--ac:var(--red)}
.s-ico{width:36px;height:36px;background:var(--teal-d);border-radius:9px;display:flex;align-items:center;justify-content:center;font-size:15px;color:var(--teal);margin-bottom:13px}
.stat-card.c-amber .s-ico{background:rgba(245,158,11,.12);color:var(--amber)}
.stat-card.c-blue  .s-ico{background:rgba(59,130,246,.12);color:var(--blue)}
.stat-card.c-red   .s-ico{background:rgba(239,68,68,.12);color:var(--red)}
.s-val{font-family:var(--fh);font-size:28px;font-weight:800;line-height:1;margin-bottom:3px}
.s-lbl2{font-size:11px;color:var(--muted);font-weight:500}

/* ══ GRID ══ */
.grid-main{display:grid;grid-template-columns:1fr 320px;gap:18px;margin-bottom:20px}
.grid-bottom{display:grid;grid-template-columns:1fr 1fr;gap:18px}

/* ══ CARD ══ */
.card{background:var(--surf);border:1px solid var(--border);border-radius:var(--rad);overflow:hidden}
.card-hd{display:flex;align-items:center;justify-content:space-between;padding:15px 18px 12px;border-bottom:1px solid var(--border)}
.card-title{font-family:var(--fh);font-size:14px;font-weight:700}
.card-sub{font-size:11px;color:var(--muted);margin-top:2px}
.card-body{padding:16px 18px}

/* ══ DOC TABLE ══ */
.doc-table{width:100%;border-collapse:collapse}
.doc-table th{font-size:10px;font-weight:700;letter-spacing:.1em;text-transform:uppercase;color:var(--muted);padding:9px 14px;text-align:left;border-bottom:1px solid var(--border);white-space:nowrap}
.doc-table td{padding:11px 14px;border-bottom:1px solid rgba(255,255,255,.04);font-size:13px;vertical-align:middle}
.doc-table tr:last-child td{border-bottom:none}
.doc-table tr:hover td{background:rgba(255,255,255,.02)}
.doc-ref{font-family:monospace;font-size:11px;color:var(--muted)}
.doc-title-cell{max-width:200px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;font-weight:500}
.doc-date{font-size:11px;color:var(--muted);white-space:nowrap}

/* ══ BADGES ══ */
.badge{display:inline-flex;align-items:center;gap:4px;font-size:10.5px;font-weight:600;padding:3px 9px;border-radius:20px;white-space:nowrap}
.b-teal {background:rgba(0,191,165,.15);color:var(--teal)}
.b-amber{background:rgba(245,158,11,.15);color:var(--amber)}
.b-blue {background:rgba(59,130,246,.15);color:var(--blue)}
.b-red  {background:rgba(239,68,68,.15);color:var(--red)}
.b-gray {background:rgba(107,114,128,.15);color:#9CA3AF}

/* ══ FILE ICONS ══ */
.fi{display:inline-flex;align-items:center;justify-content:center;font-size:9px;font-weight:800;padding:3px 6px;border-radius:4px;min-width:30px}
.fi-pdf{background:rgba(239,68,68,.15);color:var(--red)}
.fi-doc{background:rgba(59,130,246,.15);color:var(--blue)}
.fi-xls{background:rgba(34,197,94,.15);color:#22C55E}
.fi-img{background:rgba(168,85,247,.15);color:#A855F7}

/* ══ ENTERPRISE INFO ══ */
.ent-info-grid{display:flex;flex-direction:column;gap:10px}
.ent-row{display:flex;align-items:flex-start;gap:10px;padding:9px 0;border-bottom:1px solid rgba(255,255,255,.04)}
.ent-row:last-child{border-bottom:none}
.ent-icon{width:28px;height:28px;background:var(--teal-d);border-radius:7px;display:flex;align-items:center;justify-content:center;color:var(--teal);font-size:11px;flex-shrink:0;margin-top:1px}
.ent-key{font-size:10.5px;color:var(--muted);font-weight:600;text-transform:uppercase;letter-spacing:.08em;display:block;margin-bottom:2px}
.ent-val{font-size:13px;font-weight:500}

/* ══ DOSSIER TRACKER ══ */
.tracker-progress{margin-bottom:16px}
.progress-bar-wrap{height:6px;background:rgba(255,255,255,.07);border-radius:6px;overflow:hidden;margin-bottom:6px}
.progress-bar-fill{height:100%;background:linear-gradient(90deg,var(--teal),#00e6c4);border-radius:6px;transition:width .6s ease}
.progress-label{font-size:11px;color:var(--muted);display:flex;justify-content:space-between}
.step-list{display:flex;flex-direction:column;gap:1px}
.step-item{display:flex;align-items:center;gap:10px;padding:8px 0;border-bottom:1px solid rgba(255,255,255,.04)}
.step-item:last-child{border-bottom:none}
.step-dot{width:24px;height:24px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:11px;flex-shrink:0}
.step-done{background:rgba(0,191,165,.15);color:var(--teal);border:1px solid rgba(0,191,165,.3)}
.step-todo{background:rgba(255,255,255,.05);color:var(--muted);border:1px solid rgba(255,255,255,.1)}
.step-label{font-size:12.5px;flex:1}
.step-label.done{color:var(--text)}
.step-label.todo{color:var(--muted)}
.step-icon{font-size:11px;color:var(--muted)}

/* ══ NOTIFICATIONS ══ */
.notif-list{display:flex;flex-direction:column}
.notif-item{display:flex;align-items:flex-start;gap:10px;padding:11px 0;border-bottom:1px solid rgba(255,255,255,.04)}
.notif-item:last-child{border-bottom:none}
.notif-item.unread{background:rgba(0,191,165,.03)}
.notif-ico{width:28px;height:28px;border-radius:8px;background:var(--surf2);display:flex;align-items:center;justify-content:center;font-size:13px;flex-shrink:0}
.notif-text{font-size:12.5px;line-height:1.5;flex:1}
.notif-time{font-size:10.5px;color:var(--muted);white-space:nowrap;margin-top:2px}
.unread-dot{width:6px;height:6px;background:var(--teal);border-radius:50%;margin-top:6px;flex-shrink:0}

/* ══ QUICK UPLOAD ══ */
.upload-zone{
  border:2px dashed rgba(0,191,165,.25);border-radius:var(--rad);
  padding:28px 20px;text-align:center;
  transition:all var(--tr);cursor:pointer;
  background:var(--teal-d);
}
.upload-zone:hover{border-color:var(--teal);background:rgba(0,191,165,.18)}
.upload-zone i{font-size:28px;color:var(--teal);margin-bottom:10px;display:block}
.upload-zone strong{font-size:13px;font-weight:600;display:block;margin-bottom:4px}
.upload-zone span{font-size:11.5px;color:var(--muted)}

/* ══ EMPTY ══ */
.empty{text-align:center;padding:30px 20px;color:var(--muted);font-size:13px}
.empty i{font-size:28px;margin-bottom:8px;display:block}

/* ══ OVERLAY ══ */
.overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.6);z-index:99}

/* ══ ANIMATIONS ══ */
@keyframes fadeUp{from{opacity:0;transform:translateY(12px)}to{opacity:1;transform:translateY(0)}}
.a1{animation:fadeUp .4s ease both}
.a2{animation:fadeUp .4s .07s ease both}
.a3{animation:fadeUp .4s .14s ease both}
.a4{animation:fadeUp .4s .21s ease both}
.a5{animation:fadeUp .4s .28s ease both}

/* ══ RESPONSIVE ══ */
@media(max-width:1100px){.grid-main{grid-template-columns:1fr}.grid-bottom{grid-template-columns:1fr}}
@media(max-width:900px){
  .sidebar{transform:translateX(-100%)}
  .sidebar.open{transform:translateX(0)}
  .overlay.open{display:block}
  .main{margin-left:0}
  .tb-toggle{display:flex!important}
  .tb-date{display:none}
}
@media(max-width:600px){
  .stats-grid{grid-template-columns:1fr 1fr}
  .content{padding:14px}
  .topbar{padding:0 14px}
  .welcome{flex-wrap:wrap}
  .wb-right{margin-left:0}
  .doc-table th:nth-child(2),.doc-table td:nth-child(2){display:none}
}
@media(max-width:380px){.stats-grid{grid-template-columns:1fr}}

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
    <div class="s-logo-txt"><strong>AT-AMS</strong><span>Espace Entreprise</span></div>
  </div>

  <div class="s-sec">
    <div class="s-lbl">Mon Espace</div>
    <a href="index.php" class="nav-item active">
      <i class="fa-solid fa-house"></i> Tableau de bord
    </a>
    <a href="../../documents/upload.php" class="nav-item">
      <i class="fa-solid fa-cloud-arrow-up"></i> Uploader un dossier
    </a>
    <a href="../../documents/list.php" class="nav-item">
      <i class="fa-solid fa-file-lines"></i> Mes Documents
      <?php if($pending > 0): ?>
      <span class="nb"><?= $pending ?></span>
      <?php endif; ?>
    </a>
    <a href="../../documents/search.php" class="nav-item">
      <i class="fa-solid fa-magnifying-glass"></i> Recherche
    </a>
  </div>

  <div class="s-sec">
    <div class="s-lbl">Mon Entreprise</div>
    <a href="enterprise_profile.php" class="nav-item">
      <i class="fa-solid fa-building"></i> Dossier Entreprise
    </a>
    <a href="dossier_paiement.php" class="nav-item">
      <i class="fa-solid fa-file-invoice-dollar"></i> Dossiers de Paiement
    </a>
  </div>

  <div class="s-sec">
    <div class="s-lbl">Compte</div>
    <a href="../../profile/index.php" class="nav-item">
      <i class="fa-solid fa-user-gear"></i> Mon Profil
    </a>
    <a href="../../auth/login.php?logout=1" class="nav-item" style="color:var(--red)">
      <i class="fa-solid fa-right-from-bracket"></i> Déconnexion
    </a>
  </div>

  <div class="s-foot">
    <div class="s-user">
      <div class="s-avatar"><?= strtoupper(substr($user_name, 0, 1)) ?></div>
      <div class="s-uinfo">
        <strong><?= htmlspecialchars($user_name) ?></strong>
        <span>Entreprise</span>
      </div>
    </div>
  </div>
</aside>

<div class="overlay" id="overlay" onclick="closeSidebar()"></div>

<!-- ══ MAIN ══════════════════════════════════════════════════ -->
<main class="main">

  <!-- Topbar -->
  <header class="topbar">
    <button class="tb-toggle" onclick="toggleSidebar()">
      <i class="fa-solid fa-bars"></i>
    </button>
    <div class="tb-search">
      <i class="fa-solid fa-magnifying-glass"></i>
      <input type="text" placeholder="Rechercher vos documents…"
             onkeydown="if(event.key==='Enter') window.location='../../documents/search.php?q='+this.value">
    </div>
    <div class="tb-actions">
      <span class="tb-date" id="tb-date"></span>
      <a href="../../documents/upload.php" class="tb-btn" title="Uploader un document">
        <i class="fa-solid fa-cloud-arrow-up"></i>
      </a>
      <button class="tb-btn" title="Notifications" onclick="toggleNotifPanel()">
        <i class="fa-regular fa-bell"></i>
        <?php if($unread > 0): ?><span class="notif-dot"></span><?php endif; ?>
      </button>
      <a href="../../profile/index.php" class="tb-btn" title="Mon profil">
        <i class="fa-regular fa-user"></i>
      </a>
    </div>
  </header>

  <!-- Content -->
  <div class="content">

    <!-- Page Header -->
    <div class="pg-head a1">
      <div>
        <h1 class="pg-title">Mon Espace Entreprise</h1>
        <p class="pg-sub">Gérez vos dossiers et suivez l'avancement de vos paiements</p>
      </div>
      <div class="pg-actions">
        <a href="../../documents/upload.php" class="btn btn-primary">
          <i class="fa-solid fa-plus"></i> Nouveau dossier
        </a>
        <a href="../../documents/list.php" class="btn btn-outline">
          <i class="fa-solid fa-list"></i> Tous mes docs
        </a>
      </div>
    </div>

    <!-- Welcome Banner -->
    <div class="welcome a1">
      <div class="wb-av"><?= strtoupper(substr($first, 0, 1)) ?></div>
      <div class="wb-txt">
        <h2>Bonjour, <?= htmlspecialchars($first) ?> 👋</h2>
        <p>
          <?php if($ent_status === 'approved'): ?>
            Votre entreprise est active — vous pouvez soumettre vos dossiers.
          <?php elseif($ent_status === 'pending'): ?>
            Votre dossier est en cours de validation par l'administration.
          <?php else: ?>
            Votre dossier a été rejeté — contactez l'administration.
          <?php endif; ?>
        </p>
      </div>
      <div class="wb-right">
        <div class="ent-status-wrap">
          <span style="font-size:11px;color:var(--muted)">Statut entreprise :</span>
          <?= ent_status_badge($ent_status) ?>
        </div>
        <span style="font-size:11px;color:var(--muted)"><?= date('l d F Y') ?></span>
      </div>
    </div>

    <!-- Stats -->
    <div class="stats-grid a2">
      <div class="stat-card">
        <div class="s-ico"><i class="fa-solid fa-file-lines"></i></div>
        <div class="s-val"><?= $total ?></div>
        <div class="s-lbl2">Total Documents</div>
      </div>
      <div class="stat-card c-amber">
        <div class="s-ico"><i class="fa-solid fa-clock"></i></div>
        <div class="s-val"><?= $pending ?></div>
        <div class="s-lbl2">En attente</div>
      </div>
      <div class="stat-card">
        <div class="s-ico" style="background:rgba(0,191,165,.12);color:var(--teal)">
          <i class="fa-solid fa-circle-check"></i>
        </div>
        <div class="s-val"><?= $valid ?></div>
        <div class="s-lbl2">Validés</div>
      </div>
      <div class="stat-card c-blue">
        <div class="s-ico"><i class="fa-solid fa-box-archive"></i></div>
        <div class="s-val"><?= $archived ?></div>
        <div class="s-lbl2">Archivés</div>
      </div>
      <div class="stat-card c-red">
        <div class="s-ico"><i class="fa-solid fa-circle-xmark"></i></div>
        <div class="s-val"><?= $rejected ?></div>
        <div class="s-lbl2">Rejetés</div>
      </div>
    </div>

    <!-- Main Grid: docs table + enterprise info -->
    <div class="grid-main a3">

      <!-- Recent Documents -->
      <div class="card">
        <div class="card-hd">
          <div>
            <div class="card-title">Mes Documents Récents</div>
            <div class="card-sub">8 derniers dossiers soumis</div>
          </div>
          <a href="../../documents/list.php" class="btn btn-outline btn-sm">Voir tout</a>
        </div>
        <div style="overflow-x:auto">
          <?php if(empty($docs)): ?>
          <div class="empty">
            <i class="fa-regular fa-folder-open"></i>
            Aucun document soumis.
            <br><a href="../../documents/upload.php" style="color:var(--teal);margin-top:8px;display:inline-block">Uploader votre premier dossier →</a>
          </div>
          <?php else: ?>
          <table class="doc-table">
            <thead>
              <tr>
                <th>Type</th>
                <th>Référence</th>
                <th>Titre</th>
                <th>Statut</th>
                <th>Date</th>
                <th></th>
              </tr>
            </thead>
            <tbody>
              <?php foreach($docs as $d): ?>
              <tr>
                <td><?= ficon($d['file_type'] ?? 'pdf') ?></td>
                <td><span class="doc-ref"><?= htmlspecialchars($d['reference_number']) ?></span></td>
                <td>
                  <div class="doc-title-cell" title="<?= htmlspecialchars($d['title']) ?>">
                    <?= htmlspecialchars($d['title']) ?>
                  </div>
                </td>
                <td><?= sbadge($d['status']) ?></td>
                <td><span class="doc-date"><?= date('d/m/Y', strtotime($d['created_at'])) ?></span></td>
                <td>
                  <a href="../../documents/detail.php?id=<?= $d['id'] ?>"
                     class="btn btn-outline btn-sm" style="padding:5px 9px">
                    <i class="fa-regular fa-eye"></i>
                  </a>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
          <?php endif; ?>
        </div>
      </div>

      <!-- Enterprise Info -->
      <div class="card">
        <div class="card-hd">
          <div>
            <div class="card-title">Mon Entreprise</div>
            <div class="card-sub">Informations du dossier</div>
          </div>
          <?= ent_status_badge($ent_status) ?>
        </div>
        <div class="card-body">
          <div class="ent-info-grid">
            <div class="ent-row">
              <div class="ent-icon"><i class="fa-solid fa-id-card"></i></div>
              <div>
                <span class="ent-key">NIF</span>
                <span class="ent-val"><?= htmlspecialchars($ent['nif'] ?? '—') ?></span>
              </div>
            </div>
            <div class="ent-row">
              <div class="ent-icon"><i class="fa-solid fa-registered"></i></div>
              <div>
                <span class="ent-key">Registre Commercial</span>
                <span class="ent-val"><?= htmlspecialchars($ent['rc'] ?? '—') ?></span>
              </div>
            </div>
            <div class="ent-row">
              <div class="ent-icon"><i class="fa-solid fa-building-columns"></i></div>
              <div>
                <span class="ent-key">RIB — <?= htmlspecialchars($ent['bank_name'] ?? '') ?></span>
                <span class="ent-val" style="font-size:12px;font-family:monospace">
                  <?= htmlspecialchars($ent['rib'] ?? '—') ?>
                </span>
              </div>
            </div>
            <div class="ent-row">
              <div class="ent-icon"><i class="fa-solid fa-location-dot"></i></div>
              <div>
                <span class="ent-key">Localisation</span>
                <span class="ent-val">
                  <?= htmlspecialchars(($ent['city_name'] ?? '') . ', ' . ($ent['wilaya_name'] ?? '')) ?>
                </span>
              </div>
            </div>
            <div class="ent-row">
              <div class="ent-icon"><i class="fa-solid fa-map-pin"></i></div>
              <div>
                <span class="ent-key">Adresse</span>
                <span class="ent-val" style="font-size:12px">
                  <?= htmlspecialchars($ent['address'] ?? '—') ?>
                </span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Bottom Grid: dossier tracker + notifications -->
    <div class="grid-bottom a4">

      <!-- Dossier de Paiement Tracker -->
      <div class="card">
        <div class="card-hd">
          <div>
            <div class="card-title">Suivi Dossier de Paiement</div>
            <div class="card-sub">Avancement des pièces requises</div>
          </div>
          <span class="badge b-teal"><?= $steps_done ?>/<?= $steps_total ?></span>
        </div>
        <div class="card-body">
          <div class="tracker-progress">
            <div class="progress-bar-wrap">
              <div class="progress-bar-fill" style="width:<?= $progress_pct ?>%"></div>
            </div>
            <div class="progress-label">
              <span><?= $steps_done ?> pièces complètes</span>
              <span><?= $progress_pct ?>%</span>
            </div>
          </div>
          <div class="step-list">
            <?php foreach($dossier_steps as $step): ?>
            <div class="step-item">
              <div class="step-dot <?= $step['done'] ? 'step-done' : 'step-todo' ?>">
                <?php if($step['done']): ?>
                  <i class="fa-solid fa-check" style="font-size:10px"></i>
                <?php else: ?>
                  <i class="fa-solid fa-circle" style="font-size:6px;opacity:.4"></i>
                <?php endif; ?>
              </div>
              <div class="step-label <?= $step['done'] ? 'done' : 'todo' ?>">
                <?= htmlspecialchars($step['label']) ?>
              </div>
              <i class="fa-solid <?= $step['icon'] ?> step-icon"></i>
            </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>

      <!-- Right: Notifications + Quick Upload -->
      <div style="display:flex;flex-direction:column;gap:18px">

        <!-- Notifications -->
        <div class="card">
          <div class="card-hd">
            <div>
              <div class="card-title">Notifications</div>
              <div class="card-sub">Messages de l'administration</div>
            </div>
            <?php if($unread > 0): ?>
            <span class="badge b-teal"><?= $unread ?> nouveau<?= $unread>1?'x':'' ?></span>
            <?php endif; ?>
          </div>
          <div class="card-body" style="padding:8px 16px">
            <?php if(empty($notifs)): ?>
            <div class="empty" style="padding:20px 0">
              <i class="fa-regular fa-bell-slash"></i>Aucune notification
            </div>
            <?php else: ?>
            <div class="notif-list">
              <?php foreach($notifs as $n): ?>
              <div class="notif-item <?= !$n['is_read'] ? 'unread' : '' ?>">
                <div class="notif-ico"><?= notif_icon($n['type'] ?? 'info') ?></div>
                <div style="flex:1;min-width:0">
                  <div class="notif-text"><?= htmlspecialchars($n['message']) ?></div>
                  <div class="notif-time">
                    <?= date('d/m/Y H:i', strtotime($n['created_at'])) ?>
                  </div>
                </div>
                <?php if(!$n['is_read']): ?>
                <div class="unread-dot"></div>
                <?php endif; ?>
              </div>
              <?php endforeach; ?>
            </div>
            <?php endif; ?>
          </div>
        </div>

        <!-- Quick Upload -->
        <div class="card">
          <div class="card-hd">
            <div class="card-title">Upload Rapide</div>
          </div>
          <div class="card-body">
            <a href="../../documents/upload.php">
              <div class="upload-zone">
                <i class="fa-solid fa-cloud-arrow-up"></i>
                <strong>Déposer un nouveau dossier</strong>
                <span>PDF, DOCX, XLSX — max 50 MB</span>
              </div>
            </a>
          </div>
        </div>

      </div>
    </div>

  </div><!-- /content -->
</main>
</div><!-- /layout -->

<script>
function toggleSidebar() {
  document.getElementById('sidebar').classList.toggle('open');
  document.getElementById('overlay').classList.toggle('open');
}
function closeSidebar() {
  document.getElementById('sidebar').classList.remove('open');
  document.getElementById('overlay').classList.remove('open');
}
(function() {
  const el = document.getElementById('tb-date');
  if (el) el.textContent = new Date().toLocaleDateString('fr-FR', {
    weekday: 'short', day: 'numeric', month: 'short'
  });
})();
</script>
</body>
</html>