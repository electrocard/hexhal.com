<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Vérifier que l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    echo "Vous n'êtes pas connecté(e).";
    exit;
}

$current_user_id = $_SESSION['user_id'];

// Charger la configuration de la base de données depuis le fichier JSON
$configFile = __DIR__ . '/hexhal_info.json';
if (!file_exists($configFile)) {
    echo "Fichier de configuration introuvable.";
    exit;
}

$config = json_decode(file_get_contents($configFile), true);
$host = $config['server_address'];
$port = $config['port'];
$dbUser = $config['user'];
$dbPass = $config['password'];
$dbName = $config['database'];

try {
    // Connexion à la base de données
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$dbName", $dbUser, $dbPass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Récupérer la valeur du champ 'root' pour l'utilisateur connecté
    $stmt = $pdo->prepare("SELECT root FROM users WHERE user_id = :user_id");
    $stmt->execute(['user_id' => $current_user_id]);
    $userData = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$userData) {
        echo "Utilisateur introuvable.";
        exit;
    }

    // Vérifier si l'utilisateur est root (on force la conversion en entier pour être sûr)
    if ((int)$userData['root'] === 1) {
        echo "Vous êtes root.";
    } else {
        echo "Vous n'êtes pas root.";
    }
} catch (PDOException $e) {
    echo "Erreur de base de données : " . $e->getMessage();
}
?>
