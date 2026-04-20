<?php
require_once '../../includes/auth.php';

$user = getUser();
if (!isset($_SESSION['user_role']) || !in_array($_SESSION['user_role'], ['super_admin', 'dept_admin'])) {
    header('Location: ../../index.php?error=unauthorized');
    exit;
}
$pageTitle = 'Entreprises en attente';
require_once '../../includes/header.php';

// Handle approve/reject
if (isset($_GET['action']) && isset($_GET['id'])) {
    $ent_id = (int)$_GET['id'];
    $action = $_GET['action'];
    
    if (in_array($action, ['approve', 'reject'])) {
        $new_status = ($action === 'approve') ? 'approved' : 'rejected';
        $user_id = $user['id'];
        $sql = "UPDATE enterprises SET status = '$new_status', approved_by = $user_id, approved_at = NOW() WHERE id = $ent_id";
        if ($conn->query($sql)) {
            logAction($action . '_enterprise', 'enterprise', $ent_id);
            $success = 'Entreprise ' . ($action === 'approve' ? 'approuvée' : 'rejetée');
        }
    }
}

// Get enterprises
$enterprises = [];
$sql = "SELECT e.*, u.first_name, u.last_name, u.email, u.phone, w.name_fr as wilaya, c.name_fr as city
        FROM enterprises e
        JOIN users u ON e.user_id = u.id
        LEFT JOIN wilayas w ON e.wilaya_id = w.id
        LEFT JOIN cities c ON e.city_id = c.id
        ORDER BY e.created_at DESC";
$result = $conn->query($sql);
while ($row = $result->fetch_assoc()) {
    $enterprises[] = $row;
}
?>

<div class="bg-white rounded-xl shadow-sm overflow-hidden">
    <table class="w-full text-sm text-left">
        <thead class="bg-gray-50 text-gray-600 uppercase text-xs">
            <tr>
                <th class="px-6 py-3">Entreprise</th>
                <th class="px-6 py-3">Contact</th>
                <th class="px-6 py-3">NIF</th>
                <th class="px-6 py-3">RC</th>
                <th class="px-6 py-3">Wilaya</th>
                <th class="px-6 py-3">Statut</th>
                <th class="px-6 py-3">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (count($enterprises) > 0): ?>
                <?php foreach ($enterprises as $ent): ?>
                <tr class="border-b border-gray-100 hover:bg-gray-50">
                    <td class="px-6 py-4 font-medium"><?php echo htmlspecialchars($ent['first_name'] . ' ' . $ent['last_name']); ?></td>
                    <td class="px-6 py-4">
                        <div><?php echo htmlspecialchars($ent['email']); ?></div>
                        <div class="text-xs text-gray-500"><?php echo htmlspecialchars($ent['phone'] ?? ''); ?></div>
                    </td>
                    <td class="px-6 py-4"><?php echo htmlspecialchars($ent['nif'] ?? '-'); ?></td>
                    <td class="px-6 py-4"><?php echo htmlspecialchars($ent['rc'] ?? '-'); ?></td>
                    <td class="px-6 py-4"><?php echo htmlspecialchars($ent['wilaya'] ?? '-'); ?></td>
                    <td class="px-6 py-4">
                        <span class="px-2 py-1 rounded text-xs font-medium 
                            <?php echo $ent['status'] === 'approved' ? 'bg-green-100 text-green-700' : 
                                ($ent['status'] === 'pending' ? 'bg-yellow-100 text-yellow-700' : 'bg-red-100 text-red-700'); ?>">
                            <?php echo ucfirst($ent['status']); ?>
                        </span>
                    </td>
                    <td class="px-6 py-4">
                        <?php if ($ent['status'] === 'pending'): ?>
                        <a href="?action=approve&id=<?php echo $ent['id']; ?>" class="text-green-600 hover:underline mr-2" onclick="return confirm('Approuver cette entreprise?')">Approuver</a>
                        <a href="?action=reject&id=<?php echo $ent['id']; ?>" class="text-red-600 hover:underline" onclick="return confirm('Rejeter cette entreprise?')">Rejeter</a>
                        <?php else: ?>
                        <span class="text-gray-500 text-xs">
                            <?php echo $ent['status'] === 'approved' && $ent['approved_at'] ? 'Approuvé le ' . date('d/m/Y', strtotime($ent['approved_at'])) : 'Rejeté'; ?>
                        </span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="7" class="px-6 py-12 text-center">
                        <div class="text-6xl mb-4">🏢</div>
                        <p class="text-gray-500">Aucune entreprise trouvée</p>
                    </td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php require_once '../../includes/footer.php'; ?>