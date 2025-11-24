<?php
// data.php
define('USER_FILE', 'data/users.json');
define('TASK_FILE', 'data/tasks.json');

// Fonction pour lire un JSON
function get_json($file) {
    if (!file_exists($file)) return [];
    $content = file_get_contents($file);
    return json_decode($content, true) ?? [];
}

// Fonction pour écrire dans un JSON
function save_json($file, $data) {
    file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT));
}

// Initialisation des fichiers s'ils n'existent pas
if (!file_exists('data')) { mkdir('data'); }

if (!file_exists(USER_FILE)) {
    $default_users = [
        ['username' => 'admin', 'password' => password_hash('Fire_pro_89!', PASSWORD_DEFAULT)]
    ];
    save_json(USER_FILE, $default_users);
}

if (!file_exists(TASK_FILE)) { save_json(TASK_FILE, []); }
?>