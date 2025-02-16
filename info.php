<?php
session_start();

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    die("Vous devez être connecté pour voir vos informations.");
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

try {
    // Connexion à la base de données
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$db", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Récupérer les informations de l'utilisateur connecté
    $stmt = $pdo->prepare("SELECT user_id, first_name, family_name, role, society, rank FROM users WHERE user_id = :user_id");
    $stmt->execute(['user_id' => $_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // Vérifier si l'utilisateur existe
    if (!$user) {
        die("Erreur : Impossible de récupérer vos informations.");
    }

    // Affichage du rank récupéré pour déboguer
    echo 'Rank récupéré: ' . htmlspecialchars($user['rank']) . '<br>';

    // Déterminer si l'utilisateur est admin
    $isAdmin = (strtolower($user['rank']) === 'admin') ? true : false;

    // Déboguer la valeur de $isAdmin
    echo 'isAdmin: ' . ($isAdmin ? 'Oui' : 'Non') . '<br>';

} catch (PDOException $e) {
    die("Erreur de base de données : " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Informations Utilisateur</title>
</head>
<body>

<h1>Vos informations</h1>

<table border="1">
    <tr>
        <th>Champ</th>
        <th>Valeur</th>
    </tr>
    <tr>
        <td><strong>ID Utilisateur</strong></td>
        <td><?php echo htmlspecialchars($user['user_id']); ?></td>
    </tr>
    <tr>
        <td><strong>Nom</strong></td>
        <td><?php echo htmlspecialchars($user['family_name']); ?></td>
    </tr>
    <tr>
        <td><strong>Prénom</strong></td>
        <td><?php echo htmlspecialchars($user['first_name']); ?></td>
    </tr>
    <tr>
        <td><strong>Rôle</strong></td>
        <td><?php echo htmlspecialchars($user['role']); ?></td>
    </tr>
    <tr>
        <td><strong>Société</strong></td>
        <td><?php echo htmlspecialchars($user['society']); ?></td>
    </tr>
    <tr>
        <td><strong>Admin</strong></td>
        <td><?php echo $isAdmin ? 'Oui' : 'Non'; ?></td>
    </tr>
</table>

<!-- Barre de navigation avec les onglets -->
<div class="tabs">
    <ul>
        <li><a href="info.php">Informations</a></li>
        
        <?php if ($isAdmin): ?>
            <li><a href="users_list.php">Liste des utilisateurs</a></li>
            <li><a href="roles_list.php">Liste des rôles</a></li>
        <?php endif; ?>
        
    </ul>
</div>

</body>
</html>
