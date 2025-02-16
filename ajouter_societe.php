<?php
session_start();

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    die("Vous devez être connecté pour ajouter une société.");
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

    // Vérifier si le formulaire est soumis
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Récupérer les données du formulaire
        $name = trim($_POST['name']);
        $isApplied = isset($_POST['isApplied']) ? 1 : 0; // Par défaut 0 si non coché

        // Validation basique
        if (empty($name)) {
            die("Le nom de la société ne peut pas être vide.");
        }

        // Préparer la requête d'insertion
        $stmt = $pdo->prepare("INSERT INTO societies (name, isApplied) VALUES (:name, :isApplied)");
        $stmt->execute(['name' => $name, 'isApplied' => $isApplied]);

        echo "Société ajoutée avec succès !";
    }
} catch (PDOException $e) {
    die("Erreur de base de données : " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ajouter une société</title>
</head>
<body>
    <h2>Ajouter une société</h2>
    <form method="POST" action="ajouter_societe.php">
        <label for="name">Nom de la société:</label>
        <input type="text" id="name" name="name" required>
        <br><br>
        <label for="isApplied">Société appliquée (cocher si oui):</label>
        <input type="checkbox" id="isApplied" name="isApplied">
        <br><br>
        <button type="submit">Ajouter</button>
    </form>
</body>
</html>
