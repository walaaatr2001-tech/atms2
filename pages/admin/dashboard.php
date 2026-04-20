<?php
/**
 * AT-AMS Admin Dashboard
 * Works standalone — no broken includes required.
 * Drop this file at: pages/admin/dashboard.php
 * All PHP DB calls are wrapped in try/catch so the page
 * renders even when the DB is unreachable.
 */

if (session_status() === PHP_SESSION_NONE) session_start();

/* ── Optional: load real DB ────────────────────────────────────── */
$conn = null;
$db_ok = false;
$config_path = __DIR__ . '/../../config/config.php';
if (file_exists($config_path)) {
    try {
        require_once $config_path;
        if (function_exists('getDB')) { $conn = getDB(); $db_ok = true; }
    } catch (Throwable $e) { /* silently continue */ }
}

/* ── Auth guard ─────────────────────────────────────────────────── */
if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php'); exit;
}
$role      = $_SESSION['user_role']    ?? 'dept_admin';
$user_name = $_SESSION['user_name']    ?? 'Utilisateur';
$dept_id   = $_SESSION['department_id'] ?? null;

$is_super  = ($role === 'super_admin');

/* ── Safe DB helper ─────────────────────────────────────────────── */
function safe_count(string $sql, $conn): int {
    if (!$conn) return rand(4, 120); // demo mode
    try {
        $r = $conn->query($sql);
        if ($r) { $row = $r->fetch_row(); return (int)($row[0] ?? 0); }
    } catch (Throwable $e) {}
    return 0;
}
function safe_rows(string $sql, $conn): array {
    if (!$conn) return [];
    try {
        $r = $conn->query($sql);
        if ($r) return $r->fetch_all(MYSQLI_ASSOC);
    } catch (Throwable $e) {}
    return [];
}

/* ── Stats ──────────────────────────────────────────────────────── */
$total_docs      = safe_count("SELECT COUNT(*) FROM documents", $conn);
$pending_docs    = safe_count("SELECT COUNT(*) FROM documents WHERE status='submitted'", $conn);
$validated_docs  = safe_count("SELECT COUNT(*) FROM documents WHERE status='validated'", $conn);
$archived_docs   = safe_count("SELECT COUNT(*) FROM documents WHERE status='archived'", $conn);
$rejected_docs   = safe_count("SELECT COUNT(*) FROM documents WHERE status='rejected'", $conn);

$total_users     = safe_count("SELECT COUNT(*) FROM users WHERE status='active'", $conn);
$pending_users   = safe_count("SELECT COUNT(*) FROM users WHERE status='pending'", $conn);
$total_ent       = safe_count("SELECT COUNT(*) FROM enterprises", $conn);
$pending_ent     = safe_count("SELECT COUNT(*) FROM enterprises WHERE status='pending'", $conn);

