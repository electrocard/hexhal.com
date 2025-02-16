<?php
session_start();

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    die("Vous devez être connecté pour voir les rôles.");
}

// Charger la configuration JSON
$configFile = 'hexhal_info.json';  // Modifie ce chemin si nécessaire
if (!file_exists($configFile)) {
    die("Le fichier de configuration est manquant.");
}

$config = json_decode(file_get_contents($configFile), true);
if (!$config) {
    die("Impossible de charger la configuration.");
}

$host = $config['server_address'];
$port = $config['port'];
$user = $config['user'];
$pass = $config['password'];
$db = $config['database'];

// Créer la connexion PDO à la base de données
try {
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$db", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Erreur de connexion : " . $e->getMessage());
}

// Vérification si l'utilisateur est admin
$stmt = $pdo->prepare("SELECT rank, society FROM users WHERE user_id = :user_id");
$stmt->execute(['user_id' => $_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Débogage : Afficher les informations de l'utilisateur récupérées
if (!$user) {
    die("Erreur : Impossible de récupérer vos informations.");
}

// Vérifier le rang en ignorant la casse
if (strtolower($user['rank']) !== 'admin') {
    die("Vous n'avez pas l'autorisation d'accéder à cette page.");
}

// Vérifier que la société est bien définie
$society = $user['society'];
if (empty($society)) {
    die("Erreur : Votre société n'est pas définie.");
}

// Récupérer tous les rôles de la société de l'utilisateur
$stmt = $pdo->prepare("SELECT * FROM roles WHERE society = :society");
$stmt->execute(['society' => $society]);
$roles = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Liste des rôles</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            background-color:rgb(255, 255, 255);
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
            background-color: white;
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
            display: inline-block;
            text-align: center;
        }
        .edit-btn:hover {
            background-color: #ff7300;
        }
    </style>
</head>
<body>


    <div class="container">
        <?php if (count($roles) > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>Nom du rôle</th>
                        <th>Poids du rôle</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($roles as $role): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($role['role_name']); ?></td>
                            <td><?php echo htmlspecialchars($role['role_weight']); ?></td>
                            <td>
                                <a href="edit_role.php?id=<?php echo $role['role_id']; ?>&role_name=<?php echo urlencode($role['role_name']); ?>" class="edit-btn">Modifier</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>Aucun rôle trouvé pour votre entreprise.</p>
        <?php endif; ?>
    </div>

</body>
</html>
