<?php
session_start();

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    // Rediriger vers la page de connexion
    header("Location: login.php");
    exit(); // Assurez-vous d'appeler exit() après header() pour arrêter l'exécution du script
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

$society = $user['society']; // Récupérer la société de l'utilisateur

// Récupérer le rôle à modifier
if (!isset($_GET['role_name'])) {
    die("Le nom du rôle est manquant.");
}

$role_name = $_GET['role_name']; // Le nom du rôle passé en paramètre
$stmt = $pdo->prepare("SELECT * FROM roles WHERE role_name = :role_name AND society = :society");
$stmt->execute(['role_name' => $role_name, 'society' => $society]);
$role = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$role) {
    die("Rôle introuvable pour cette société.");
}

// Mise à jour du rôle si le formulaire est soumis
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $role_name = $_POST['role_name'];
    $role_weight = $_POST['role_weight'];

    try {
        $stmt = $pdo->prepare("UPDATE roles SET role_name = :role_name, role_weight = :role_weight WHERE id = :id");
        $stmt->execute([
            'role_name' => $role_name,
            'role_weight' => $role_weight,
            'id' => $role['id']
        ]);

        // Redirection vers roles_list.php après l'application des modifications
        header("Location: roles_list.php");
        exit();
    } catch (PDOException $e) {
        $message = "Erreur de base de données: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifier le rôle</title>
</head>
<body>

<h1>Modifier le rôle</h1>

<?php if (!empty($message)): ?>
    <p><?php echo $message; ?></p>
<?php endif; ?>

<!-- Formulaire de modification de rôle -->
<form method="POST" action="">
    <label for="role_name">Nom du rôle</label>
    <input type="text" id="role_name" name="role_name" value="<?php echo htmlspecialchars($role['role_name']); ?>" required><br><br>

    <label for="role_weight">Poids du rôle</label>
    <input type="number" id="role_weight" name="role_weight" value="<?php echo htmlspecialchars($role['role_weight']); ?>" required><br><br>

    <button type="submit">Appliquer les modifications</button>
    <a href="roles_list.php"><button type="button">Annuler</button></a>
</form>

</body>
</html>
