<?php
// Vérification de la session de l'utilisateur (supposons que l'utilisateur est déjà connecté)
session_start();

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    die("Vous devez être connecté pour ajouter un rôle.");
}

// Chemin vers le fichier de configuration JSON
$configFile = 'hexhal_info.json';  // Modifie ce chemin si nécessaire

if (!file_exists($configFile)) {
    die("Le fichier de configuration est manquant.");
}

// Charger les données du fichier JSON
$config = json_decode(file_get_contents($configFile), true);

// Vérifier si la configuration a bien été chargée
if (!$config) {
    die("Impossible de charger la configuration.");
}

// Récupérer les informations de connexion
$host = $config['server_address'];
$port = $config['port'];
$user = $config['user'];
$pass = $config['password'];
$db = $config['database'];

// Créer une connexion PDO à la base de données
try {
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$db", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Erreur de connexion : " . $e->getMessage());
}

// Vérification si l'utilisateur a le rôle d'admin
try {
    $stmt = $pdo->prepare("SELECT rank FROM users WHERE user_id = :user_id");
    $stmt->execute(['user_id' => $_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user || $user['rank'] != 'admin') {
        // L'utilisateur n'a pas le rôle "admin"
        die("Vous n'avez pas l'autorisation d'accéder à cette page.");
    }
} catch (PDOException $e) {
    die("Erreur de base de données : " . $e->getMessage());
}

// Variable pour les messages
$message = "";

// Vérifier si le formulaire est soumis
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Récupérer les données du formulaire
    $role_name = $_POST['role_name'];
    $role_weight = $_POST['role_weight'];

    // Récupérer la société de l'utilisateur connecté
    try {
        // Récupérer la société de l'utilisateur connecté
        $stmt = $pdo->prepare("SELECT society FROM users WHERE user_id = :user_id");
        $stmt->execute(['user_id' => $_SESSION['user_id']]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            $society = $user['society']; // Associer le rôle à la société de l'utilisateur
        } else {
            $message = "Société de l'utilisateur introuvable.";
            exit;
        }

        // Insérer les données dans la base de données
        $stmt = $pdo->prepare("INSERT INTO roles (role_name, role_weight, society) VALUES (:role_name, :role_weight, :society)");
        $stmt->execute([
            'role_name' => $role_name,
            'role_weight' => $role_weight,
            'society' => $society
        ]);

        $message = "Rôle ajouté avec succès !";
    } catch (PDOException $e) {
        // Si une erreur se produit lors de l'insertion
        $message = "Erreur de base de données: " . $e->getMessage();
    }
}

// Optionnel : Récupérer les sociétés disponibles (si nécessaire)
$societies = [];
try {
    $stmt = $pdo->query("SELECT * FROM societies");
    $societies = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $message = "Erreur lors de la récupération des sociétés : " . $e->getMessage();
}

?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ajouter un rôle</title>
</head>
<body>

<h1>Ajouter un rôle</h1>

<!-- Afficher le message après l'ajout -->
<?php if (!empty($message)): ?>
    <p><?php echo $message; ?></p>
<?php endif; ?>

<!-- Formulaire pour ajouter un rôle -->
<form method="POST" action="">
    <label for="role_name">Nom du rôle</label>
    <input type="text" id="role_name" name="role_name" required><br><br>

    <label for="role_weight">Poids du rôle</label>
    <input type="number" id="role_weight" name="role_weight" required><br><br>

    <!-- Societé est assignée automatiquement -->
    <input type="hidden" name="society" value="<?php echo $society; ?>">

    <button type="submit">Ajouter le rôle</button>
</form>

</body>
</html>
