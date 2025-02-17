<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Vérification si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    // Rediriger vers la page de connexion
    header("Location: login.php");
    exit();
}

// Vérifier si l'ID de l'utilisateur à éditer est fourni
if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo json_encode(['status' => 'error', 'message' => 'ID utilisateur manquant']);
    exit;
}

$user_id_to_edit = $_GET['id']; // L'ID de l'utilisateur à éditer
$current_user_id = $_SESSION['user_id']; // L'ID de l'utilisateur connecté

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
    $stmt = $pdo->prepare("SELECT first_name, family_name, email, role, society, root, rank FROM users WHERE user_id = :user_id");
    $stmt->execute(['user_id' => $user_id_to_edit]);
    $user = $stmt->fetch();

    // Si l'utilisateur n'existe pas
    if (!$user) {
        echo json_encode(['status' => 'error', 'message' => 'Utilisateur non trouvé']);
        exit;
    }

    // Récupérer les informations de l'utilisateur connecté pour vérifier s'il est un root
    $stmt_current_user = $pdo->prepare("SELECT root FROM users WHERE user_id = :user_id");
    $stmt_current_user->execute(['user_id' => $current_user_id]);
    $current_user = $stmt_current_user->fetch();

    // Vérifier si l'utilisateur connecté est un root
    $is_root = $current_user['root'] == 1;

    // Initialiser les valeurs à afficher dans le formulaire
    $first_name = $user['first_name'] ?? '';
    $family_name = $user['family_name'] ?? '';
    $email = $user['email'] ?? '';
    $role = $user['role'] ?? '';
    $society = $user['society'] ?? '';
    $rank = $user['rank'] ?? '';  // Récupérer le rank de l'utilisateur

    // Si l'utilisateur connecté essaie de s'éditer lui-même et n'est pas root, il est redirigé
    if ($current_user_id == $user_id_to_edit && !$is_root) {
        echo json_encode(['status' => 'error', 'message' => 'Vous ne pouvez pas vous éditer vous-même à moins d\'être un administrateur root.']);
        exit;
    }

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
        $rank = $_POST['rank'];  // Récupérer le nouveau rang (admin ou none)

        // Vérifier si l'utilisateur connecté est root et s'il modifie un autre utilisateur
        if ($is_root && $current_user_id != $user_id_to_edit) {
            // Mettre à jour les données dans la base de données pour cet utilisateur
            $update_sql = "UPDATE users SET first_name = :first_name, family_name = :family_name, email = :email, role = :role, rank = :rank WHERE user_id = :user_id";
            $update_stmt = $pdo->prepare($update_sql);
            $update_stmt->execute([
                'first_name' => $first_name,
                'family_name' => $family_name,
                'email' => $email,
                'role' => $role,
                'rank' => $rank,
                'user_id' => $user_id_to_edit
            ]);
        } else {
            // Mettre à jour seulement les informations autres que le rank
            $update_sql = "UPDATE users SET first_name = :first_name, family_name = :family_name, email = :email, role = :role WHERE user_id = :user_id";
            $update_stmt = $pdo->prepare($update_sql);
            $update_stmt->execute([
                'first_name' => $first_name,
                'family_name' => $family_name,
                'email' => $email,
                'role' => $role,
                'user_id' => $user_id_to_edit
            ]);
        }

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

            <!-- Afficher le switch uniquement pour l'utilisateur root -->
            <?php if ($is_root && $current_user_id != $user_id_to_edit): ?>
                <label for="rank">Rank</label><br>
                <input type="radio" id="admin" name="rank" value="admin" <?php echo ($rank == 'admin') ? 'checked' : ''; ?>>
                <label for="admin">Admin</label><br>
                <input type="radio" id="none" name="rank" value="none" <?php echo ($rank == 'none') ? 'checked' : ''; ?>>
                <label for="none">None</label><br>
            <?php else: ?>
                <p><strong>Le switch de rank n'est visible que pour l'utilisateur root.</strong></p>
            <?php endif; ?>

            <button type="submit">Mettre à jour</button>
            <a href="dashboard.php"><button type="button">Annuler</button></a>
        </form>
        <?php
    }

} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Erreur de base de données: ' . $e->getMessage()]);
}
?>
