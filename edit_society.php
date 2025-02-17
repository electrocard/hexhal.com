<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Vérification si l'utilisateur est connecté et a le rang "admin"
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$current_user_id = $_SESSION['user_id'];
$configFile = __DIR__ . '/hexhal_info.json';
$config = json_decode(file_get_contents($configFile), true);
$host = $config['server_address'];
$port = $config['port'];
$user = $config['user'];
$pass = $config['password'];
$db = $config['database'];

// Connexion à la base de données
try {
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$db", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Vérifier le rôle de l'utilisateur pour l'accès
    $stmt = $pdo->prepare("SELECT rank FROM users WHERE user_id = :user_id");
    $stmt->execute(['user_id' => $current_user_id]);
    $user = $stmt->fetch();
    
    if (!$user || $user['rank'] !== 'admin') {
        // Si l'utilisateur n'a pas le rang admin, rediriger vers dashboard
        header("Location: edit_society.php");
        exit();
    }

    // Récupérer les informations de la société
    $society_name = 'hexhal-ai'; // Exemple, remplacez par la variable dynamique selon votre logique
    $stmt_society = $pdo->prepare("SELECT full_name, description FROM societies WHERE name = :name");
    $stmt_society->execute(['name' => $society_name]);
    $society = $stmt_society->fetch();

    if (!$society) {
        echo 'Société introuvable.';
        exit;
    }

    // Définir le chemin du logo
    $logoPath = "societies/$society_name/logo.png";
    $defaultLogo = "https://ui-avatars.com/api/?name=$society_name"; // Logo par défaut si le fichier n'existe pas

    // Traitement de l'upload du logo et de la mise à jour des informations
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Vérifier et traiter l'upload de l'image
        if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
            $fileTmpPath = $_FILES['logo']['tmp_name'];
            $fileType = $_FILES['logo']['type'];
            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];

            // Vérifier que le fichier est bien une image
            if (!in_array($fileType, $allowedTypes)) {
                echo 'Le fichier n\'est pas une image valide.';
                exit;
            }

            // Déplacer le fichier téléchargé vers le répertoire du logo
            move_uploaded_file($fileTmpPath, $logoPath);
        }

        // Mise à jour de la description et du full_name de la société
        if (isset($_POST['full_name']) && isset($_POST['description'])) {
            $full_name = $_POST['full_name'];
            $description = $_POST['description'];

            // Mise à jour des informations de la société dans la base de données
            $update_sql = "UPDATE societies SET full_name = :full_name, description = :description WHERE name = :name";
            $update_stmt = $pdo->prepare($update_sql);
            $update_stmt->execute([
                'full_name' => $full_name,
                'description' => $description,
                'name' => $society_name
            ]);

            echo 'Informations de la société mises à jour avec succès.';
            header("Location: dashboard.php");
            exit();
        }
    }

} catch (PDOException $e) {
    echo 'Erreur : ' . $e->getMessage();
    exit();
}

?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Édition de la société</title>
    <style>
        #logoInput {
            display: none; /* Cacher le champ de fichier */
        }
    </style>
</head>
<body>
    <h1>Édition de la société <?php echo htmlspecialchars($society_name); ?></h1>
    
    <!-- Affichage du logo actuel ou logo par défaut -->
    <div>
        <h2>Logo actuel</h2>
        <a href="javascript:void(0)" onclick="document.getElementById('logoInput').click();">
            <img src="<?php echo file_exists($logoPath) ? $logoPath : $defaultLogo; ?>" alt="Logo" style="width: 200px; height: auto; cursor: pointer;">
        </a>
        <input type="file" name="logo" id="logoInput" accept="image/*" onchange="this.form.submit();">
    </div>

    <!-- Formulaire d'édition des informations de la société -->
    <form method="POST" action="" enctype="multipart/form-data">
        <label for="full_name">Nom complet de la société :</label>
        <input type="text" name="full_name" id="full_name" value="<?php echo htmlspecialchars($society['full_name']); ?>" required><br><br>

        <label for="description">Description de la société :</label><br>
        <textarea name="description" id="description" rows="5" cols="50" required><?php echo htmlspecialchars($society['description']); ?></textarea><br><br>

        <!-- Bouton unique pour enregistrer ou annuler -->
        <button type="submit">Enregistrer les modifications</button>
        <a href="edit_society.php"><button type="button">Annuler</button></a>
    </form>

</body>
</html>
