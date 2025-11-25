<?php
session_start();
require __DIR__ . '/../conf/data.php';

if (!isset($_SESSION['user'])) { header("Location: index.php"); exit; }

$users = get_json(USER_FILE);
$tasks = get_json(TASK_FILE);

$current_user_name = $_SESSION['user'];
$current_user_data = null;
foreach ($users as $u) {
    if ($u['username'] === $current_user_name) {
        $current_user_data = $u;
        break;
    }
}
if (!$current_user_data) { header("Location: logout.php"); exit; }

$is_admin = ($current_user_data['role'] ?? 'user') === 'admin';
$view_user = $current_user_name;

if ($is_admin && isset($_GET['view'])) {
    $view_user = $_GET['view'];
}

// --- TRAITEMENT DES ACTIONS ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. CRÉATION USER
    if (isset($_POST['create_user']) && $is_admin) {
        $new_u = trim($_POST['username']);
        $new_p = $_POST['password'];
        $exists = false;
        foreach($users as $u) { if($u['username'] === $new_u) $exists = true; }
        if (!$exists && !empty($new_u) && !empty($new_p)) {
            $users[] = ['username' => $new_u, 'password' => password_hash($new_p, PASSWORD_DEFAULT), 'role' => 'user'];
            save_json(USER_FILE, $users);
        }
        header("Location: dashboard.php"); exit;
    }
    // 2. SUPPRESSION USER
    if (isset($_POST['delete_user']) && $is_admin) {
        $user_to_delete = $_POST['user_to_delete'];
        if ($user_to_delete !== $current_user_name) {
            $users = array_filter($users, fn($u) => $u['username'] !== $user_to_delete);
            save_json(USER_FILE, array_values($users));
            $tasks = array_filter($tasks, fn($t) => $t['user'] !== $user_to_delete);
            save_json(TASK_FILE, array_values($tasks));
        }
        header("Location: dashboard.php"); exit;
    }
    // 3. UPDATE PROFIL
    if (isset($_POST['update_profile'])) {
        $target_username = $_POST['target_username'];
        $new_username = trim($_POST['new_username']);
        $new_password = $_POST['new_password'];
        if (!$is_admin && $target_username !== $current_user_name) { die("Interdit"); }
        if (!$is_admin && $new_username !== $current_user_name) { $new_username = $current_user_name; }
        foreach ($users as &$u) {
            if ($u['username'] === $target_username) {
                $old_name = $u['username'];
                $u['username'] = $new_username;
                if (!empty($new_password)) $u['password'] = password_hash($new_password, PASSWORD_DEFAULT);
                save_json(USER_FILE, $users);
                if ($old_name !== $new_username) {
                    foreach ($tasks as &$t) { if ($t['user'] === $old_name) $t['user'] = $new_username; }
                    save_json(TASK_FILE, $tasks);
                    if ($target_username === $current_user_name) $_SESSION['user'] = $new_username;
                }
                break;
            }
        }
        $redirect = ($is_admin && $target_username !== $current_user_name) ? "?view=".$new_username : "dashboard.php";
        header("Location: " . $redirect); exit;
    }
    // 4. SAVE TASK
    if (isset($_POST['save_task'])) {
        $id = $_POST['task_id'] ?? '';
        $priority = $_POST['priority'] ?? 'medium';
        if (!empty($id)) {
            foreach ($tasks as &$t) {
                if ($t['id'] === $id) {
                    $t['title'] = $_POST['title']; $t['desc'] = $_POST['desc']; $t['date'] = $_POST['date']; $t['priority'] = $priority;
                    break;
                }
            }
        } else {
            $tasks[] = ['id' => uniqid(), 'user' => $view_user, 'title' => $_POST['title'], 'desc' => $_POST['desc'], 'date' => $_POST['date'], 'priority' => $priority, 'status' => 'todo'];
        }
        save_json(TASK_FILE, $tasks);
        header("Location: dashboard.php?view=" . $view_user); exit;
    }
    // 5. ACTION TASK
    if (isset($_POST['action_type'])) {
        $target_id = $_POST['task_id'];
        foreach ($tasks as $k => $t) {
            if ($t['id'] === $target_id) {
                if ($_POST['action_type'] === 'delete') array_splice($tasks, $k, 1);
                elseif ($_POST['action_type'] === 'move') $tasks[$k]['status'] = $_POST['new_status'];
                break;
            }
        }
        save_json(TASK_FILE, $tasks);
        header("Location: dashboard.php?view=" . $view_user); exit;
    }
}

