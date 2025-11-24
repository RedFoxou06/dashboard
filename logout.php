<?php
session_start(); // On récupère la session en cours
session_destroy(); // On la détruit (déconnexion)
header("Location: index.php"); // On redirige vers la page de login
exit;
?>