<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');

// Helper function for logging errors
function logError($message) {
    error_log($message . PHP_EOL, 3, __DIR__ . '/error_log.txt');
}

// Read JSON request data
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (isset($data['user_id']) && isset($data['password'])) {
    $user_id = $data['user_id'];
    $password = $data['password'];

    // Load database configuration
    $configFile = __DIR__ . 'hexhal_info.json';
    if (!file_exists($configFile)) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Configuration file not found.'
        ]);
        logError('Configuration file missing.');
        exit;
    }

    $config = json_decode(file_get_contents($configFile), true);
    $host = $config['server_address'];
    $port = $config['port'];
    $user = $config['user'];
    $pass = $config['password'];
    $db = $config['database'];

    try {
        // Connect to the database
        $pdo = new PDO("mysql:host=$host;port=$port;dbname=$db", $user, $pass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Prepare and execute SQL query using user_id
        $sql = "SELECT * FROM users WHERE user_id = :user_id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['user_id' => $user_id]);
        $user = $stmt->fetch();

        // Verify password
        if ($user && password_verify($password, $user['password'])) {
            // Authentication success
            $auth_token = bin2hex(random_bytes(32));
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['auth_token'] = $auth_token;

            // Update token in the database
            $update_sql = "UPDATE users SET auth_token = :token WHERE user_id = :user_id";
            $update_stmt = $pdo->prepare($update_sql);
            $update_stmt->execute([
                'token' => $auth_token,
                'user_id' => $user['user_id']
            ]);

            // Success response
            $response = [
                'status' => 'success',
                'message' => 'Authentification réussie.',
                'user' => [
                    'user_id' => $user['user_id'],
                    'first_name' => $user['first_name'],
                    'family_name' => $user['family_name'],
                    'society' => $user['society']
                ],
                'auth_token' => $auth_token
            ];
        } else {
            // Authentication failure
            $response = [
                'status' => 'error',
                'message' => "Identifiants incorrects."
            ];
            logError("Authentication failed for user_id: $user_id");
        }
    } catch (PDOException $e) {
        // Database connection error
        $response = [
            'status' => 'error',
            'message' => 'Erreur de connexion à la base de données: ' . $e->getMessage()
        ];
        logError("Database error: " . $e->getMessage());
    } catch (Exception $e) {
        // General error
        $response = [
            'status' => 'error',
            'message' => 'Erreur inattendue: ' . $e->getMessage()
        ];
        logError("Unexpected error: " . $e->getMessage());
    }

    echo json_encode($response);
} else {
    // Missing data error
    $response = [
        'status' => 'error',
        'message' => 'Données manquantes.'
    ];
    logError("Request missing user_id or password.");
    echo json_encode($response);
}
?>
