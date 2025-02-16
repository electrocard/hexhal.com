<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Helper function for logging errors
function logError($message) {
    error_log($message . PHP_EOL, 3, __DIR__ . '/error_log.txt');
}

// Get redirect URL if provided
$redirect_url = isset($_GET['redirect']) ? $_GET['redirect'] : 'dashboard.php';

// Check if it's an AJAX request and handle login
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['user_id']) && isset($_POST['password'])) {
    header('Content-Type: application/json');

    $user_id = $_POST['user_id'];
    $password = $_POST['password'];

    // Load database configuration
    $configFile = __DIR__ . '/hexhal_info.json';
    if (!file_exists($configFile)) {
        echo json_encode(['status' => 'error', 'message' => 'Configuration file not found.']);
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

        // Prepare and execute SQL query to get the user by user_id
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
            $update_stmt->execute(['token' => $auth_token, 'user_id' => $user['user_id']]);

            // Success response with redirection URL
            echo json_encode([
                'status' => 'success',
                'message' => 'Authentification réussie.',
                'redirect' => $redirect_url
            ]);
        } else {
            echo json_encode(['status' => 'error', 'message' => "ID utilisateur ou mot de passe incorrect."]);
            logError("Authentication failed for user: $user_id");
        }
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Erreur de connexion à la base de données: ' . $e->getMessage()]);
        logError("Database error: " . $e->getMessage());
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => 'Erreur inattendue: ' . $e->getMessage()]);
        logError("Unexpected error: " . $e->getMessage());
    }
    exit;
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <title>Glassmorphism Login Form</title>
    <link rel="stylesheet" href="resource/css/login2.css">
    <script>
    function handleLogin(event) {
        event.preventDefault();
        
        const user_id = document.getElementById('user_id').value;
        const password = document.getElementById('password').value;
        
        const formData = new FormData();
        formData.append('user_id', user_id);
        formData.append('password', password);

        fetch('', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                window.location.href = data.redirect;
            } else {
                document.getElementById('error-message').textContent = data.message;
            }
        })
        .catch(error => {
            document.getElementById('error-message').textContent = 'Une erreur est survenue.';
            console.error('Erreur:', error);
        });
    }
    </script>
</head>
<body>
    <div class="background"></div>
    <form class="login-form" method="POST" action="" onsubmit="handleLogin(event)">
        <div class="logo">
            <img src="resource/logo/hexhal_logo.png" alt="Logo">
        </div>
        <p id="error-message" style="color: red; text-align: center;"></p>
        <label for="user_id">ID utilisateur</label>
        <input type="text" placeholder="ID utilisateur" id="user_id" name="user_id" required>
        <label for="password">Mot de passe</label>
        <input type="password" placeholder="Mot de passe" id="password" name="password" required>
        <button type="submit">Se connecter</button>
    </form>
</body>
</html>