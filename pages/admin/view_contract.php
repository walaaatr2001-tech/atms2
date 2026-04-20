<?php
session_start();
require_once '../../config/database.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['super_admin', 'dept_admin'])) {
    header("Location: ../../pages/auth/login.php");
    exit;
}

$contract_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

$stmt = $conn->prepare("SELECT * FROM contracts WHERE id = ?");
$stmt->bind_param("i", $contract_id);
$stmt->execute();
$contract = $stmt->get_result()->fetch_assoc();

if (!$contract) {
    die("Contrat non trouvé !");
}

require_once '../../includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php require_once '../../includes/sidebar.php'; ?>

        <div class="col-md-10 p-4">
            <h2>Contrat N° <?= htmlspecialchars($contract['contract_number']) ?></h2>
            <p><strong>Type :</strong> <?= htmlspecialchars($contract['contract_type']) ?></p>

            <h4 class="mt-4">📄 Documents liés à ce contrat</h4>
            <table class="table table-bordered">
                <thead class="table-dark">
                    <tr><th>Nom du fichier</th><th>Date</th><th>Actions</th></tr>
                </thead>
                <tbody>
                    <?php
                    $docs = $conn->prepare("SELECT * FROM documents WHERE contract_id = ? ORDER BY uploaded_at DESC");
                    $docs->bind_param("i", $contract_id);
                    $docs->execute();
                    $result = $docs->get_result();
                    while ($doc = $result->fetch_assoc()):
                    ?>
                    <tr>
                        <td><?= htmlspecialchars($doc['filename'] ?? basename($doc['file_path'])) ?></td>
                        <td><?= $doc['uploaded_at'] ?></td>
                        <td><a href="../../<?= $doc['file_path'] ?>" target="_blank" class="btn btn-sm btn-primary">Ouvrir</a></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>

            <a href="dashboard.php" class="btn btn-secondary">← Retour au tableau de bord</a>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>