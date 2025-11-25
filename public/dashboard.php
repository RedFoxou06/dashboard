<?php
session_start();
require __DIR__ . '/../conf/data.php';

if (!isset($_SESSION['user'])) { header("Location: index.php"); exit; }

$users = get_json(USER_FILE);
$tasks = get_json(TASK_FILE);

// Récupération des infos de l'utilisateur connecté
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
</head>
<body>

<div class="header">
    <div class="header-left">
        <div class="logo-mini"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M9 11L12 14L22 4"/><path d="M21 12V19C21 20.1046 20.1046 21 19 21H5C3.89543 21 3 20.1046 3 19V5C3 3.89543 3.89543 3 5 3H16"/></svg></div>
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
            <button class="btn-admin" onclick="openAdminPanel()">👑 Admin Panel</button>
        <?php endif; ?>
        <div class="user-info"><div class="user-name"><?= htmlspecialchars($current_user_name) ?></div><div class="user-badge"><?= $is_admin ? 'Administrateur' : 'Utilisateur' ?></div></div>
        <div class="user-avatar-container">
            <div class="user-avatar" onclick="toggleUserMenu()"><?= strtoupper(substr($current_user_name, 0, 1)) ?></div>
            <div class="user-dropdown" id="userMenu">
                <div class="dropdown-item" onclick="openProfileModal('<?= $current_user_name ?>', <?= $is_admin ? 'false' : 'true' ?>)">✏️ Mon Profil</div>
                <div class="dropdown-divider"></div>
                <a href="logout.php" class="dropdown-item text-danger">🚪 Déconnexion</a>
            </div>
        </div>
    </div>
</div>

<div class="action-bar"><button class="btn-create" onclick="openAddModal()"><span>+</span> Nouvelle Tâche pour <?= htmlspecialchars($view_user) ?></button></div>

<div class="board">
    <div class="column status-todo">
        <div class="column-header"><div class="column-title">📋 À Faire</div><span class="count-badge"><?= count($cols['todo']) ?></span></div>
        <?php foreach($cols['todo'] as $t): render_card($t, 'todo'); endforeach; ?>
    </div>
    <div class="column status-inprogress">
        <div class="column-header"><div class="column-title">⚡ En Cours</div><span class="count-badge"><?= count($cols['inprogress']) ?></span></div>
        <?php foreach($cols['inprogress'] as $t): render_card($t, 'inprogress'); endforeach; ?>
    </div>
    <div class="column status-done">
        <div class="column-header"><div class="column-title">✅ Terminé</div><span class="count-badge"><?= count($cols['done']) ?></span></div>
        <?php foreach($cols['done'] as $t): render_card($t, 'done'); endforeach; ?>
    </div>
</div>

<div class="modal-overlay" id="taskModal">
    <div class="modal-content">
        <div class="modal-header"><h2 class="modal-title" id="modalHeaderTitle">Tâche</h2><button class="modal-close" onclick="closeModal('taskModal')">×</button></div>
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
        <div class="modal-header"><h2 class="modal-title">👤 Modifier le profil</h2><button class="modal-close" onclick="closeModal('profileModal')">×</button></div>
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
        <div class="modal-header"><h2 class="modal-title" style="color:var(--danger-color)">⚠️ Suppression Tâche</h2><button class="modal-close" onclick="closeModal('deleteModal')">×</button></div>
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
        <div class="modal-header"><h2 class="modal-title" style="color:var(--danger-color)">⚠️ Suppression Utilisateur</h2><button class="modal-close" onclick="closeModal('deleteUserModal')">×</button></div>
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
            <div class="modal-header"><h2 class="modal-title">👑 Gestion Utilisateurs</h2><button class="modal-close" onclick="closeModal('adminPanelModal')">×</button></div>
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
                            <td style="padding:12px;"><strong><?= htmlspecialchars($u['username']) ?></strong> <?php if(($u['role']??'user') === 'admin') echo '👑'; ?></td>
                            <td class="admin-actions">
                                <a href="?view=<?= $u['username'] ?>" class="icon-btn" title="Voir les tâches" style="text-decoration:none; display:flex; align-items:center; justify-content:center;">👁️</a>
                                <button class="icon-btn" onclick="openProfileModal('<?= $u['username'] ?>', false)" title="Modifier">✏️</button>
                                <?php if($u['username'] !== $current_user_name): ?>
                                    <button class="icon-btn delete-user-btn" onclick="openDeleteUserModal('<?= $u['username'] ?>')" title="Supprimer">🗑️</button>
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
                <button class="icon-btn edit-btn" <?= $dataParams ?> onclick="openEditModal(this)">✏️</button>
                <button class="icon-btn delete-btn" onclick="openDeleteModal('<?= $t['id'] ?>')">🗑️</button>
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