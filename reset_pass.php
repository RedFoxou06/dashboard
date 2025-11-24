<?php
require 'data.php';

$nouveau_pass = "Fire_pro_89!";

// 2. On charge les utilisateurs
$users = get_json(USER_FILE);
$trouve = false;

// 3. On cherche 'admin' et on met à jour
foreach ($users as &$user) {
    if ($user['username'] === 'admin') {
        $user['password'] = password_hash($nouveau_pass, PASSWORD_DEFAULT);
        $trouve = true;
        break;
    }
}

if ($trouve) {
    save_json(USER_FILE, $users);
    echo "✅ Le mot de passe de 'admin' a été modifié avec succès !<br>";
    echo "⚠️ <strong>IMPORTANT :</strong> Supprimez ce fichier (reset_pass.php) de votre serveur maintenant.";
} else {
    echo "❌ Erreur : L'utilisateur 'admin' n'a pas été trouvé dans le fichier data/users.json";
}
?>