$my_tasks = array_filter($tasks, fn($t) => $t['user'] === $view_user);
$cols = ['todo' => [], 'inprogress' => [], 'done' => []];
foreach ($my_tasks as $t) $cols[$t['status']][] = $t;
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="img/logo_64.png">
    <link rel="stylesheet" href="style/dashboard.css">
    <title>Dashboard</title>
    <style>
        /* Ajustements SVG globaux */
        .icon-btn svg, .column-title svg { width: 18px; height: 18px; stroke-width: 2; }
        .icon-btn { padding: 6px; display: inline-flex; align-items: center; justify-content: center; }
        .user-dropdown .dropdown-item svg { width: 16px; height: 16px; margin-right: 8px; color: #666; }
        .admin-badge svg { width: 14px; height: 14px; color: #FFD700; fill: #FFD700; margin-left: 5px; }

        /* Style spécifique pour les titres de modales avec SVG */
        .modal-header-custom { display: flex; align-items: center; gap: 10px; }
        .modal-header-custom svg { width: 24px; height: 24px; }

        /* Correction alignement bouton admin */
        .btn-admin { display: flex; align-items: center; gap: 6px; }
    </style>
</head>
<body>

<div class="header">
    <div class="header-left">
        <div class="logo-mini">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M9 11L12 14L22 4"/><path d="M21 12V19C21 20.1046 20.1046 21 19 21H5C3.89543 21 3 20.1046 3 19V5C3 3.89543 3.89543 3 5 3H16"/></svg>
        </div>
        <div class="header-title">
            <h1>Dashboard</h1>
            <div class="header-subtitle">
                <?php if($view_user === $current_user_name): ?>
                    Bienvenue, <?= htmlspecialchars($current_user_name) ?>
                <?php else: ?>
                    👀 Visionnage du profil : <strong><?= htmlspecialchars($view_user) ?></strong>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="user-section">
        <?php if($is_admin): ?>
            <button class="btn-admin" onclick="openAdminPanel()">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" style="width:18px; height:18px;">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                </svg>
                Admin Panel
            </button>
        <?php endif; ?>
        <div class="user-info"><div class="user-name"><?= htmlspecialchars($current_user_name) ?></div><div class="user-badge"><?= $is_admin ? 'Administrateur' : 'Utilisateur' ?></div></div>
        <div class="user-avatar-container">
            <div class="user-avatar" onclick="toggleUserMenu()"><?= strtoupper(substr($current_user_name, 0, 1)) ?></div>
            <div class="user-dropdown" id="userMenu">
                <div class="dropdown-item" onclick="openProfileModal('<?= $current_user_name ?>', <?= $is_admin ? 'false' : 'true' ?>)">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" /></svg>
                    Mon Profil
                </div>
                <div class="dropdown-divider"></div>
                <a href="logout.php" class="dropdown-item text-danger">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" style="color:#FF6B6B"><path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" /></svg>
                    Déconnexion
                </a>
            </div>
        </div>
    </div>
</div>

<div class="action-bar">
    <button class="btn-create" onclick="openAddModal()">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" style="width:20px; height:20px; margin-right:5px;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M12 4v16m8-8H4" /></svg>
        Nouvelle Tâche pour <?= htmlspecialchars($view_user) ?>
    </button>
</div>

<div class="board">
    <div class="column status-todo">
        <div class="column-header">
            <div class="column-title">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" style="color:#666"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" /></svg>
                À Faire
            </div>
            <span class="count-badge"><?= count($cols['todo']) ?></span>
        </div>
        <?php foreach($cols['todo'] as $t): render_card($t, 'todo'); endforeach; ?>
    </div>
    <div class="column status-inprogress">
        <div class="column-header">
            <div class="column-title">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" style="color:#3498db"><path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z" /></svg>
                En Cours
            </div>
            <span class="count-badge"><?= count($cols['inprogress']) ?></span>
        </div>
        <?php foreach($cols['inprogress'] as $t): render_card($t, 'inprogress'); endforeach; ?>
    </div>
    <div class="column status-done">
        <div class="column-header">
            <div class="column-title">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" style="color:#27ae60"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                Terminé
            </div>
            <span class="count-badge"><?= count($cols['done']) ?></span>
        </div>
        <?php foreach($cols['done'] as $t): render_card($t, 'done'); endforeach; ?>
    </div>
</div>

<div class="modal-overlay" id="taskModal">
    <div class="modal-content">
        <div class="modal-header">
            <h2 class="modal-title modal-header-custom">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" style="color:var(--primary-blue)">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                </svg>
                <span id="modalHeaderTitle">Tâche</span>
            </h2>
            <button class="modal-close" onclick="closeModal('taskModal')">×</button>
        </div>
        <form method="POST">
            <input type="hidden" name="task_id" id="modalTaskId">
            <div class="modal-form-group"><label>Titre</label><input type="text" name="title" id="modalTitle" required></div>
            <div class="modal-form-group"><label>Priorité</label><select name="priority" id="modalPriority"><option value="low">🟢 Basse</option><option value="medium">🟠 Moyenne</option><option value="high">🔴 Haute</option></select></div>
            <div class="modal-form-group"><label>Description</label><textarea name="desc" id="modalDesc"></textarea></div>
            <div class="modal-form-group"><label>Date limite</label><input type="date" name="date" id="modalDate"></div>
            <button type="submit" name="save_task" class="btn-save">Enregistrer</button>
        </form>
    </div>
</div>

<div class="modal-overlay" id="profileModal">
    <div class="modal-content">
        <div class="modal-header">
            <h2 class="modal-title modal-header-custom">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" style="color:var(--primary-blue)">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                </svg>
                Modifier le profil
            </h2>
            <button class="modal-close" onclick="closeModal('profileModal')">×</button>
        </div>
        <form method="POST">
            <input type="hidden" name="update_profile" value="1">
            <input type="hidden" name="target_username" id="profileTargetUser">
            <div class="modal-form-group"><label>Nom d'utilisateur</label><input type="text" name="new_username" id="profileUsername" required><small id="profileHelp" style="color:#888; display:none;">Seul l'admin peut modifier le nom.</small></div>
            <div class="modal-form-group"><label>Nouveau mot de passe</label><input type="password" name="new_password" placeholder="Laisser vide pour ne pas changer"></div>
            <button type="submit" class="btn-save">Mettre à jour</button>
        </form>
    </div>
</div>

<div class="modal-overlay" id="deleteModal">
    <div class="modal-content">
        <div class="modal-header">
            <h2 class="modal-title modal-header-custom" style="color:var(--danger-color)">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
                Suppression
            </h2>
            <button class="modal-close" onclick="closeModal('deleteModal')">×</button>
        </div>
        <p style="text-align:center; margin-bottom:20px;">Supprimer cette tâche ?</p>
        <form method="POST" class="btn-grid">
            <input type="hidden" name="task_id" id="deleteTaskId"><input type="hidden" name="action_type" value="delete">
            <button type="button" class="btn-secondary" onclick="closeModal('deleteModal')">Non</button>
            <button type="submit" class="btn-danger">Oui</button>
        </form>
    </div>
</div>

<div class="modal-overlay" id="deleteUserModal">
    <div class="modal-content">
        <div class="modal-header">
            <h2 class="modal-title modal-header-custom" style="color:var(--danger-color)">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7a4 4 0 11-8 0 4 4 0 018 0zM9 14a6 6 0 00-6 6v1h12v-1a6 6 0 00-6-6zM21 12h-6" />
                </svg>
                Suppression User
            </h2>
            <button class="modal-close" onclick="closeModal('deleteUserModal')">×</button>
        </div>
        <p style="text-align:center; margin-bottom:10px;">Voulez-vous vraiment supprimer <strong id="deleteUserTargetDisplay"></strong> ?</p>
        <p style="text-align:center; margin-bottom:20px; font-size:0.9rem; color:#666;">Toutes ses tâches seront également supprimées.</p>
        <form method="POST" class="btn-grid">
            <input type="hidden" name="user_to_delete" id="deleteUserName">
            <input type="hidden" name="delete_user" value="1">
            <button type="button" class="btn-secondary" onclick="closeModal('deleteUserModal')">Annuler</button>
            <button type="submit" class="btn-danger">Confirmer</button>
        </form>
    </div>
</div>

<?php if($is_admin): ?>
    <div class="modal-overlay" id="adminPanelModal">
        <div class="modal-content" style="max-width:700px;">
            <div class="modal-header">
                <h2 class="modal-title modal-header-custom">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" style="color:var(--primary-blue)">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                    </svg>
                    Gestion Utilisateurs
                </h2>
                <button class="modal-close" onclick="closeModal('adminPanelModal')">×</button>
            </div>
            <div style="background:#f9f9f9; padding:15px; border-radius:12px; margin-bottom:20px;">
                <h4 style="margin-bottom:10px;">Créer un nouvel utilisateur</h4>
                <form method="POST" style="display:flex; gap:10px;">
                    <input type="text" name="username" placeholder="Nom" required>
                    <input type="password" name="password" placeholder="Mot de passe" required>
                    <button type="submit" name="create_user" class="btn-create" style="padding:0.5rem 1rem; font-size:0.8rem;">Créer</button>
                </form>
            </div>
            <div style="max-height:400px; overflow-y:auto;">
                <table style="width:100%; border-collapse:collapse;">
                    <?php foreach($users as $u): ?>
                        <tr style="border-bottom:1px solid #eee;">
                            <td style="padding:12px;">
                                <strong><?= htmlspecialchars($u['username']) ?></strong>
                                <?php if(($u['role']??'user') === 'admin'): ?>
                                    <span class="admin-badge" title="Admin">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" /></svg>
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="admin-actions">
                                <a href="?view=<?= $u['username'] ?>" class="icon-btn" title="Voir les tâches" style="text-decoration:none;">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                                </a>
                                <button class="icon-btn" onclick="openProfileModal('<?= $u['username'] ?>', false)" title="Modifier">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" /></svg>
                                </button>
                                <?php if($u['username'] !== $current_user_name): ?>
                                    <button class="icon-btn delete-user-btn" onclick="openDeleteUserModal('<?= $u['username'] ?>')" title="Supprimer">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                    </button>
                                <?php else: ?><div style="width:32px;"></div><?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            </div>
            <div style="margin-top:20px; text-align:center;"><a href="dashboard.php" class="btn-secondary" style="padding:10px 20px; text-decoration:none;">Revenir à mon profil</a></div>
        </div>
    </div>
<?php endif; ?>

<?php
function render_card($t, $status) {
    $p = $t['priority'] ?? 'medium';
    $pLabel = ($p === 'high') ? 'Urgent' : (($p === 'low') ? 'Faible' : 'Normal');
    $dataParams = sprintf('data-id="%s" data-title="%s" data-desc="%s" data-date="%s" data-priority="%s"', htmlspecialchars($t['id']), htmlspecialchars($t['title']), htmlspecialchars($t['desc']), htmlspecialchars($t['date']), htmlspecialchars($p));
    ?>
    <div class="task-card">
        <span class="priority-badge p-<?= $p ?>"><?= $pLabel ?></span>
        <div class="card-header">
            <h4 class="card-title"><?= htmlspecialchars($t['title']) ?></h4>
            <div class="card-actions">
                <button class="icon-btn edit-btn" <?= $dataParams ?> onclick="openEditModal(this)">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" /></svg>
                </button>
                <button class="icon-btn delete-btn" onclick="openDeleteModal('<?= $t['id'] ?>')">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                </button>
            </div>
        </div>
        <?php if(!empty($t['desc'])): ?><div class="card-desc"><?= nl2br(htmlspecialchars($t['desc'])) ?></div><?php endif; ?>
        <div class="card-footer">
            <span class="date-badge"><?= $t['date'] ? '📅 '.date('d/m/Y', strtotime($t['date'])) : '📅 --' ?></span>
            <form method="POST" style="display:inline;">
                <input type="hidden" name="task_id" value="<?= $t['id'] ?>"><input type="hidden" name="action_type" value="move">
                <?php if($status === 'todo'): ?><input type="hidden" name="new_status" value="inprogress"><button class="move-btn">Go &rarr;</button>
                <?php elseif($status === 'inprogress'): ?><input type="hidden" name="new_status" value="done"><button class="move-btn">Fini &rarr;</button>
                <?php elseif($status === 'done'): ?><input type="hidden" name="new_status" value="inprogress"><button class="move-btn">Rouvrir</button><?php endif; ?>
            </form>
        </div>
    </div>
    <?php
}
?>

<script src="script/dashboard.js"></script>
</body>
</html>