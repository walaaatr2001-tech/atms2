<?php
require_once '../../includes/auth.php';
$pageTitle = 'Détails du document';
require_once '../../includes/header.php';

$doc_id = (int)$_GET['id'] ?? 0;
$user = getUser();

$sql = "SELECT d.*, u.first_name, u.last_name, dept.name as dept_name, cat.name as cat_name
        FROM documents d 
        LEFT JOIN users u ON d.uploaded_by = u.id 
        LEFT JOIN departments dept ON d.department_id = dept.id
        LEFT JOIN document_categories cat ON d.category_id = cat.id
        WHERE d.id = $doc_id";

$result = $conn->query($sql);
$doc = $result->fetch_assoc();

if (!$doc) {
    echo '<div class="p-8 text-center"><p class="text-red-600">Document non trouvé</p></div>';
    require_once '../../includes/footer.php';
    exit;
}

// Get versions
$versions = [];
$result = $conn->query("SELECT v.*, u.first_name, u.last_name 
    FROM document_versions v 
    LEFT JOIN users u ON v.uploaded_by = u.id 
    WHERE v.document_id = $doc_id 
    ORDER BY v.version_number DESC");
while ($row = $result->fetch_assoc()) {
    $versions[] = $row;
}
?>

<div class="max-w-4xl mx-auto">
    <div class="bg-white rounded-xl shadow-sm p-8">
        <div class="flex justify-between items-start mb-6">
            <div>
                <h2 class="text-2xl font-bold text-gray-800"><?php echo htmlspecialchars($doc['title']); ?></h2>
                <p class="text-at-blue font-medium mt-1">Référence: <?php echo htmlspecialchars($doc['reference_number']); ?></p>
            </div>
            <span class="px-4 py-2 rounded-full text-sm font-medium status-<?php echo $doc['status']; ?>">
                <?php echo ucfirst($doc['status']); ?>
            </span>
        </div>
        
        <div class="grid md:grid-cols-2 gap-6 mb-8">
            <div class="bg-gray-50 rounded-lg p-4">
                <p class="text-sm text-gray-500 mb-1">Département</p>
                <p class="font-medium text-gray-800"><?php echo htmlspecialchars($doc['dept_name']); ?></p>
            </div>
            <div class="bg-gray-50 rounded-lg p-4">
                <p class="text-sm text-gray-500 mb-1">Catégorie</p>
                <p class="font-medium text-gray-800"><?php echo htmlspecialchars($doc['cat_name'] ?? '-'); ?></p>
            </div>
            <div class="bg-gray-50 rounded-lg p-4">
                <p class="text-sm text-gray-500 mb-1">Téléversé par</p>
                <p class="font-medium text-gray-800"><?php echo htmlspecialchars($doc['first_name'] . ' ' . $doc['last_name']); ?></p>
            </div>
            <div class="bg-gray-50 rounded-lg p-4">
                <p class="text-sm text-gray-500 mb-1">Date</p>
                <p class="font-medium text-gray-800"><?php echo date('d/m/Y à H:i', strtotime($doc['created_at'])); ?></p>
            </div>
        </div>
        
        <?php if ($doc['description']): ?>
        <div class="mb-8">
            <p class="text-sm text-gray-500 mb-2">Description</p>
            <p class="text-gray-700"><?php echo nl2br(htmlspecialchars($doc['description'])); ?></p>
        </div>
        <?php endif; ?>
        
        <!-- File Info -->
        <div class="border-t pt-6">
            <h3 class="font-semibold text-gray-800 mb-4">Fichier</h3>
            <div class="flex items-center justify-between bg-blue-50 rounded-lg p-4">
                <div class="flex items-center gap-3">
                    <span class="text-3xl">📄</span>
                    <div>
                        <p class="font-medium text-gray-800"><?php echo strtoupper($doc['file_type']); ?></p>
                        <p class="text-sm text-gray-500"><?php echo round($doc['file_size'] / 1024, 2); ?> KB</p>
                    </div>
                </div>
                <?php if ($doc['file_path'] && file_exists($doc['file_path'])): ?>
                <a href="<?php echo $doc['file_path']; ?>" target="_blank" class="bg-at-blue text-white px-4 py-2 rounded-lg hover:bg-at-blue-dark">
                    Télécharger
                </a>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Actions for admin -->
        <?php if (in_array($user['role'], ['super_admin', 'dept_admin']) && $doc['status'] === 'submitted'): ?>
        <div class="border-t pt-6 mt-6">
            <h3 class="font-semibold text-gray-800 mb-4">Actions</h3>
            <div class="flex gap-4">
                <button onclick="updateStatus(<?php echo $doc['id']; ?>, 'validated')" 
                    class="bg-green-600 text-white px-6 py-2 rounded-lg hover:bg-green-700">
                    Valider le document
                </button>
                <button onclick="updateStatus(<?php echo $doc['id']; ?>, 'rejected')" 
                    class="bg-red-600 text-white px-6 py-2 rounded-lg hover:bg-red-700">
                    Rejeter le document
                </button>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Versions -->
        <?php if (count($versions) > 0): ?>
        <div class="border-t pt-6 mt-6">
            <h3 class="font-semibold text-gray-800 mb-4">Historique des versions</h3>
            <div class="space-y-3">
                <?php foreach ($versions as $v): ?>
                <div class="flex items-center justify-between bg-gray-50 rounded-lg p-3">
                    <div>
                        <span class="font-medium">Version <?php echo $v['version_number']; ?></span>
                        <span class="text-sm text-gray-500">par <?php echo htmlspecialchars($v['first_name'] . ' ' . $v['last_name']); ?></span>
                    </div>
                    <span class="text-sm text-gray-500"><?php echo date('d/m/Y', strtotime($v['created_at'])); ?></span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
        
        <div class="mt-6">
            <a href="list.php" class="text-at-blue hover:underline">← Retour à la liste</a>
        </div>
    </div>
</div>

<script>
function updateStatus(docId, status) {
    if (confirm('Êtes-vous sûr de vouloir ' + (status === 'validated' ? 'valider' : 'rejeter') + ' ce document?')) {
        window.location.href = '../../controllers/update_status.php?id=' + docId + '&status=' + status;
    }
}
</script>

<?php require_once '../../includes/footer.php'; ?>