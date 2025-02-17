<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');

// Vérifier si l'utilisateur est authentifié
if (!isset($_SESSION['user_id']) || !isset($_SESSION['auth_token'])) {
    echo json_encode(['status' => 'error', 'message' => 'Accès interdit : veuillez vous connecter.']);
    exit;
}

// Charger les configurations de la base de données
$config = json_decode(file_get_contents('hexhal_info.json'), true);
$host = $config['server_address'];
$port = $config['port'];
$user = $config['user'];
$pass = $config['password'];
$db = $config['database'];

try {
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$db", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Vérifier le token d'authentification
    $stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = :user_id AND auth_token = :auth_token");
    $stmt->execute(['user_id' => $_SESSION['user_id'], 'auth_token' => $_SESSION['auth_token']]);
    $user = $stmt->fetch();

    if (!$user) {
        echo json_encode(['status' => 'error', 'message' => 'Token invalide ou expiré.']);
        exit;
    }

    // Vérifier si un fichier a été uploadé
    if (!isset($_FILES['file'])) {
        echo json_encode(['status' => 'error', 'message' => 'Aucun fichier n\'a été uploadé.']);
        exit;
    }

    $uploadDir = "../society/{$user['society']}/{$user['username']}/";
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    $filename = basename($_FILES['file']['name']);
    $targetFile = $uploadDir . $filename;

    if (move_uploaded_file($_FILES['file']['tmp_name'], $targetFile)) {
        echo json_encode(['status' => 'success', 'message' => 'Fichier uploadé avec succès.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Erreur lors de l\'upload du fichier.']);
    }

} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Erreur de base de données: ' . $e->getMessage()]);
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => 'Erreur inattendue: ' . $e->getMessage()]);
}
