<?php
require_once '../../includes/auth.php';
$pageTitle = 'Versions du document';
require_once '../../includes/header.php';

$doc_id = (int)$_GET['id'] ?? 0;

$sql = "SELECT * FROM documents WHERE id = $doc_id";
$result = $conn->query($sql);
$doc = $result->fetch_assoc();

if (!$doc) {
    echo '<div class="p-8 text-center"><p class="text-red-600">Document non trouvé</p></div>';
    require_once '../../includes/footer.php';
    exit;
}

// Get all versions
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
        <div class="mb-6">
            <h2 class="text-2xl font-bold text-gray-800">Versions du document</h2>
            <p class="text-at-blue"><?php echo htmlspecialchars($doc['reference_number']); ?> - <?php echo htmlspecialchars($doc['title']); ?></p>
        </div>
        
        <?php if (count($versions) > 0): ?>
        <div class="space-y-4">
            <?php foreach ($versions as $v): ?>
            <div class="flex items-center justify-between bg-gray-50 rounded-lg p-4">
                <div class="flex items-center gap-4">
                    <div class="w-10 h-10 bg-at-blue text-white rounded-full flex items-center justify-center font-bold">
                        v<?php echo $v['version_number']; ?>
                    </div>
                    <div>
                        <p class="font-medium text-gray-800">Version <?php echo $v['version_number']; ?></p>
                        <p class="text-sm text-gray-500">Par <?php echo htmlspecialchars($v['first_name'] . ' ' . $v['last_name']); ?></p>
                    </div>
                </div>
                <div class="flex items-center gap-4">
                    <span class="text-sm text-gray-500"><?php echo date('d/m/Y H:i', strtotime($v['created_at'])); ?></span>
                    <?php if ($v['file_path']): ?>
                    <a href="<?php echo $v['file_path']; ?>" target="_blank" class="text-at-blue hover:underline">Télécharger</a>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div class="text-center py-12">
            <div class="text-6xl mb-4">📋</div>
            <p class="text-gray-500">Aucune version trouvée</p>
        </div>
        <?php endif; ?>
        
        <div class="mt-6">
            <a href="detail.php?id=<?php echo $doc_id; ?>" class="text-at-blue hover:underline">← Retour au document</a>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>