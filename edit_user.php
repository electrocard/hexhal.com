<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Vérification si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    // Rediriger vers la page de connexion
    header("Location: login.php");
    exit(); // Assurez-vous d'appeler exit() après header() pour arrêter l'exécution du script
}

// Vérifier si l'ID de l'utilisateur à éditer est fourni
if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo json_encode(['status' => 'error', 'message' => 'ID utilisateur manquant']);
    exit;
}

$user_id_to_edit = $_GET['id']; // L'ID de l'utilisateur à éditer

// Configuration de la base de données
$configFile = __DIR__ . '/hexhal_info.json';
if (!file_exists($configFile)) {
    echo json_encode(['status' => 'error', 'message' => 'Fichier de configuration introuvable']);
    exit;
}

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

    // Récupérer les données de l'utilisateur à éditer
    $stmt = $pdo->prepare("SELECT first_name, family_name, email, role, society FROM users WHERE user_id = :user_id");
    $stmt->execute(['user_id' => $user_id_to_edit]);
    $user = $stmt->fetch();

    // Si l'utilisateur n'existe pas
    if (!$user) {
        echo json_encode(['status' => 'error', 'message' => 'Utilisateur non trouvé']);
        exit;
    }

    // Initialiser les valeurs à afficher dans le formulaire
    $first_name = $user['first_name'] ?? '';
    $family_name = $user['family_name'] ?? '';
    $email = $user['email'] ?? '';
    $role = $user['role'] ?? '';
    $society = $user['society'] ?? '';

    // Récupérer les rôles disponibles pour la société de l'utilisateur
    $stmt_roles = $pdo->prepare("SELECT role_name FROM roles WHERE society = :society");
    $stmt_roles->execute(['society' => $society]);
    $roles = $stmt_roles->fetchAll(PDO::FETCH_ASSOC);

    // Si le formulaire a été soumis
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Récupérer les données soumises
        $first_name = $_POST['first_name'];
        $family_name = $_POST['family_name'];
        $email = $_POST['email'];
        $role = $_POST['role'];

        // Mettre à jour les données dans la base de données pour cet utilisateur
        $update_sql = "UPDATE users SET first_name = :first_name, family_name = :family_name, email = :email, role = :role WHERE user_id = :user_id";
        $update_stmt = $pdo->prepare($update_sql);
        $update_stmt->execute([
            'first_name' => $first_name,
            'family_name' => $family_name,
            'email' => $email,
            'role' => $role,
            'user_id' => $user_id_to_edit
        ]);

        // Redirection vers la page du tableau de bord après la mise à jour
        header("Location: dashboard.php");
        exit; // Important pour éviter de continuer le script après la redirection
    } else {
        // Afficher le formulaire avec les données actuelles de l'utilisateur
        ?>
        <form method="POST" action="">
            <label for="first_name">Prénom</label>
            <input type="text" id="first_name" name="first_name" value="<?php echo htmlspecialchars($first_name); ?>" required><br>

            <label for="family_name">Nom de famille</label>
            <input type="text" id="family_name" name="family_name" value="<?php echo htmlspecialchars($family_name); ?>" required><br>

            <label for="email">Email</label>
            <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required><br>

            <label for="role">Rôle</label>
            <select id="role" name="role" required>
                <?php
                foreach ($roles as $role_option) {
                    $selected = ($role_option['role_name'] == $role) ? 'selected' : '';
                    echo '<option value="' . htmlspecialchars($role_option['role_name']) . '" ' . $selected . '>' . htmlspecialchars($role_option['role_name']) . '</option>';
                }
                ?>
            </select><br>

            <button type="submit">Mettre à jour</button>
        </form>
        <?php
    }

} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Erreur de base de données: ' . $e->getMessage()]);
}
?>
