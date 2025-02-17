<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Fichier de log d'erreur personnalisé
$logFile = 'error_log.txt';

// Fonction pour écrire des erreurs dans le log
function log_error($message) {
    global $logFile;
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($logFile, "[$timestamp] - $message\n", FILE_APPEND);
}

// Vérifier si l'utilisateur est authentifié
if (!isset($_SESSION['user_id']) || !isset($_SESSION['auth_token'])) {
    log_error('Accès interdit : utilisateur non authentifié.');
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Accès interdit : veuillez vous connecter.']);
    exit;
}

try {
    // Charger les configurations de la base de données
    $configFile = __DIR__ . 'hexhal_info.json';
    if (!file_exists($configFile)) {
        log_error('Fichier de configuration manquant.');
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => 'Fichier de configuration manquant.']);
        exit;
    }

    $config = json_decode(file_get_contents($configFile), true);
    $host = $config['server_address'];
    $port = $config['port'];
    $user = $config['user'];
    $pass = $config['password'];
    $db = $config['database'];

    // Connecter à la base de données
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$db", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Vérifier le token d'authentification
    $stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = :user_id AND auth_token = :auth_token");
    $stmt->execute(['user_id' => $_SESSION['user_id'], 'auth_token' => $_SESSION['auth_token']]);
    $user = $stmt->fetch();

    if (!$user) {
        log_error('Token invalide ou expiré pour l\'utilisateur ID: ' . $_SESSION['user_id']);
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => 'Token invalide ou expiré.']);
        exit;
    }

    // Vérifier si le nom du fichier est fourni
    if (!isset($_GET['filename']) || empty($_GET['filename'])) {
        log_error('Nom de fichier non spécifié pour l\'utilisateur ID: ' . $_SESSION['user_id']);
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => 'Nom de fichier non spécifié.']);
        exit;
    }

    $filename = basename($_GET['filename']); // Sécuriser le nom du fichier
    $filepath = "../society/{$user['society']}/{$user['user_id']}/$filename"; // Utiliser user_id à la place de username

    // Vérifier si le fichier existe
    if (!file_exists($filepath)) {
        log_error("Fichier non trouvé: $filepath pour l'utilisateur ID: " . $_SESSION['user_id']);
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => 'Fichier non trouvé.']);
        exit;
    }

    // Vérifier que le fichier est lisible
    if (!is_readable($filepath)) {
        log_error("Le fichier $filepath n'est pas lisible pour l'utilisateur ID: " . $_SESSION['user_id']);
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => 'Le fichier n\'est pas lisible.']);
        exit;
    }

    // Définir les en-têtes pour le téléchargement
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . filesize($filepath));

    // Désactiver la mise en cache
    header('Cache-Control: no-cache, no-store, must-revalidate');
    header('Pragma: no-cache');
    header('Expires: 0');

    // Lire et envoyer le fichier
    readfile($filepath);
    exit;

} catch (PDOException $e) {
    log_error('Erreur de base de données: ' . $e->getMessage());
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Erreur de base de données: ' . $e->getMessage()]);
    exit;
} catch (Exception $e) {
    log_error('Erreur inattendue: ' . $e->getMessage());
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Erreur inattendue: ' . $e->getMessage()]);
    exit;
}
?>
