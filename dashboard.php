<?php
session_start();
header('Content-Type: text/html; charset=utf-8');

// Helper function for logging errors
function logError($message) {
    error_log($message . PHP_EOL, 3, __DIR__ . '/error_log.txt');
}

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id']) || !isset($_SESSION['auth_token'])) {
    $error_message = 'Utilisateur non connecté.';
    $users = []; // Pas d'utilisateurs à afficher
} else {
    $user_id = $_SESSION['user_id'];
    $auth_token = $_SESSION['auth_token'];

    // Charger la configuration de la base de données
    $configFile = __DIR__ . '/hexhal_info.json';
    if (!file_exists($configFile)) {
        $error_message = 'Configuration file not found.';
        $users = []; // Pas d'utilisateurs à afficher
    } else {
        $config = json_decode(file_get_contents($configFile), true);
        $host = $config['server_address'];
        $port = $config['port'];
        $user = $config['user'];
        $pass = $config['password'];
        $db = $config['database'];

        try {
            // Connexion à la base de données
            $pdo = new PDO("mysql:host=$host;port=$port;dbname=$db", $user, $pass);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            // Vérifier si le jeton d'authentification est valide
            $sql = "SELECT * FROM users WHERE user_id = :user_id AND auth_token = :auth_token";
            $stmt = $pdo->prepare($sql);
            $stmt->execute(['user_id' => $user_id, 'auth_token' => $auth_token]);
            $user = $stmt->fetch();

            if (!$user) {
                $error_message = 'Jeton invalide ou session expirée.';
                $users = []; // Pas d'utilisateurs à afficher
            } else {
                // Récupérer l'ID de la société de l'utilisateur connecté
                $society_id = $user['society'];

                // Récupérer tous les utilisateurs de la même société
                $sql = "SELECT * FROM users WHERE society = :society_id";
                $stmt = $pdo->prepare($sql);
                $stmt->execute(['society_id' => $society_id]);
                $users = $stmt->fetchAll();

                if (!$users) {
                    $error_message = 'Aucun utilisateur trouvé dans la même société.';
                } else {
                    $error_message = ''; // Pas d'erreur, utilisateurs trouvés
                }
            }
        } catch (PDOException $e) {
            $error_message = 'Erreur de connexion à la base de données: ' . $e->getMessage();
            $users = [];
            logError("Database error: " . $e->getMessage());
        } catch (Exception $e) {
            $error_message = 'Erreur inattendue: ' . $e->getMessage();
            $users = [];
            logError("Unexpected error: " . $e->getMessage());
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Utilisateurs</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
        }
        .container {
            margin-top: 30px;
        }
        .user-list {
            list-style-type: none;
            padding: 0;
        }
        .user-list li {
            padding: 10px;
            border: 1px solid #ddd;
            margin-bottom: 10px;
            border-radius: 5px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .user-list li:hover {
            background-color: #f4f4f4;
        }
        .error-message {
            color: red;
            text-align: center;
            font-weight: bold;
        }
        .edit-btn {
            padding: 5px 10px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        .edit-btn:hover {
            background-color: #0056b3;
        }
    </style>
</head>
<body>

    <h1>Tableau de bord</h1>

    <?php if (!empty($error_message)): ?>
        <p class="error-message"><?php echo $error_message; ?></p>
    <?php endif; ?>

    <div class="container">
        <?php if (!empty($users)): ?>
            <h2>Utilisateurs dans la même société</h2>
            <ul class="user-list">
                <?php foreach ($users as $user): ?>
                    <li>
                        <span><?php echo htmlspecialchars($user['first_name']) . ' ' . htmlspecialchars($user['family_name']); ?></span>
                        <span>Société: <?php echo htmlspecialchars($user['society']); ?></span>
                        <a href="edit_user.php?id=<?php echo $user['user_id']; ?>" class="edit-btn">Éditer</a>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <p>Aucun utilisateur à afficher ou une erreur s'est produite.</p>
        <?php endif; ?>
    </div>

</body>
</html>
