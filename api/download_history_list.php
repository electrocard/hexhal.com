<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');



logError("Début du script download_history_list.php");

// Récupérer le token d'authentification de l'en-tête
$headers = getallheaders();
$auth_header = isset($headers['Authorization']) ? $headers['Authorization'] : '';
$token = str_replace('Bearer ', '', $auth_header);

logError("Token reçu : " . $token);

if (empty($token)) {
    http_response_code(401);
    echo json_encode(['error' => 'Token d\'authentification manquant']);
    logError("Token d'authentification manquant");
    exit;
}

// Charger les configurations de la base de données
$config = json_decode(file_get_contents('hexhal_info.json'), true);

try {
    $pdo = new PDO("mysql:host={$config['server_address']};port={$config['port']};dbname={$config['database']}", $config['user'], $config['password']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    logError("Connexion à la base de données établie");

    // Vérifier si le token est valide
    $stmt = $pdo->prepare("SELECT user_id, society FROM users WHERE auth_token = :token");
    $stmt->execute(['token' => $token]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        http_response_code(401);
        echo json_encode(['error' => 'Token invalide']);
        logError("Token invalide");
        exit;
    }

    logError("Utilisateur authentifié : " . $user['user_id']);

    $user_id = $user['user_id'];
    $society = $user['society'];

    // Construire le chemin du fichier
    $file_path = "../society/$society/$user_id/historylist.json";  // Changer le nom du fichier ici
    logError("Chemin du fichier : " . $file_path);

    // Vérifier si le fichier existe
    if (!file_exists($file_path)) {
        http_response_code(404);
        echo json_encode(['error' => 'Fichier non trouvé']);
        logError("Fichier non trouvé : " . $file_path);
        exit;
    }

    // Lire et envoyer le contenu du fichier
    $file_content = file_get_contents($file_path);
    if ($file_content === false) {
        http_response_code(500);
        echo json_encode(['error' => 'Erreur lors de la lecture du fichier']);
        logError("Erreur lors de la lecture du fichier : " . $file_path);
        exit;
    }

    logError("Fichier lu avec succès");

    // Vérifier si le contenu est un JSON valide
    $json_content = json_decode($file_content);
    if (json_last_error() !== JSON_ERROR_NONE) {
        http_response_code(500);
        echo json_encode(['error' => 'Le fichier ne contient pas un JSON valide']);
        logError("Le fichier ne contient pas un JSON valide : " . json_last_error_msg());
        exit;
    }

    logError("Contenu JSON valide, envoi de la réponse");

    // Envoyer le contenu JSON
    echo $file_content;

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Erreur de base de données : ' . $e->getMessage()]);
    logError("Erreur PDO : " . $e->getMessage());
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Erreur inattendue : ' . $e->getMessage()]);
    logError("Erreur inattendue : " . $e->getMessage());
}

logError("Fin du script download_history_list.php");
