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
                // Vérification du rôle de l'utilisateur (doit être admin)
                if (strtolower($user['rank']) !== 'admin') {
                    $error_message = 'Accès interdit. Vous devez être administrateur pour accéder à cette page.';
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
        table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            margin-top: 20px;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.1);
        }
        th, td {
            padding: 10px;
            text-align: left;
        }
        th {
            background-color: #ff7300;
            color: white;
        }
        /* Coins arrondis pour la première et dernière colonne */
        th:first-child {
            border-top-left-radius: 10px;
        }
        th:last-child {
            border-top-right-radius: 10px;
        }
        tr:last-child td:first-child {
            border-bottom-left-radius: 10px;
        }
        tr:last-child td:last-child {
            border-bottom-right-radius: 10px;
        }
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        .error-message {
            color: red;
            text-align: center;
            font-weight: bold;
        }
        .edit-btn {
            padding: 5px 10px;
            background-color: #ff7300;
            color: white;
            border: none;
            border-radius: 5px;
            text-decoration: none;
        }
        .edit-btn:hover {
            background-color: #ff7300;
        }
    </style>
</head>
<body>


    <?php if (!empty($error_message)): ?>
        <p class="error-message"><?php echo $error_message; ?></p>
    <?php endif; ?>

    <div class="container">
        <?php if (!empty($users)): ?>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Prénom</th>
                        <th>Nom</th>
                        <th>Admin</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($user['user_id']); ?></td>
                            <td><?php echo htmlspecialchars($user['first_name']); ?></td>
                            <td><?php echo htmlspecialchars($user['family_name']); ?></td>
                            <td><?php echo (strtolower($user['rank']) === 'admin') ? '✅ Oui' : '❌ Non'; ?></td>
                            <td><a href="edit_user.php?id=<?php echo $user['user_id']; ?>" class="edit-btn">Modifier</a></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>Aucun utilisateur à afficher ou une erreur s'est produite.</p>
        <?php endif; ?>
    </div>

</body>
</html>
