<?php
session_start();
require __DIR__ . '/../conf/data.php';

if (!isset($_SESSION['user'])) { header("Location: index.php"); exit; }

$current_user = $_SESSION['user'];
$tasks = get_json(TASK_FILE);
$users = get_json(USER_FILE);

// --- TRAITEMENT DES ACTIONS (POST) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // 1. MISE À JOUR PROFIL
    if (isset($_POST['update_profile'])) {
        $new_username = trim($_POST['new_username']);
        $new_password = $_POST['new_password'];
        if (!empty($new_username)) {
            foreach ($users as &$u) {
                if ($u['username'] === $current_user) {
                    $u['username'] = $new_username;
                    if (!empty($new_password)) $u['password'] = password_hash($new_password, PASSWORD_DEFAULT);
                    break;
                }
            }
            save_json(USER_FILE, $users);
            if ($new_username !== $current_user) {
                foreach ($tasks as &$t) { if ($t['user'] === $current_user) $t['user'] = $new_username; }
                save_json(TASK_FILE, $tasks);
                $_SESSION['user'] = $new_username;
                $current_user = $new_username;
            }
            header("Location: dashboard.php"); exit;
        }
    }

    // 2. SAUVEGARDE TÂCHE (Avec Priorité)
    if (isset($_POST['save_task'])) {
        $id = $_POST['task_id'] ?? '';
        $priority = $_POST['priority'] ?? 'medium'; // Valeur par défaut

        if (!empty($id)) {
            foreach ($tasks as &$t) {
                if ($t['id'] === $id && $t['user'] === $current_user) {
                    $t['title'] = $_POST['title'];
                    $t['desc'] = $_POST['desc'];
                    $t['date'] = $_POST['date'];
                    $t['priority'] = $priority; // Sauvegarde de la priorité
                    break;
                }
            }
        } else {
            $tasks[] = [
                'id' => uniqid(),
                'user' => $current_user,
                'title' => $_POST['title'],
                'desc' => $_POST['desc'],
                'date' => $_POST['date'],
                'priority' => $priority, // Sauvegarde de la priorité
                'status' => 'todo'
            ];
        }
        save_json(TASK_FILE, $tasks);
        header("Location: dashboard.php"); exit;
    }

    // 3. ACTIONS (MOVE / DELETE)
    if (isset($_POST['action_type'])) {
        $target_id = $_POST['task_id'];
        foreach ($tasks as $k => $t) {
            if ($t['id'] === $target_id && $t['user'] === $current_user) {
                if ($_POST['action_type'] === 'delete') {
                    array_splice($tasks, $k, 1);
                } elseif ($_POST['action_type'] === 'move') {
                    $tasks[$k]['status'] = $_POST['new_status'];
                }
                break;
            }
        }
        save_json(TASK_FILE, $tasks);
        header("Location: dashboard.php"); exit;
    }
}

// FILTRE ET TRI
$my_tasks = array_filter($tasks, fn($t) => $t['user'] === $current_user);
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
        <div class="logo-mini">
            <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M9 11L12 14L22 4" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/><path d="M21 12V19C21 20.1046 20.1046 21 19 21H5C3.89543 21 3 20.1046 3 19V5C3 3.89543 3.89543 3 5 3H16" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"/></svg>
        </div>
        <div class="header-title">
            <h1>Dashboard</h1>
            <div class="header-subtitle">Bienvenue sur votre dashboard <?= htmlspecialchars($current_user) ?></div>
        </div>
    </div>
    <div class="user-section">
        <div class="user-info"><div class="user-name"><?= htmlspecialchars($current_user) ?></div><div class="user-badge">👤 Utilisateur actif</div></div>
        <div class="user-avatar-container">
            <div class="user-avatar" onclick="toggleUserMenu()"><?= strtoupper(substr($current_user, 0, 1)) ?></div>
            <div class="user-dropdown" id="userMenu">
                <div class="dropdown-item" onclick="openProfileModal()">✏️ Modifier mes données</div>
                <div class="dropdown-divider"></div>
                <a href="logout.php" class="dropdown-item text-danger">🚪 Déconnexion</a>
            </div>
        </div>
    </div>
</div>

<div class="action-bar"><button class="btn-create" onclick="openAddModal()"><span>+</span> Nouvelle Tâche</button></div>

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
        <div class="modal-header"><h2 class="modal-title" id="modalHeaderTitle">Ajouter une tâche</h2><button class="modal-close" onclick="closeModal('taskModal')">×</button></div>
        <form method="POST">
            <input type="hidden" name="task_id" id="modalTaskId">

            <div class="modal-form-group"><label>Titre</label><input type="text" name="title" id="modalTitle" required placeholder="Ex: Réunion client"></div>

            <div class="modal-form-group">
                <label>Niveau de priorité</label>
                <select name="priority" id="modalPriority">
                    <option value="low">🟢 Basse</option>
                    <option value="medium" selected>🟠 Moyenne</option>
                    <option value="high">🔴 Urgent</option>
                </select>
            </div>

            <div class="modal-form-group"><label>Description</label><textarea name="desc" id="modalDesc" placeholder="Détails..."></textarea></div>
            <div class="modal-form-group"><label>Date limite</label><input type="date" name="date" id="modalDate"></div>

            <button type="submit" name="save_task" class="btn-save">Enregistrer</button>
        </form>
    </div>
</div>

<div class="modal-overlay" id="profileModal">
    <div class="modal-content">
        <div class="modal-header"><h2 class="modal-title">👤 Mon Profil</h2><button class="modal-close" onclick="closeModal('profileModal')">×</button></div>
        <form method="POST">
            <input type="hidden" name="update_profile" value="1">
            <div class="modal-form-group"><label>Nom d'utilisateur</label><input type="text" name="new_username" value="<?= htmlspecialchars($current_user) ?>" required></div>
            <div class="modal-form-group"><label>Nouveau mot de passe</label><input type="password" name="new_password" placeholder="••••••••"></div>
            <button type="submit" class="btn-save">Mettre à jour</button>
        </form>
    </div>
</div>

<div class="modal-overlay" id="deleteModal">
    <div class="modal-content">
        <div class="modal-header"><h2 class="modal-title" style="background: none; -webkit-text-fill-color: var(--danger-color);">⚠️ Suppression</h2><button class="modal-close" onclick="closeModal('deleteModal')">×</button></div>
        <p style="color: var(--text-light); text-align: center; margin-bottom: 20px;">Êtes-vous sûr de vouloir supprimer cette tâche ?<br>Cette action est irréversible.</p>
        <form method="POST" class="btn-grid">
            <input type="hidden" name="task_id" id="deleteTaskId">
            <input type="hidden" name="action_type" value="delete">
            <button type="button" class="btn-secondary" onclick="closeModal('deleteModal')">Annuler</button>
            <button type="submit" class="btn-danger">Oui, Supprimer</button>
        </form>
    </div>
</div>

<?php
function render_card($t, $status) {
    // Gestion de la priorité
    $p = $t['priority'] ?? 'medium';
    $pLabel = ($p === 'high') ? 'Urgent' : (($p === 'low') ? 'Faible' : 'Normal');

    $dataParams = sprintf('data-id="%s" data-title="%s" data-desc="%s" data-date="%s" data-priority="%s"',
        htmlspecialchars($t['id']),
        htmlspecialchars($t['title']),
        htmlspecialchars($t['desc']),
        htmlspecialchars($t['date']),
        htmlspecialchars($p)
    );
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