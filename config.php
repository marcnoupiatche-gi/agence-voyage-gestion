<?php
// Configuration de la base de données
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'touristique_exp_bd');

// Connexion à la base de données
function getDBConnection() {
    try {
        $conn = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $conn->exec("SET NAMES utf8");
        return $conn;
    } catch(PDOException $e) {
        die("Erreur de connexion : " . $e->getMessage());
    }
}

// Démarrer la session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Fonction pour vérifier si l'utilisateur est connecté
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Fonction pour vérifier si l'utilisateur est admin
function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

// Fonction pour rediriger si non connecté
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit();
    }
}

// Fonction pour générer un numéro unique
function generateUniqueNumber($prefix) {
    return $prefix . date('Ymd') . rand(1000, 9999);
}

// Constantes de l'application
define('APP_NAME', 'Touristique Express');
define('APP_EMAIL', 'contact@touristique-express.com');
define('APP_PHONE', '+237 690 000 000');
define('APP_ADDRESS', 'Yaoundé, Cameroun');
?>