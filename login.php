<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Helper function for logging errors
function logError($message) {
    error_log($message . PHP_EOL, 3, __DIR__ . '/error_log.txt');
}

// Check if it's an AJAX request and handle login
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['username']) && isset($_POST['password'])) {
    header('Content-Type: application/json');

    $username = $_POST['username'];
    $password = $_POST['password'];

    // Load database configuration
    $configFile = __DIR__ . '/hexhal_info.json';
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

        // Prepare and execute SQL query
        $sql = "SELECT * FROM users WHERE username = :username";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['username' => $username]);
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
            echo json_encode([
                'status' => 'success',
                'message' => 'Authentification réussie.',
                'user' => [
                    'user_id' => $user['user_id'],
                    'first_name' => $user['first_name'],
                    'family_name' => $user['family_name'],
                    'society' => $user['society']
                ],
                'auth_token' => $auth_token
            ]);
        } else {
            // Authentication failure
            echo json_encode([
                'status' => 'error',
                'message' => "Nom d'utilisateur ou mot de passe incorrect."
            ]);
            logError("Authentication failed for user: $username");
        }
    } catch (PDOException $e) {
        // Database connection error
        echo json_encode([
            'status' => 'error',
            'message' => 'Erreur de connexion à la base de données: ' . $e->getMessage()
        ]);
        logError("Database error: " . $e->getMessage());
    } catch (Exception $e) {
        // General error
        echo json_encode([
            'status' => 'error',
            'message' => 'Erreur inattendue: ' . $e->getMessage()
        ]);
        logError("Unexpected error: " . $e->getMessage());
    }
    exit;
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <title>Glassmorphism Login Form</title>

    <link rel="preconnect" href="https://fonts.gstatic.com">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="resource/css/login2.css">

    <script>
        function handleLogin(event) {
            event.preventDefault();

            const username = document.getElementById('username').value;
            const password = document.getElementById('password').value;

            const formData = new FormData();
            formData.append('username', username);
            formData.append('password', password);

            fetch('', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    alert('Login successful!');
                    // Optionally, you can redirect the user or store session data
                } else {
                    document.getElementById('error-message').textContent = data.message;
                }
            })
            .catch(error => {
                document.getElementById('error-message').textContent = 'An error occurred.';
                console.error('Error:', error);
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

        <!-- Affichage de l'erreur si nécessaire -->
        <p id="error-message" style="color: red; text-align: center;"></p>

        <label for="username">Nom d'utilisateur</label>
        <input type="text" placeholder="Email ou téléphone" id="username" name="username" required>

        <label for="password">Mot de passe</label>
        <input type="password" placeholder="Mot de passe" id="password" name="password" required>

        <button type="submit">Se connecter</button>
    </form>
</body>
</html>
