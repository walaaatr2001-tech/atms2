<?php
require_once '../../config/config.php';

// ============================================================
// CSRF CHECK
// ============================================================
if (!validateCSRF($_POST['csrf_token'] ?? '')) {
    header("Location: register.php?error=" . urlencode("Erreur de sécurité. Veuillez réessayer."));
    exit;
}

// ============================================================
// COLLECT & CLEAN ALL FIELDS
// ============================================================
$first_name  = trim($_POST['first_name']  ?? '');
$last_name   = trim($_POST['last_name']   ?? '');
$username    = trim($_POST['username']    ?? '');
$email       = trim($_POST['email']       ?? '');
$phone       = trim($_POST['phone']       ?? '');
$password    = $_POST['password']         ?? '';
$confirm_pwd = $_POST['confirm_password'] ?? '';

$nif         = trim($_POST['nif']         ?? '');
$rc          = trim($_POST['rc']          ?? '');
$rib         = trim($_POST['rib']         ?? '');
$bank_name   = trim($_POST['bank_name']   ?? '');
$bank_other  = trim($_POST['bank_other']  ?? '');

$wilaya_id   = (int)($_POST['wilaya_id']  ?? 0);  // FIX: cast to int immediately
$city_id     = (int)($_POST['city_id']    ?? 0);  // FIX: cast to int (FK to cities.id)
$address     = trim($_POST['address']     ?? '');

// ============================================================
// SERVER-SIDE VALIDATION
// ============================================================

$required = [
    'first_name' => $first_name,
    'last_name'  => $last_name,
    'username'   => $username,
    'email'      => $email,
    'phone'      => $phone,
    'password'   => $password,
    'nif'        => $nif,
    'rc'         => $rc,
    'rib'        => $rib,
    'bank_name'  => $bank_name,
    'address'    => $address,
];

foreach ($required as $field => $value) {
    if ($value === '') {
        header("Location: register.php?error=" . urlencode("Tous les champs obligatoires doivent être remplis. (Champ manquant: $field)"));
        exit;
    }
}

// FIX: validate wilaya_id and city_id as integers > 0
if ($wilaya_id < 1 || $wilaya_id > 58) {
    header("Location: register.php?error=" . urlencode("Wilaya invalide."));
    exit;
}

if ($city_id <= 0) {
    header("Location: register.php?error=" . urlencode("Veuillez sélectionner une ville valide."));
    exit;
}

// If bank is "Autre", bank_other must be filled
if ($bank_name === 'Autre' && $bank_other === '') {
    header("Location: register.php?error=" . urlencode("Veuillez préciser le nom de votre banque."));
    exit;
}

// Email format
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header("Location: register.php?error=" . urlencode("Adresse email invalide."));
    exit;
}

// Phone: +213 or 0 followed by 9 digits
if (!preg_match('/^(\+213|0)[0-9]{9}$/', $phone)) {
    header("Location: register.php?error=" . urlencode("Format téléphone invalide. Utilisez 0550XXXXXX ou +213XXXXXXXXX"));
    exit;
}

// RIB: exactly 20 digits
if (!preg_match('/^[0-9]{20}$/', $rib)) {
    header("Location: register.php?error=" . urlencode("Le RIB doit contenir exactement 20 chiffres numeriques."));
    exit;
}

// Password match
if ($password !== $confirm_pwd) {
    header("Location: register.php?error=" . urlencode("Les mots de passe ne correspondent pas."));
    exit;
}

// Password minimum length
if (strlen($password) < 6) {
    header("Location: register.php?error=" . urlencode("Le mot de passe doit contenir au moins 6 caractères."));
    exit;
}

// ============================================================
// DATABASE CONNECTION
// ============================================================
$conn = getDB();

// ============================================================
// FIX: VERIFY city_id actually belongs to the selected wilaya_id
// This prevents someone submitting a city from a different wilaya
// ============================================================
$stmt = $conn->prepare("SELECT id FROM cities WHERE id = ? AND wilaya_id = ?");
$stmt->bind_param("ii", $city_id, $wilaya_id);
$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows === 0) {
    $stmt->close();
    header("Location: register.php?error=" . urlencode("La ville sélectionnée ne correspond pas à la wilaya."));
    exit;
}
$stmt->close();

// ============================================================
// CHECK USERNAME AND EMAIL UNIQUENESS
// ============================================================
$check = $conn->query("SELECT id FROM users WHERE username = '" . $conn->real_escape_string($username) . "' OR email = '" . $conn->real_escape_string($email) . "' LIMIT 1");

if ($check && $check->num_rows > 0) {
    header("Location: register.php?error=" . urlencode("Ce nom d'utilisateur ou cet email est déjà utilisé."));
    exit;
}

// ============================================================
// INSERT INTO users TABLE
// ============================================================
$fn = $conn->real_escape_string($first_name);
$ln = $conn->real_escape_string($last_name);
$un = $conn->real_escape_string($username);
$em = $conn->real_escape_string($email);
$ph = $conn->real_escape_string($phone);
$pw = password_hash($password, PASSWORD_DEFAULT);

$sql_user = "INSERT INTO users (first_name, last_name, username, email, phone, password, role, status)
             VALUES ('$fn', '$ln', '$un', '$em', '$ph', '$pw', 'enterprise', 'pending')";

if (!$conn->query($sql_user)) {
    header("Location: register.php?error=" . urlencode("Erreur lors de la création du compte. Veuillez réessayer."));
    exit;
}

$user_id = $conn->insert_id;

// ============================================================
// ADD USER TO user_roles TABLE (RBAC)
// ============================================================
$role_check = $conn->query("SELECT id FROM roles WHERE name = 'enterprise'");
if ($role_check && $role_check->num_rows > 0) {
    $role_row = $role_check->fetch_assoc();
    $role_id = $role_row['id'];
    $conn->query("INSERT INTO user_roles (user_id, role_id) VALUES ($user_id, $role_id)");
}

// ============================================================
// INSERT INTO enterprises TABLE
// ============================================================
$e_nif   = $conn->real_escape_string($nif);
$e_rc    = $conn->real_escape_string($rc);
$e_rib   = $conn->real_escape_string($rib);
$e_bank  = $conn->real_escape_string($bank_name);
$e_other = $conn->real_escape_string($bank_other);
$e_addr  = $conn->real_escape_string($address);
// FIX: $wilaya_id and $city_id are already safe integers — no quotes in SQL

$sql_ent = "INSERT INTO enterprises (user_id, nif, rc, rib, bank_name, bank_other, wilaya_id, city_id, address, status)
            VALUES ($user_id, '$e_nif', '$e_rc', '$e_rib', '$e_bank', '$e_other', $wilaya_id, $city_id, '$e_addr', 'pending')";

if (!$conn->query($sql_ent)) {
    $conn->query("DELETE FROM users WHERE id = $user_id");
    header("Location: register.php?error=" . urlencode("Erreur lors de l'enregistrement de l'entreprise. Veuillez réessayer."));
    exit;
}

// ============================================================
// LOG IN audit_logs
// ============================================================
$ip      = $conn->real_escape_string($_SERVER['REMOTE_ADDR'] ?? '');
$details = $conn->real_escape_string(json_encode(['username' => $username, 'email' => $email]));
$conn->query("INSERT INTO audit_logs (user_id, action, entity_type, entity_id, ip_address, details_json)
              VALUES ($user_id, 'register', 'user', $user_id, '$ip', '$details')");

$conn->close();

// ============================================================
// SUCCESS
// ============================================================
header("Location: login.php?registered=1");
exit;
?>