<?php
// conf/data.php

define('DATA_DIR', __DIR__ . '/data/');
define('USER_FILE', DATA_DIR . 'users.json');
define('TASK_FILE', DATA_DIR . 'tasks.json');

function get_json($file) {
    if (!file_exists($file)) return [];
    $content = file_get_contents($file);
    return json_decode($content, true) ?? [];
}

function save_json($file, $data) {
    file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT));
}

if (!is_dir(DATA_DIR)) { mkdir(DATA_DIR, 0755, true); }

if (!file_exists(USER_FILE)) {
    $default_users = [
        [
            'username' => 'admin',
            'password' => password_hash('root', PASSWORD_DEFAULT),
            'role' => 'admin'
        ]
    ];
    save_json(USER_FILE, $default_users);
}

if (!file_exists(TASK_FILE)) { save_json(TASK_FILE, []); }
?>