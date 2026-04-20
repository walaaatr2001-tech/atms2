<?php
require_once '../../includes/auth.php';
$pageTitle = 'Notifications';
require_once '../../includes/header.php';

$user = getUser();
$conn = getDB();
$user_id = $_SESSION['user_id'];

if (isset($_GET['mark_read'])) {
    $notif_id = intval($_GET['mark_read']);
    $conn->query("UPDATE notifications SET is_read = 1 WHERE id = $notif_id AND user_id = $user_id");
    header('Location: notifications.php');
    exit;
}

if (isset($_GET['mark_all'])) {
    $conn->query("UPDATE notifications SET is_read = 1 WHERE user_id = $user_id");
    header('Location: notifications.php');
    exit;
}

$filter = $_GET['filter'] ?? 'all';
$where = "user_id = $user_id";
if ($filter === 'unread') {
    $where .= " AND is_read = 0";
}

$notifications = [];
$result = $conn->query("SELECT * FROM notifications WHERE $where ORDER BY created_at DESC LIMIT 50");
while ($row = $result->fetch_assoc()) {
    $notifications[] = $row;
}

$unread_count = 0;
$result = $conn->query("SELECT COUNT(*) as cnt FROM notifications WHERE user_id = $user_id AND is_read = 0");
if ($row = $result->fetch_assoc()) {
    $unread_count = $row['cnt'];
}
?>
<style>
:root { --bg: #0b0f0e; --surface: #111615; --surface2: #161d1b; --border: rgba(255,255,255,.07); --border2: rgba(0,191,165,.18); --teal: #00BFA5; --teal-dim: rgba(0,191,165,.12); --teal-glow: rgba(0,191,165,.25); --amber: #F59E0B; --red: #EF4444; --blue: #3B82F6; --text: #E8EDEC; --text-muted: #6B7A78; --text-dim: #9AAFAD; --radius: 14px; --radius-sm: 8px; }
.page { padding: 28px; }
.page-header { display:flex; align-items:flex-start; justify-content:space-between; margin-bottom:28px; }
.page-title { font-family:'Syne',sans-serif; font-size:26px; font-weight:800; color:var(--text); }
.page-subtitle { font-size:13px; color:var(--text-muted); margin-top:4px; }
.btn { display:inline-flex; align-items:center; gap:8px; padding:8px 16px; border-radius:var(--radius-sm); font-size:12px; font-weight:600; cursor:pointer; transition:all .2s; text-decoration:none; }
.btn-outline { background:transparent; color:var(--text); border:1px solid var(--border); }
.btn-outline:hover { border-color:var(--teal); color:var(--teal); }
.tabs { display:flex; gap:8px; margin-bottom:20px; }
.tab { padding:8px 16px; border-radius:var(--radius-sm); font-size:13px; color:var(--text-muted); background:transparent; border:none; cursor:pointer; }
.tab:hover { color:var(--text); }
.tab.active { background:var(--teal-dim); color:var(--teal); }
.badge-count { background:var(--teal); color:#000; font-size:10px; font-weight:700; padding:2px 6px; border-radius:10px; margin-left:6px; }
.notif-list { display:flex; flex-direction:column; gap:8px; }
.notif-item { display:flex; gap:16px; padding:16px; background:var(--surface); border:1px solid var(--border); border-radius:var(--radius); transition:all .2s; }
.notif-item:hover { border-color:var(--border2); }
.notif-item.unread { border-left:3px solid var(--teal); }
.notif-icon { width:40px; height:40px; border-radius:10px; display:flex; align-items:center; justify-content:center; font-size:16px; flex-shrink:0; }
.notif-icon.info { background:var(--teal-dim); color:var(--teal); }
.notif-icon.success { background:rgba(34,197,94,.15); color:#22C55E; }
.notif-icon.warning { background:rgba(245,158,11,.15); color:var(--amber); }
.notif-icon.error { background:rgba(239,68,68,.15); color:var(--red); }
.notif-content { flex:1; }
.notif-title { font-size:14px; font-weight:600; color:var(--text); margin-bottom:4px; }
.notif-text { font-size:13px; color:var(--text-dim); }
.notif-time { font-size:11px; color:var(--text-muted); margin-top:8px; }
.notif-actions { display:flex; flex-direction:column; gap:8px; }
.empty { text-align:center; padding:60px 20px; color:var(--text-muted); }
.empty i { font-size:48px; opacity:.5; margin-bottom:16px; }
</style>

<div class="page">
    <div class="page-header">
        <div>
            <h1 class="page-title">Notifications</h1>
            <p class="page-subtitle"><?= $unread_count ?> notification(s) non lue(s)</p>
        </div>
        <?php if ($unread_count > 0): ?>
        <a href="?mark_all=1" class="btn btn-outline">
            <i class="fa-solid fa-check-double"></i> Tout marquer comme lu
        </a>
        <?php endif; ?>
    </div>

    <div class="tabs">
        <a href="?filter=all" class="tab <?= $filter === 'all' ? 'active' : '' ?>">Tout</a>
        <a href="?filter=unread" class="tab <?= $filter === 'unread' ? 'active' : '' ?>">
            Non lues <?php if ($unread_count > 0): ?><span class="badge-count"><?= $unread_count ?></span><?php endif; ?>
        </a>
    </div>

    <?php if (count($notifications) > 0): ?>
    <div class="notif-list">
        <?php foreach ($notifications as $notif): ?>
        <?php
        $icon_class = match($notif['type'] ?? 'info') {
            'success' => 'success',
            'warning' => 'warning',
            'error' => 'error',
            default => 'info'
        };
        $icon = match($notif['type'] ?? 'info') {
            'success' => 'fa-check',
            'warning' => 'fa-triangle-exclamation',
            'error' => 'fa-xmark',
            default => 'fa-info'
        };
        ?>
        <div class="notif-item <?= $notif['is_read'] ? '' : 'unread' ?>">
            <div class="notif-icon <?= $icon_class ?>"><i class="fa-solid <?= $icon ?>"></i></div>
            <div class="notif-content">
                <div class="notif-title"><?= htmlspecialchars($notif['message'] ?? 'Notification') ?></div>
                <div class="notif-time"><?= date('d/m/Y à H:i', strtotime($notif['created_at'])) ?></div>
            </div>
            <?php if (!$notif['is_read']): ?>
            <div class="notif-actions">
                <a href="?mark_read=<?= $notif['id'] ?>" class="btn btn-outline btn-sm">Marquer lu</a>
            </div>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>
    <?php else: ?>
    <div class="empty">
        <i class="fa-regular fa-bell"></i>
        <p>Aucune notification</p>
    </div>
    <?php endif; ?>
</div>
<?php require_once '../../includes/footer.php'; ?>