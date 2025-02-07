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

if (!$user || $user['rank'] != 'admin') {
    die("Vous n'avez pas l'autorisation d'accéder à cette page.");
}

// Récupérer tous les rôles de la société de l'utilisateur
$society = $user['society'];
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
</head>
<body>

<h1>Liste des rôles de votre entreprise</h1>

<!-- Afficher les rôles -->
<?php if (count($roles) > 0): ?>
    <table border="1">
        <tr>
            <th>Nom du rôle</th>
            <th>Poids du rôle</th>
            <th>Action</th>
        </tr>
        <?php foreach ($roles as $role): ?>
    <tr>
        <td><?php echo htmlspecialchars($role['role_name']); ?></td>
        <td><?php echo htmlspecialchars($role['role_weight']); ?></td>
        <td>
            <!-- Lien de modification avec ID et nom du rôle -->
            <a href="edit_role.php?id=<?php echo $role['role_id']; ?>&role_name=<?php echo urlencode($role['role_name']); ?>">Modifier</a>
        </td>
    </tr>
<?php endforeach; ?>

    </table>
<?php else: ?>
    <p>Aucun rôle trouvé pour votre entreprise.</p>
<?php endif; ?>

</body>
</html>
