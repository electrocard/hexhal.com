<?php
session_start();
header('Content-Type: application/json');

// Helper function for logging errors
function logError($message) {
    error_log($message . PHP_EOL, 3, __DIR__ . '/error_log.txt');
}

$response = [];

try {
    // Vérifier si l'utilisateur est connecté
    if (isset($_SESSION['user_id']) && isset($_SESSION['auth_token'])) {
        $user_id = $_SESSION['user_id'];
        $auth_token = $_SESSION['auth_token'];

        // Charger la configuration de la base de données
        $configFile = __DIR__ . '/hexhal_info.json';
        if (!file_exists($configFile)) {
            $response = [
                'status' => 'error',
                'message' => 'Configuration file not found.'
            ];
            logError('Configuration file missing.');
            echo json_encode($response);
            exit;
        }

        $config = json_decode(file_get_contents($configFile), true);
        $host = $config['server_address'];
        $port = $config['port'];
        $user = $config['user'];
        $pass = $config['password'];
        $db = $config['database'];

        // Connexion à la base de données
        $pdo = new PDO("mysql:host=$host;port=$port;dbname=$db", $user, $pass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Vérifier si le jeton d'authentification est valide
        $sql = "SELECT * FROM users WHERE user_id = :user_id AND auth_token = :auth_token";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['user_id' => $user_id, 'auth_token' => $auth_token]);
        $user = $stmt->fetch();

        if ($user) {
            // L'utilisateur est connecté
            $response = [
                'status' => 'success',
                'message' => 'Utilisateur authentifié.',
                'user' => [
                    'user_id' => $user['user_id'],
                    'first_name' => $user['first_name'],
                    'family_name' => $user['family_name'],
                    'society' => $user['society']
                ]
            ];
        } else {
            // Le jeton n'est pas valide ou la session a expiré
            $response = [
                'status' => 'error',
                'message' => 'Authentification échouée. Jeton invalide ou session expirée.'
            ];
            logError('Invalid auth token or session expired for user: ' . $user_id);
        }
    } else {
        // Pas de session active
        $response = [
            'status' => 'error',
            'message' => 'Utilisateur non connecté.'
        ];
    }
} catch (PDOException $e) {
    // Erreur de base de données
    $response = [
        'status' => 'error',
        'message' => 'Erreur de connexion à la base de données: ' . $e->getMessage()
    ];
    logError("Database error: " . $e->getMessage());
} catch (Exception $e) {
    // Erreur générale
    $response = [
        'status' => 'error',
        'message' => 'Erreur inattendue: ' . $e->getMessage()
    ];
    logError("Unexpected error: " . $e->getMessage());
}

echo json_encode($response);
