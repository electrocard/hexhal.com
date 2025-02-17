<?php
session_start(); // Toujours démarrer la session au tout début

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Lire la configuration de la base de données depuis un fichier JSON
$config = json_decode(file_get_contents('hexhal_info.json'), true);
$host = $config['server_address'];
$port = $config['port'];
$user = $config['user'];
$pass = $config['password'];
$db = $config['database'];

try {
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$db", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

$error = ""; // Initialisation de la variable d'erreur

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    if (!empty($username) && !empty($password)) {
        // Recherche l'utilisateur dans la base de données
        $sql = "SELECT * FROM users WHERE username = :username";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['username' => $username]);
        $user = $stmt->fetch();

        // Vérifie si l'utilisateur existe et si le mot de passe est correct
        if ($user && password_verify($password, $user['password'])) {
            // Si l'utilisateur est authentifié, on initialise la session avec les informations nécessaires
            $_SESSION['username'] = $user['username'];
            $_SESSION['society'] = $user['society'];
            $_SESSION['first_name'] = $user['first_name'];
            $_SESSION['family_name'] = $user['family_name'];

            // Debug : afficher les données de session après connexion
            echo "<pre>";
            var_dump($_SESSION); // Affiche les données de la session pour déboguer
            echo "</pre>";

            // Rediriger vers le dashboard
            header("Location: dashboard.php");
            exit();
        } else {
            // Si le mot de passe ou le nom d'utilisateur est incorrect
            $error = "Nom d'utilisateur ou mot de passe incorrect.";
        }
    } else {
        // Si un des champs est vide
        $error = "Veuillez remplir tous les champs.";
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
</head>
<body>
    <h1>Se connecter</h1>

    <form method="POST" action="">
        <label for="username">Nom d'utilisateur</label>
        <input type="text" name="username" id="username" required>

        <label for="password">Mot de passe</label>
        <input type="password" name="password" id="password" required>

        <button type="submit">Se connecter</button>

        <?php if (!empty($error)): ?>
            <p style="color: red;"><?php echo $error; ?></p>
        <?php endif; ?>
    </form>
</body>
</html>