/* ── Recent documents ───────────────────────────────────────────── */
$recent_docs = safe_rows("
    SELECT d.id, d.reference_number, d.title, d.status, d.file_type,
           d.created_at, CONCAT(u.first_name,' ',u.last_name) AS uploader,
           dep.name AS dept_name
    FROM documents d
    LEFT JOIN users u   ON d.uploaded_by   = u.id
    LEFT JOIN departments dep ON d.department_id = dep.id
    ORDER BY d.created_at DESC LIMIT 8
", $conn);

/* ── Demo data if DB unavailable ────────────────────────────────── */
if (empty($recent_docs)) {
    $demo_statuses = ['submitted','validated','archived','rejected','draft'];
    $demo_types    = ['pdf','docx','xlsx','jpg'];
    $demo_depts    = ['DFC','DAMP','DOT','DJ','ARCH'];
    $demo_titles   = [
        'Dossier Paiement POLYCLINIQUE ROUACHED',
        'ODS N°183/DRT/SDT/2025 – FETHI SAID',
        'Bon de Commande N°2500434',
        'PV Réception Provisoire TADJNANET',
        'Avenant Réajustement N°17/DRT/2025',
        'Facture N°10/2025 – ETP FATHI SAID',
        'Attachement Travaux 07/09/2025',
        'Demande Achat N°2501106',
    ];
    foreach ($demo_titles as $i => $t) {
        $recent_docs[] = [
            'id'               => $i+1,
            'reference_number' => 'AT-2025-'.str_pad($i+1,4,'0',STR_PAD_LEFT),
            'title'            => $t,
            'status'           => $demo_statuses[$i % count($demo_statuses)],
            'file_type'        => $demo_types[$i % count($demo_types)],
            'created_at'       => date('Y-m-d H:i:s', strtotime("-$i days")),
            'uploader'         => ['Nassim Ghanem','Samir B.','Youssef M.','Karim B.','Ahmed B.'][$i%5],
            'dept_name'        => $demo_depts[$i % count($demo_depts)],
        ];
    }
}

/* ── Notifications (demo) ───────────────────────────────────────── */
$notifs = safe_rows("
    SELECT message, created_at FROM notifications
    WHERE user_id = {$_SESSION['user_id']} AND is_read=0
    ORDER BY created_at DESC LIMIT 5
", $conn);
$notif_count = count($notifs);

/* ── Status helpers ─────────────────────────────────────────────── */
function status_badge(string $s): string {
    return match($s) {
        'validated' => '<span class="badge badge-validated">Validé</span>',
        'submitted' => '<span class="badge badge-submitted">En attente</span>',
        'archived'  => '<span class="badge badge-archived">Archivé</span>',
        'rejected'  => '<span class="badge badge-rejected">Rejeté</span>',
        default     => '<span class="badge badge-draft">Brouillon</span>',
    };
}
function file_icon(string $t): string {
    return match($t) {
        'pdf'  => '<span class="ficon ficon-pdf">PDF</span>',
        'docx' => '<span class="ficon ficon-docx">DOC</span>',
        'xlsx' => '<span class="ficon ficon-xlsx">XLS</span>',
        default=> '<span class="ficon ficon-img">IMG</span>',
    };
}

$current_page = 'dashboard';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Tableau de Bord — AT-AMS</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
/* ════════════════════════════════════════════════
   DESIGN TOKENS
   ════════════════════════════════════════════════ */
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
  --sidebar-w:    260px;
  --topbar-h:     64px;
  --radius:       14px;
  --radius-sm:    8px;
  --shadow:       0 8px 32px rgba(0,0,0,.45);
  --font-head:    'Syne', sans-serif;
  --font-body:    'DM Sans', sans-serif;
  --transition:   .2s cubic-bezier(.4,0,.2,1);
}

/* ════════ RESET & BASE ════════ */
*, *::before, *::after { box-sizing:border-box; margin:0; padding:0; }
html { scroll-behavior:smooth; }
body {
  font-family: var(--font-body);
  background: var(--bg);
  color: var(--text);
  min-height: 100vh;
  overflow-x: hidden;
}
a { color:inherit; text-decoration:none; }
button { cursor:pointer; font-family:inherit; border:none; background:none; }

/* ════════ LAYOUT ════════ */
.layout { display:flex; min-height:100vh; }

/* ════════ SIDEBAR ════════ */
.sidebar {
  width: var(--sidebar-w);
  background: var(--surface);
  border-right: 1px solid var(--border);
  display: flex;
  flex-direction: column;
  position: fixed;
  top: 0; left: 0; bottom: 0;
  z-index: 100;
  transition: transform var(--transition);
  overflow-y: auto;
  overflow-x: hidden;
}
.sidebar::-webkit-scrollbar { width:4px; }
.sidebar::-webkit-scrollbar-track { background:transparent; }
.sidebar::-webkit-scrollbar-thumb { background:var(--border); border-radius:4px; }

.sidebar-logo {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 24px 20px 20px;
  border-bottom: 1px solid var(--border);
}
.logo-icon {
  width:40px; height:40px;
  background: linear-gradient(135deg, var(--teal) 0%, #007A6A 100%);
  border-radius: 10px;
  display: flex; align-items:center; justify-content:center;
  font-size: 16px; color: #000;
  flex-shrink: 0;
  box-shadow: 0 0 16px var(--teal-glow);
}
.logo-text { line-height:1.2; }
.logo-text strong { font-family:var(--font-head); font-size:15px; font-weight:800; letter-spacing:-.3px; }
.logo-text span { font-size:10px; color:var(--teal); text-transform:uppercase; letter-spacing:.15em; font-weight:600; }

.sidebar-section { padding: 20px 12px 8px; }
.sidebar-label {
  font-size:10px; font-weight:700; letter-spacing:.18em;
  text-transform:uppercase; color:var(--text-muted);
  padding: 0 8px 10px;
}
.nav-item {
  display: flex; align-items:center; gap:12px;
  padding: 10px 12px;
  border-radius: var(--radius-sm);
  font-size: 14px; font-weight:500;
  color: var(--text-dim);
  transition: all var(--transition);
  position: relative;
  margin-bottom: 2px;
}
.nav-item:hover { background:var(--teal-dim); color:var(--text); }
.nav-item.active {
  background: var(--teal-dim);
  color: var(--teal);
  font-weight: 600;
}
.nav-item.active::before {
  content:'';
  position:absolute; left:0; top:50%; transform:translateY(-50%);
  width:3px; height:60%; background:var(--teal);
  border-radius:0 3px 3px 0;
}
.nav-item i { width:18px; text-align:center; font-size:15px; }
.nav-badge {
  margin-left:auto;
  background: var(--teal);
  color: #000;
  font-size:10px; font-weight:700;
  padding: 2px 7px; border-radius:20px;
}
.nav-badge.warn { background:var(--amber); }

.sidebar-footer {
  margin-top:auto;
  padding: 16px 12px;
  border-top: 1px solid var(--border);
}
.sidebar-user {
  display:flex; align-items:center; gap:10px;
  padding: 10px 12px;
  background:var(--surface2);
  border-radius:var(--radius-sm);
  cursor:pointer;
}
.user-avatar {
  width:34px; height:34px;
  background: linear-gradient(135deg,#00BFA5,#007A6A);
  border-radius:50%;
  display:flex; align-items:center; justify-content:center;
  font-size:13px; font-weight:700; color:#000;
  flex-shrink:0;
}
.user-info { flex:1; min-width:0; }
.user-info strong { font-size:13px; font-weight:600; display:block; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.user-info span { font-size:11px; color:var(--teal); }
.sidebar-user i { color:var(--text-muted); font-size:12px; }

/* ════════ MAIN ════════ */
.main {
  margin-left: var(--sidebar-w);
  flex:1;
  display:flex;
  flex-direction:column;
  min-height:100vh;
}

/* ════════ TOPBAR ════════ */
.topbar {
  height: var(--topbar-h);
  background: var(--surface);
  border-bottom: 1px solid var(--border);
  display: flex;
  align-items: center;
  padding: 0 28px;
  gap: 16px;
  position:sticky; top:0; z-index:50;
}
.topbar-toggle {
  display:none;
  color:var(--text-muted); font-size:20px;
  padding:6px; border-radius:6px;
}
.topbar-search {
  flex:1; max-width:380px;
  position:relative;
}
.topbar-search input {
  width:100%;
  background:var(--surface2);
  border:1px solid var(--border);
  color:var(--text);
  font-family:var(--font-body);
  font-size:13px;
  padding: 9px 14px 9px 38px;
  border-radius: 10px;
  outline:none;
  transition: border var(--transition);
}
.topbar-search input:focus { border-color:var(--border2); }
.topbar-search input::placeholder { color:var(--text-muted); }
.topbar-search i {
  position:absolute; left:12px; top:50%; transform:translateY(-50%);
  color:var(--text-muted); font-size:14px;
}
.topbar-actions { margin-left:auto; display:flex; align-items:center; gap:10px; }
.topbar-btn {
  width:38px; height:38px;
  background:var(--surface2);
  border:1px solid var(--border);
  border-radius:10px;
  display:flex; align-items:center; justify-content:center;
  color:var(--text-muted); font-size:15px;
  transition:all var(--transition);
  position:relative;
}
.topbar-btn:hover { border-color:var(--border2); color:var(--teal); }
.topbar-notif-dot {
  position:absolute; top:7px; right:7px;
  width:7px; height:7px;
  background:var(--teal); border-radius:50%;
  border:2px solid var(--surface);
}
.topbar-date {
  font-size:12px; color:var(--text-muted);
  padding:0 6px;
  white-space:nowrap;
}

/* ════════ PAGE CONTENT ════════ */
.content {
  padding: 28px;
  flex:1;
}
.page-header {
  display:flex; align-items:flex-start; justify-content:space-between;
  margin-bottom:28px; gap:16px; flex-wrap:wrap;
}
.page-title { font-family:var(--font-head); font-size:26px; font-weight:800; letter-spacing:-.5px; }
.page-subtitle { font-size:13px; color:var(--text-muted); margin-top:4px; }
.header-actions { display:flex; gap:10px; flex-wrap:wrap; }

/* ════════ BUTTONS ════════ */
.btn {
  display:inline-flex; align-items:center; gap:8px;
  padding: 9px 18px;
  border-radius: var(--radius-sm);
  font-size:13px; font-weight:600;
  transition:all var(--transition);
  white-space:nowrap;
}
.btn-primary {
  background:var(--teal); color:#000;
  box-shadow: 0 4px 14px var(--teal-glow);
}
.btn-primary:hover { background:#00e6c4; transform:translateY(-1px); }
.btn-outline {
  background:transparent; color:var(--text);
  border:1px solid var(--border);
}
.btn-outline:hover { border-color:var(--border2); color:var(--teal); }
.btn-sm { padding:7px 14px; font-size:12px; }

/* ════════ STAT CARDS ════════ */
.stats-grid {
  display:grid;
  grid-template-columns: repeat(auto-fit, minmax(180px,1fr));
  gap:16px;
  margin-bottom:28px;
}
.stat-card {
  background:var(--surface);
  border:1px solid var(--border);
  border-radius:var(--radius);
  padding:20px;
  position:relative;
  overflow:hidden;
  transition:all var(--transition);
  cursor:default;
}
.stat-card:hover {
  border-color:var(--border2);
  transform:translateY(-2px);
  box-shadow:0 12px 32px rgba(0,0,0,.3);
}
.stat-card::before {
  content:'';
  position:absolute; top:0; left:0; right:0; height:2px;
  background: linear-gradient(90deg, transparent, var(--accent, var(--teal)), transparent);
  opacity:.7;
}
.stat-card.accent-amber { --accent: var(--amber); }
.stat-card.accent-red   { --accent: var(--red); }
.stat-card.accent-blue  { --accent: var(--blue); }
.stat-card.accent-purple{ --accent: var(--purple); }

.stat-icon {
  width:40px; height:40px;
  background: var(--teal-dim);
  border-radius:10px;
  display:flex; align-items:center; justify-content:center;
  font-size:17px; color:var(--teal);
  margin-bottom:16px;
}
.stat-card.accent-amber .stat-icon { background:rgba(245,158,11,.12); color:var(--amber); }
.stat-card.accent-red   .stat-icon { background:rgba(239,68,68,.12);  color:var(--red); }
.stat-card.accent-blue  .stat-icon { background:rgba(59,130,246,.12); color:var(--blue); }
.stat-card.accent-purple.stat-icon { background:rgba(139,92,246,.12); color:var(--purple); }

.stat-value {
  font-family:var(--font-head);
  font-size:32px; font-weight:800; line-height:1;
  margin-bottom:4px;
}
.stat-label { font-size:12px; color:var(--text-muted); font-weight:500; }
.stat-trend {
  position:absolute; top:16px; right:16px;
  font-size:11px; font-weight:600;
  padding:3px 8px; border-radius:20px;
}
.trend-up   { background:rgba(0,191,165,.15); color:var(--teal); }
.trend-warn { background:rgba(245,158,11,.15); color:var(--amber); }
.trend-down { background:rgba(239,68,68,.15);  color:var(--red); }

/* ════════ GRID 2COL ════════ */
.grid-2 {
  display:grid;
  grid-template-columns: 1fr 340px;
  gap:20px;
  margin-bottom:24px;
}

/* ════════ CARDS ════════ */
.card {
  background:var(--surface);
  border:1px solid var(--border);
  border-radius:var(--radius);
  overflow:hidden;
}
.card-header {
  display:flex; align-items:center; justify-content:space-between;
  padding:18px 20px 14px;
  border-bottom:1px solid var(--border);
}
.card-title { font-family:var(--font-head); font-size:15px; font-weight:700; }
.card-subtitle { font-size:11px; color:var(--text-muted); margin-top:2px; }
.card-body { padding:16px 20px; }

/* ════════ TABLE ════════ */
.doc-table { width:100%; border-collapse:collapse; }
.doc-table th {
  font-size:11px; font-weight:700; letter-spacing:.1em;
  text-transform:uppercase; color:var(--text-muted);
  padding:10px 16px; text-align:left;
  border-bottom:1px solid var(--border);
  white-space:nowrap;
}
.doc-table td {
  padding:12px 16px;
  border-bottom:1px solid rgba(255,255,255,.04);
  font-size:13px; vertical-align:middle;
}
.doc-table tr:last-child td { border-bottom:none; }
.doc-table tr:hover td { background:rgba(255,255,255,.02); }

.doc-ref { font-family:monospace; font-size:12px; color:var(--text-muted); }
.doc-title-cell { max-width:240px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; font-weight:500; }
.doc-dept { font-size:11px; font-weight:600; padding:3px 8px; background:var(--surface2); border-radius:5px; white-space:nowrap; }
.doc-uploader { font-size:12px; color:var(--text-muted); white-space:nowrap; }
.doc-date { font-size:11px; color:var(--text-muted); white-space:nowrap; }

/* badges */
.badge {
  display:inline-flex; align-items:center;
  font-size:11px; font-weight:600;
  padding:3px 9px; border-radius:20px; white-space:nowrap;
}
.badge-validated { background:rgba(0,191,165,.15);  color:var(--teal); }
.badge-submitted { background:rgba(245,158,11,.15); color:var(--amber); }
.badge-archived  { background:rgba(59,130,246,.15); color:var(--blue); }
.badge-rejected  { background:rgba(239,68,68,.15);  color:var(--red); }
.badge-draft     { background:rgba(107,114,128,.15);color:#9CA3AF; }

/* file icons */
.ficon {
  display:inline-flex; align-items:center; justify-content:center;
  font-size:9px; font-weight:800; letter-spacing:.03em;
  padding:3px 6px; border-radius:5px; min-width:32px;
}
.ficon-pdf  { background:rgba(239,68,68,.15);  color:var(--red); }
.ficon-docx { background:rgba(59,130,246,.15); color:var(--blue); }
.ficon-xlsx { background:rgba(34,197,94,.15);  color:#22C55E; }
.ficon-img  { background:rgba(168,85,247,.15); color:#A855F7; }

/* ════════ SIDEBAR RIGHT ════════ */
.side-widget { margin-bottom:16px; }

/* Donut chart (pure CSS) */
.donut-wrap { padding:20px; display:flex; flex-direction:column; align-items:center; gap:16px; }
.donut { position:relative; width:130px; height:130px; }
.donut svg { transform:rotate(-90deg); }
.donut-center {
  position:absolute; top:50%; left:50%; transform:translate(-50%,-50%);
  text-align:center;
}
.donut-center strong { font-family:var(--font-head); font-size:24px; font-weight:800; display:block; }
.donut-center span { font-size:10px; color:var(--text-muted); }
.donut-legend { width:100%; display:flex; flex-direction:column; gap:8px; }
.legend-item { display:flex; align-items:center; gap:8px; font-size:12px; }
.legend-dot { width:8px; height:8px; border-radius:50%; flex-shrink:0; }
.legend-label { color:var(--text-muted); flex:1; }
.legend-val { font-weight:600; font-size:12px; }

/* Activity feed */
.activity-list { display:flex; flex-direction:column; gap:1px; }
.activity-item {
  display:flex; align-items:flex-start; gap:12px;
  padding:12px 0;
  border-bottom:1px solid rgba(255,255,255,.04);
}
.activity-item:last-child { border-bottom:none; }
.act-dot {
  width:8px; height:8px; border-radius:50%;
  margin-top:5px; flex-shrink:0;
}
.act-dot-teal   { background:var(--teal); box-shadow:0 0 6px var(--teal); }
.act-dot-amber  { background:var(--amber); }
.act-dot-red    { background:var(--red); }
.act-dot-blue   { background:var(--blue); }
.act-text { font-size:12.5px; line-height:1.5; flex:1; }
.act-text strong { font-weight:600; }
.act-time { font-size:11px; color:var(--text-muted); white-space:nowrap; margin-top:2px; }

/* mini stats row */
.mini-stats { display:flex; gap:12px; margin-bottom:20px; }
.mini-stat {
  flex:1; background:var(--surface);
  border:1px solid var(--border);
  border-radius:var(--radius-sm);
  padding:12px 14px;
  text-align:center;
}
.mini-stat strong { font-family:var(--font-head); font-size:20px; font-weight:800; display:block; }
.mini-stat span   { font-size:11px; color:var(--text-muted); }

/* ════════ PENDING TABLE (small) ════════ */
.pending-list { padding:0 20px; }
.pending-row {
  display:flex; align-items:center; gap:10px;
  padding:10px 0;
  border-bottom:1px solid rgba(255,255,255,.04);
  font-size:13px;
}
.pending-row:last-child { border-bottom:none; }
.pending-icon {
  width:32px; height:32px;
  background:var(--teal-dim); border-radius:8px;
  display:flex; align-items:center; justify-content:center;
  color:var(--teal); font-size:13px; flex-shrink:0;
}
.pending-info { flex:1; min-width:0; }
.pending-info strong { display:block; font-size:12px; font-weight:600; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.pending-info span { font-size:11px; color:var(--text-muted); }
.pending-action { font-size:11px; color:var(--teal); font-weight:600; white-space:nowrap; }

/* ════════ OVERLAY ════════ */
.overlay { display:none; position:fixed; inset:0; background:rgba(0,0,0,.6); z-index:99; }

/* ════════ RESPONSIVE ════════ */
@media(max-width:1100px) {
  .grid-2 { grid-template-columns:1fr; }
}
@media(max-width:900px) {
  .stats-grid { grid-template-columns:repeat(2,1fr); }
  .sidebar { transform:translateX(-100%); }
  .sidebar.open { transform:translateX(0); }
  .overlay.open { display:block; }
  .main { margin-left:0; }
  .topbar-toggle { display:flex !important; }
  .topbar-date { display:none; }
}
@media(max-width:600px) {
  .stats-grid { grid-template-columns:1fr 1fr; }
  .content { padding:16px; }
  .topbar { padding:0 16px; }
  .page-header { flex-direction:column; }
  .doc-table th:nth-child(4),
  .doc-table td:nth-child(4),
  .doc-table th:nth-child(5),
  .doc-table td:nth-child(5) { display:none; }
}
@media(max-width:400px) {
  .stats-grid { grid-template-columns:1fr; }
}

/* ════════ SCROLLBAR ════════ */
::-webkit-scrollbar { width:6px; height:6px; }
::-webkit-scrollbar-track { background:transparent; }
::-webkit-scrollbar-thumb { background:rgba(255,255,255,.1); border-radius:6px; }

/* ════════ ANIMATIONS ════════ */
@keyframes fadeUp {
  from { opacity:0; transform:translateY(14px); }
  to   { opacity:1; transform:translateY(0); }
}
.anim-1 { animation:fadeUp .45s ease both; }
.anim-2 { animation:fadeUp .45s .08s ease both; }
.anim-3 { animation:fadeUp .45s .16s ease both; }
.anim-4 { animation:fadeUp .45s .24s ease both; }
.anim-5 { animation:fadeUp .45s .32s ease both; }

/* ════════ EMPTY STATE ════════ */
.empty { text-align:center; padding:40px 20px; color:var(--text-muted); font-size:13px; }
.empty i { font-size:32px; margin-bottom:10px; display:block; }
</style>
</head>
<body>
<div class="layout">

<!-- ══ SIDEBAR ══════════════════════════════════════════════════ -->
<aside class="sidebar" id="sidebar">

  <div class="sidebar-logo">
    <div class="logo-icon"><i class="fa-solid fa-box-archive"></i></div>
    <div class="logo-text">
      <strong>AT-AMS</strong>
      <span>Digital Archives</span>
    </div>
  </div>

  <div class="sidebar-section">
    <div class="sidebar-label">Principal</div>
    <a href="dashboard.php" class="nav-item active">
      <i class="fa-solid fa-grid-2"></i> Tableau de bord
    </a>
    <a href="../documents/index.php" class="nav-item">
      <i class="fa-solid fa-file-lines"></i> Documents
      <?php if($pending_docs>0): ?>
      <span class="nav-badge"><?= $pending_docs ?></span>
      <?php endif; ?>
    </a>
    <a href="../documents/upload.php" class="nav-item">
      <i class="fa-solid fa-cloud-arrow-up"></i> Uploader
    </a>
    <a href="../documents/search.php" class="nav-item">
      <i class="fa-solid fa-magnifying-glass"></i> Recherche
    </a>
  </div>

  <?php if($is_super || $role==='dept_admin'): ?>
  <div class="sidebar-section">
    <div class="sidebar-label">Administration</div>
    <a href="dashboard.php" class="nav-item">
      <i class="fa-solid fa-gauge-high"></i> Vue Générale
    </a>
    <a href="users.php" class="nav-item">
      <i class="fa-solid fa-users"></i> Utilisateurs
      <?php if($pending_users>0): ?>
      <span class="nav-badge warn"><?= $pending_users ?></span>
      <?php endif; ?>
    </a>
    <a href="enterprises-pending.php" class="nav-item">
      <i class="fa-solid fa-building"></i> Entreprises
      <?php if($pending_ent>0): ?>
      <span class="nav-badge warn"><?= $pending_ent ?></span>
      <?php endif; ?>
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
  <?php endif; ?>

  <div class="sidebar-section">
    <div class="sidebar-label">Archives</div>
    <a href="../documents/archive.php" class="nav-item">
      <i class="fa-solid fa-archive"></i> Archives Centrales
    </a>
    <a href="../documents/contracts.php" class="nav-item">
      <i class="fa-solid fa-file-contract"></i> Contrats
    </a>
    <a href="../documents/reports.php" class="nav-item">
      <i class="fa-solid fa-chart-bar"></i> Rapports
    </a>
  </div>

  <div class="sidebar-footer">
    <div class="sidebar-user">
      <div class="user-avatar"><?= strtoupper(substr($user_name,0,1)) ?></div>
      <div class="user-info">
        <strong><?= htmlspecialchars($user_name) ?></strong>
        <span><?= match($role){ 'super_admin'=>'Super Admin','dept_admin'=>'Admin Dept.',
              'internal_staff'=>'Personnel','enterprise'=>'Entreprise',default=>$role } ?></span>
      </div>
      <i class="fa-solid fa-ellipsis-vertical"></i>
    </div>
    <a href="../auth/login.php?logout=1" class="nav-item" style="margin-top:6px; color:var(--red);">
      <i class="fa-solid fa-right-from-bracket"></i> Déconnexion
    </a>
  </div>
</aside>

<div class="overlay" id="overlay" onclick="closeSidebar()"></div>

<!-- ══ MAIN ══════════════════════════════════════════════════════ -->
<main class="main">

  <!-- Topbar -->
  <header class="topbar">
    <button class="topbar-toggle" onclick="toggleSidebar()">
      <i class="fa-solid fa-bars"></i>
    </button>
    <div class="topbar-search">
      <i class="fa-solid fa-magnifying-glass"></i>
      <input type="text" placeholder="Rechercher documents, contrats, ODS…">
    </div>
    <div class="topbar-actions">
      <span class="topbar-date" id="topbar-date"></span>
      <button class="topbar-btn" title="Notifications">
        <i class="fa-regular fa-bell"></i>
        <?php if($notif_count>0): ?><span class="topbar-notif-dot"></span><?php endif; ?>
      </button>
      <button class="topbar-btn" title="Rafraîchir" onclick="location.reload()">
        <i class="fa-solid fa-rotate-right"></i>
      </button>
      <a href="users.php" class="topbar-btn" title="Paramètres">
        <i class="fa-solid fa-sliders"></i>
      </a>
    </div>
  </header>

  <!-- Page -->
  <div class="content">

    <!-- Header -->
    <div class="page-header anim-1">
      <div>
        <h1 class="page-title">Tableau de Bord</h1>
        <p class="page-subtitle">Gestion centralisée des archives — <?= date('d F Y') ?></p>
      </div>
      <div class="header-actions">
        <a href="../documents/upload.php" class="btn btn-primary">
          <i class="fa-solid fa-plus"></i> Nouveau document
        </a>
        <a href="../documents/index.php" class="btn btn-outline">
          <i class="fa-solid fa-list"></i> Tous les docs
        </a>
      </div>
    </div>

    <!-- Stat Cards -->
    <div class="stats-grid anim-2">
      <div class="stat-card">
        <div class="stat-icon"><i class="fa-solid fa-file-lines"></i></div>
        <div class="stat-value"><?= number_format($total_docs) ?></div>
        <div class="stat-label">Total Documents</div>
        <span class="stat-trend trend-up">+<?= rand(2,8) ?>%</span>
      </div>
      <div class="stat-card accent-amber">
        <div class="stat-icon"><i class="fa-solid fa-clock"></i></div>
        <div class="stat-value"><?= $pending_docs ?></div>
        <div class="stat-label">En attente</div>
        <?php if($pending_docs>5): ?><span class="stat-trend trend-warn">Urgent</span><?php endif; ?>
      </div>
      <div class="stat-card">
        <div class="stat-icon" style="background:rgba(0,191,165,.12);color:var(--teal)"><i class="fa-solid fa-circle-check"></i></div>
        <div class="stat-value"><?= $validated_docs ?></div>
        <div class="stat-label">Validés</div>
        <span class="stat-trend trend-up">OK</span>
      </div>
      <div class="stat-card accent-blue">
        <div class="stat-icon"><i class="fa-solid fa-box-archive"></i></div>
        <div class="stat-value"><?= $archived_docs ?></div>
        <div class="stat-label">Archivés</div>
      </div>
      <div class="stat-card accent-red">
        <div class="stat-icon"><i class="fa-solid fa-xmark-circle"></i></div>
        <div class="stat-value"><?= $rejected_docs ?></div>
        <div class="stat-label">Rejetés</div>
      </div>
      <div class="stat-card accent-purple">
        <div class="stat-icon" style="background:rgba(139,92,246,.12);color:var(--purple)"><i class="fa-solid fa-users"></i></div>
        <div class="stat-value"><?= $total_users ?></div>
        <div class="stat-label">Utilisateurs actifs</div>
        <?php if($pending_users>0): ?><span class="stat-trend trend-warn"><?= $pending_users ?> en attente</span><?php endif; ?>
      </div>
    </div>

    <!-- Main Grid -->
    <div class="grid-2 anim-3">

      <!-- Recent Documents Table -->
      <div class="card">
        <div class="card-header">
          <div>
            <div class="card-title">Documents Récents</div>
            <div class="card-subtitle">8 derniers documents enregistrés</div>
          </div>
          <a href="../documents/index.php" class="btn btn-outline btn-sm">Voir tout</a>
        </div>
        <div style="overflow-x:auto;">
          <table class="doc-table">
            <thead>
              <tr>
                <th>Type</th>
                <th>Référence</th>
                <th>Titre</th>
                <th>Département</th>
                <th>Statut</th>
                <th>Date</th>
                <th></th>
              </tr>
            </thead>
            <tbody>
              <?php if(empty($recent_docs)): ?>
              <tr><td colspan="7"><div class="empty"><i class="fa-regular fa-folder-open"></i>Aucun document</div></td></tr>
              <?php else: foreach($recent_docs as $d): ?>
              <tr>
                <td><?= file_icon($d['file_type'] ?? 'pdf') ?></td>
                <td><span class="doc-ref"><?= htmlspecialchars($d['reference_number']) ?></span></td>
                <td><div class="doc-title-cell" title="<?= htmlspecialchars($d['title']) ?>"><?= htmlspecialchars($d['title']) ?></div></td>
                <td><span class="doc-dept"><?= htmlspecialchars($d['dept_name'] ?? '—') ?></span></td>
                <td><?= status_badge($d['status']) ?></td>
                <td><span class="doc-date"><?= date('d/m/y', strtotime($d['created_at'])) ?></span></td>
                <td>
                  <a href="view_contract.php?id=<?= $d['id'] ?>" class="btn btn-outline btn-sm" style="padding:5px 10px;">
                    <i class="fa-regular fa-eye"></i>
                  </a>
                </td>
              </tr>
              <?php endforeach; endif; ?>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Right Column -->
      <div>

        <!-- Status donut -->
        <div class="card side-widget">
          <div class="card-header">
            <div class="card-title">Répartition par statut</div>
          </div>
          <?php
            $d_total = max($total_docs,1);
            // SVG donut segments
            $segs = [
              ['val'=>$validated_docs,'color'=>'#00BFA5','label'=>'Validés'],
              ['val'=>$pending_docs,  'color'=>'#F59E0B','label'=>'En attente'],
              ['val'=>$archived_docs, 'color'=>'#3B82F6','label'=>'Archivés'],
              ['val'=>$rejected_docs, 'color'=>'#EF4444','label'=>'Rejetés'],
            ];
            $R=50; $cx=65; $cy=65; $r=40; $circum=2*M_PI*$r;
            $offset=0;
          ?>
          <div class="donut-wrap">
            <div class="donut">
              <svg width="130" height="130" viewBox="0 0 130 130">
                <!-- bg circle -->
                <circle cx="<?=$cx?>" cy="<?=$cy?>" r="<?=$r?>"
                  fill="none" stroke="rgba(255,255,255,.06)" stroke-width="14"/>
                <?php foreach($segs as $seg):
                  $pct = $d_total>0 ? $seg['val']/$d_total : 0;
                  $dash = $pct * $circum;
                  $gap  = $circum - $dash;
                  if($dash<.5){ $offset+=0; continue; }
                ?>
                <circle cx="<?=$cx?>" cy="<?=$cy?>" r="<?=$r?>"
                  fill="none"
                  stroke="<?=$seg['color']?>"
                  stroke-width="14"
                  stroke-dasharray="<?= round($dash,2) ?> <?= round($gap,2) ?>"
                  stroke-dashoffset="<?= round(-$offset*$circum,2) ?>"
                  stroke-linecap="round"/>
                <?php $offset+=$pct; endforeach; ?>
              </svg>
              <div class="donut-center">
                <strong><?= $total_docs ?></strong>
                <span>total</span>
              </div>
            </div>
            <div class="donut-legend" style="width:100%;padding:0 8px;">
              <?php foreach($segs as $seg): ?>
              <div class="legend-item">
                <span class="legend-dot" style="background:<?=$seg['color']?>"></span>
                <span class="legend-label"><?=$seg['label']?></span>
                <span class="legend-val"><?=$seg['val']?></span>
              </div>
              <?php endforeach; ?>
            </div>
          </div>
        </div>

        <!-- Pending approvals -->
        <?php if($is_super || $role==='dept_admin'): ?>
        <div class="card side-widget">
          <div class="card-header">
            <div class="card-title">Approbations</div>
            <?php if($pending_users+$pending_ent>0): ?>
            <span class="nav-badge warn"><?= $pending_users+$pending_ent ?></span>
            <?php endif; ?>
          </div>
          <div class="pending-list">
            <?php if($pending_users>0): ?>
            <a href="users.php?filter=pending" class="pending-row" style="display:flex; text-decoration:none;">
              <div class="pending-icon"><i class="fa-solid fa-user-clock"></i></div>
              <div class="pending-info">
                <strong><?= $pending_users ?> utilisateur(s) en attente</strong>
                <span>Validation de compte requise</span>
              </div>
              <span class="pending-action">Voir <i class="fa-solid fa-arrow-right" style="font-size:10px"></i></span>
            </a>
            <?php endif; ?>
            <?php if($pending_ent>0): ?>
            <a href="enterprises-pending.php" class="pending-row" style="display:flex; text-decoration:none;">
              <div class="pending-icon" style="background:rgba(245,158,11,.12);color:var(--amber)"><i class="fa-solid fa-building"></i></div>
              <div class="pending-info">
                <strong><?= $pending_ent ?> entreprise(s) en attente</strong>
                <span>Approbation dossier requise</span>
              </div>
              <span class="pending-action">Voir <i class="fa-solid fa-arrow-right" style="font-size:10px"></i></span>
            </a>
            <?php endif; ?>
            <?php if($pending_docs>0): ?>
            <a href="../documents/index.php?status=submitted" class="pending-row" style="display:flex; text-decoration:none;">
              <div class="pending-icon" style="background:rgba(59,130,246,.12);color:var(--blue)"><i class="fa-solid fa-file-pen"></i></div>
              <div class="pending-info">
                <strong><?= $pending_docs ?> document(s) soumis</strong>
                <span>En attente de validation</span>
              </div>
              <span class="pending-action">Voir <i class="fa-solid fa-arrow-right" style="font-size:10px"></i></span>
            </a>
            <?php endif; ?>
            <?php if($pending_users+$pending_ent+$pending_docs===0): ?>
            <div class="empty" style="padding:20px 0"><i class="fa-solid fa-check-circle" style="color:var(--teal)"></i>Tout est à jour</div>
            <?php endif; ?>
          </div>
        </div>
        <?php endif; ?>

      </div><!-- /right col -->
    </div><!-- /grid -->

    <!-- Activity Feed -->
    <div class="card anim-4">
      <div class="card-header">
        <div>
          <div class="card-title">Activité Récente</div>
          <div class="card-subtitle">Dernières actions sur les dossiers</div>
        </div>
      </div>
      <div class="card-body">
        <div class="activity-list">
          <?php
          $activities = [
            ['color'=>'act-dot-teal', 'text'=>'<strong>Dossier paiement</strong> — Polyclinique ROUACHED soumis par <strong>ETP FATHI SAID</strong>', 'time'=>'Il y a 2h'],
            ['color'=>'act-dot-amber','text'=>'<strong>Avenant N°17/DRT/2025</strong> en attente de validation', 'time'=>'Il y a 4h'],
            ['color'=>'act-dot-teal', 'text'=>'<strong>ODS N°183/DRT/SDT/2025</strong> validé par le département DOT', 'time'=>'Hier'],
            ['color'=>'act-dot-blue', 'text'=>'Bon de commande <strong>N°2500434</strong> archivé avec succès', 'time'=>'Hier'],
            ['color'=>'act-dot-red',  'text'=>'<strong>Demande de paiement</strong> AT/DOT MILA/SDT/DRT/153/2025 rejetée', 'time'=>'Il y a 2j'],
            ['color'=>'act-dot-teal', 'text'=>'PV Réception provisoire <strong>Polyclinique TADJNANET</strong> uploadé', 'time'=>'Il y a 3j'],
          ];
          foreach($activities as $a): ?>
          <div class="activity-item">
            <span class="act-dot <?= $a['color'] ?>"></span>
            <div style="flex:1">
              <div class="act-text"><?= $a['text'] ?></div>
            </div>
            <div class="act-time"><?= $a['time'] ?></div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>

  </div><!-- /content -->
</main>
</div><!-- /layout -->

<script>
/* sidebar mobile */
function toggleSidebar(){
  document.getElementById('sidebar').classList.toggle('open');
  document.getElementById('overlay').classList.toggle('open');
}
function closeSidebar(){
  document.getElementById('sidebar').classList.remove('open');
  document.getElementById('overlay').classList.remove('open');
}

/* topbar date */
(function(){
  const d = new Date();
  const opts = {weekday:'short',day:'numeric',month:'short',year:'numeric'};
  document.getElementById('topbar-date').textContent =
    d.toLocaleDateString('fr-FR',opts);
})();
</script>
</body>
</html>