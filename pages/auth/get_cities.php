<?php
require_once '../../config/config.php';

$wilaya_id = isset($_GET['wilaya_id']) ? (int)$_GET['wilaya_id'] : 0;

if ($wilaya_id <= 0) {
    echo json_encode([]);
    exit;
}

$conn = getDB();
$result = $conn->query("SELECT id, name_fr FROM cities WHERE wilaya_id = $wilaya_id ORDER BY name_fr");

$cities = [];
while ($row = $result->fetch_assoc()) {
    $cities[] = ['id' => (int)$row['id'], 'name' => $row['name_fr']];
}

header('Content-Type: application/json');
echo json_encode($cities);
?>