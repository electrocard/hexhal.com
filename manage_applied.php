<?php
if (!isset($_SESSION['user_id'])) {
    // Rediriger vers la page de connexion
    header("Location: login.php");
    exit(); // Assurez-vous d'appeler exit() après header() pour arrêter l'exécution du script
}
?>
