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

try {
    // Connexion à la base de données
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$db", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Récupérer les informations de l'utilisateur et la colonne isApplied de la société
    $stmt = $pdo->prepare("SELECT user_id, first_name, family_name, role, society, rank, isApplied FROM users JOIN societies ON users.society = societies.name WHERE user_id = :user_id");
    $stmt->execute(['user_id' => $_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // Vérifier si l'utilisateur existe
    if (!$user) {
        die("Erreur : Impossible de récupérer vos informations.");
    }

    // Déterminer si l'utilisateur est admin
    $isAdmin = (strtolower($user['rank']) === 'admin') ? true : false;

    // Vérifier si la société de l'utilisateur a isApplied = 1
    $isApplied = $user['isApplied'] == 1;

} catch (PDOException $e) {
    die("Erreur de base de données : " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="resource/css/global.css">
    <link rel="stylesheet" href="resource/css/dashboard.css">
    <title>Tableau de bord</title>

</head>
<body>

<!-- Barre supérieure -->
<div class="topbar">
    <span>Tableau de bord</span>
    <span class="user-info"><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['family_name']); ?></span>
    <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($user['first_name'] . '+' . $user['family_name']); ?>&background=0D8ABC&color=fff&background=f76111" style="width:2em;height:auto;margin-left:7px;border-radius:20%">
</div>

<!-- Barre latérale -->
<div class="sidebar">
    <a href="index.html">    
    <div class="sidebar-header">
        <img src="resource/logo/hexhal_banner.png" alt="Logo Hexhal" style="width:200px;height:auto;">
    </div>
    </a>

    <ul>
        <li class="tab-link active" data-tab="tab-1"><img src="resource/icon/home.png" class="sidebar-button">  Accueil</li>
        <li class="tab-link" data-tab="tab-2"><img src="resource/icon/info.png" class="sidebar-button">  Informations</li>

        <?php if ($isAdmin): ?>
            <li class="tab-link" id="adminMenu"><img src="resource/icon/wrench.png" class="sidebar-button">
                Administration <span class="arrow" id="adminArrow">→</span>
            </li>
            <ul class="submenu" id="adminSubMenu">
                <li class="tab-link" data-tab="tab-6"><img src="resource/icon/briefcase.png" class="sidebar-button-2">  Apparence</li>
                <li class="tab-link" data-tab="tab-3"><img src="resource/icon/user.png" class="sidebar-button-2">  Utilisateurs</li>
                <li class="tab-link" data-tab="tab-4"><img src="resource/icon/group.png" class="sidebar-button-2">  Rôles</li>
            </ul>
        <?php endif; ?>

        <!-- Onglet "isApplied" visible si la société a isApplied = 1 -->
        <?php if ($isApplied): ?>
            <li class="tab-link" data-tab="tab-5"><img src="resource/logo/applied_logo.png" class="sidebar-button">  Applied</li>
        <?php endif; ?>

        <li onclick="window.location.href='logout.php';" style="cursor: pointer;">
            <img src="resource/icon/logout.png" class="sidebar-button">Se déconnecter
        </li>
    </ul>
</div>

<!-- Contenu principal -->
<div class="content">
    <div id="tab-1" class="tab-content active">
        <h2>Bienvenue sur le tableau de bord</h2>
        <p>Voici votre espace personnel où vous pouvez consulter vos informations et gérer d'autres paramètres.</p>
    </div>

    <div id="tab-2" class="tab-content">
        <h2>Informations de l'utilisateur</h2>
        <table border="1">
            <tr><th>Champ</th><th>Valeur</th></tr>
            <tr><td><strong>ID Utilisateur</strong></td><td><?php echo htmlspecialchars($user['user_id']); ?></td></tr>
            <tr><td><strong>Nom</strong></td><td><?php echo htmlspecialchars($user['family_name']); ?></td></tr>
            <tr><td><strong>Prénom</strong></td><td><?php echo htmlspecialchars($user['first_name']); ?></td></tr>
            <tr><td><strong>Rôle</strong></td><td><?php echo htmlspecialchars($user['role']); ?></td></tr>
            <tr><td><strong>Société</strong></td><td><?php echo htmlspecialchars($user['society']); ?></td></tr>
            <tr><td><strong>Admin</strong></td><td><?php echo $isAdmin ? 'Oui' : 'Non'; ?></td></tr>
        </table>
    </div>

    <?php if ($isAdmin): ?>
        <div id="tab-3" class="tab-content">
            <iframe
                id="dashboardIframe"
                title="Dashboard External Page"
                src="users_list.php">
            </iframe>
        </div>
        <div id="tab-4" class="tab-content">
            <iframe
                id="dashboardIframe"
                title="Dashboard External Page"
                src="roles_list.php">
            </iframe>
        </div>
        <div id="tab-6" class="tab-content">
            <iframe
                id="dashboardIframe"
                title="Dashboard External Page"
                src="edit_society.php">
            </iframe>
        </div>
    <?php endif; ?>

    <!-- Onglet "isApplied" si isApplied = 1 -->
    <?php if ($isApplied): ?>
        <div id="tab-5" class="tab-content">
        <iframe
                id="dashboardIframe"
                title="Dashboard External Page"
                src="manage_aplied.php">
            </iframe>
        </div>
    <?php endif; ?>
</div>

<script>
    const tabLinks = document.querySelectorAll('.tab-link');
    const tabContents = document.querySelectorAll('.tab-content');
    const adminMenu = document.getElementById('adminMenu');
    const adminSubMenu = document.getElementById('adminSubMenu');
    const adminArrow = document.getElementById('adminArrow');

    if (adminMenu) {
        adminMenu.addEventListener('click', function() {
            const isMenuOpen = adminSubMenu.style.display === 'block';
            adminSubMenu.style.display = isMenuOpen ? 'none' : 'block';
            adminArrow.classList.toggle('down', !isMenuOpen);
        });
    }

    tabLinks.forEach(link => {
        link.addEventListener('click', function() {
            tabLinks.forEach(item => item.classList.remove('active'));
            tabContents.forEach(item => item.classList.remove('active'));

            this.classList.add('active');
            document.getElementById(this.getAttribute('data-tab')).classList.add('active');
        });
    });
</script>

</body>
</html>