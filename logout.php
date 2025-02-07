<?php
session_start(); // Démarrer la session

// Détruire toutes les variables de session
session_unset();

// Détruire la session
session_destroy();

// Rediriger l'utilisateur vers la page de connexion
header("Location: login.php");
exit();
?>
