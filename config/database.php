<?php
/**
 * Database Connection - MySQLi
 */

if (!defined('DB_CONNECTED')) {
    define('DB_HOST', 'localhost');
    define('DB_NAME', 'at_ams');
    define('DB_USER', 'root');
    define('DB_PASS', '');

    try {
        $mysqli = @new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        
        if ($mysqli->connect_error) {
            if ($mysqli->connect_errno === 1045) {
                die("<h3>Erreur de connexion</h3><p>Mot de passe MySQL incorrect. Veuillez vérifier le mot de passe dans config/database.php</p>");
            } else {
                die("<h3>Base de données inaccessible</h3><p>La base de données '$DB_NAME' n'existe pas ou MySQL n'est pas démarré.</p><p>Veuillez:</p><ol><li>Démarrer MySQL dans XAMPP Control Panel</li><li>Créer la base de données '$DB_NAME' dans phpMyAdmin</li><li>Importer le fichier sql/schema.sql</li></ol>");
            }
        }

        $mysqli->set_charset("utf8mb4");
        $conn = $mysqli;
        define('DB_CONNECTED', true);
    } catch (Exception $e) {
        die("<h3>Erreur de connexion MySQL</h3><p>" . $e->getMessage() . "</p><p>Assurez-vous que MySQL est démarré dans XAMPP.</p>");
    }
}

function getDB() {
    global $conn;
    return $conn;
}