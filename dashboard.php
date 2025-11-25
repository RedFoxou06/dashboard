<?php
session_start();
require 'data.php';

if (!isset($_SESSION['user'])) { header("Location: index.php"); exit; }

$current_user = $_SESSION['user'];
$tasks = get_json(TASK_FILE);
$users = get_json(USER_FILE);

// --- TRAITEMENT DES ACTIONS (POST) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // 1. MISE À JOUR DU PROFIL (NOUVEAU)
    if (isset($_POST['update_profile'])) {
        $new_username = trim($_POST['new_username']);
        $new_password = $_POST['new_password'];

        // Vérif simple : le username ne doit pas être vide
        if (!empty($new_username)) {
            // A. Mise à jour de l'utilisateur dans users.json
            foreach ($users as &$u) {
                if ($u['username'] === $current_user) {
                    $u['username'] = $new_username;
                    if (!empty($new_password)) {
                        $u['password'] = password_hash($new_password, PASSWORD_DEFAULT);
                    }
                    break;
                }
            }
            save_json(USER_FILE, $users);

            // B. Mise à jour des tâches (Migration des tâches vers le nouveau nom)
            if ($new_username !== $current_user) {
                foreach ($tasks as &$t) {
                    if ($t['user'] === $current_user) {
                        $t['user'] = $new_username;
                    }
                }
                save_json(TASK_FILE, $tasks);

                // C. Mise à jour de la session
                $_SESSION['user'] = $new_username;
                $current_user = $new_username;
            }

            // On recharge pour voir les changements
            header("Location: dashboard.php");
            exit;
        }
    }

    // 2. SAUVEGARDE TÂCHE (AJOUT OU MODIFICATION)
    if (isset($_POST['save_task'])) {
        $id = $_POST['task_id'] ?? null;

        if ($id) {
            foreach ($tasks as &$t) {
                if ($t['id'] === $id && $t['user'] === $current_user) {
                    $t['title'] = $_POST['title'];
                    $t['desc'] = $_POST['desc'];
                    $t['date'] = $_POST['date'];
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
                'status' => 'todo'
            ];
        }
        save_json(TASK_FILE, $tasks);
        header("Location: dashboard.php");
        exit;
    }

    // 3. AUTRES ACTIONS (MOVE / DELETE)
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
        header("Location: dashboard.php");
        exit;
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
    <title>Dashboard</title>
    <style>
        /* --- Styles existants (Base) --- */
        :root {
            --primary-blue: #b2e3ff; --dark-blue: #8cd4ff; --light-blue: #d4f1ff; --very-light-blue: #e8f7ff;
            --bg-gradient: linear-gradient(135deg, #f0faff 0%, #FFFFFF 100%);
            --text-dark: #2D2D2D; --text-light: #6B6B6B; --text-lighter: #9CA3AF;
            --shadow-soft: rgba(178, 227, 255, 0.25); --shadow-card: rgba(0, 0, 0, 0.04);
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: var(--bg-gradient); color: var(--text-dark); min-height: 100vh; padding: 20px; }

        /* --- Header & User Menu Styles --- */
        .header { background: white; padding: 1.5rem 2rem; border-radius: 20px; box-shadow: 0 4px 20px var(--shadow-card); margin-bottom: 2rem; display: flex; justify-content: space-between; align-items: center; }
        .header-left { display: flex; align-items: center; gap: 1rem; }
        .logo-mini { width: 45px; height: 45px; background: linear-gradient(135deg, var(--primary-blue), var(--dark-blue)); border-radius: 12px; display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 12px var(--shadow-soft); }
        .logo-mini svg { width: 24px; height: 24px; filter: brightness(0) invert(1); }
        .header-title h1 { font-size: 1.5rem; font-weight: 700; background: linear-gradient(135deg, var(--dark-blue), var(--primary-blue)); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        .header-subtitle { font-size: 0.85rem; color: var(--text-light); }

        .user-section { display: flex; align-items: center; gap: 1rem; position: relative; } /* Added relative for dropdown */
        .user-info { text-align: right; }
        .user-name { font-weight: 600; color: var(--text-dark); font-size: 0.95rem; }
        .user-badge { font-size: 0.75rem; color: var(--text-lighter); }

        /* Avatar Interactif */
        .user-avatar-container { position: relative; cursor: pointer; }
        .user-avatar { width: 45px; height: 45px; background: linear-gradient(135deg, var(--primary-blue), var(--dark-blue)); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-weight: 700; font-size: 1.1rem; box-shadow: 0 4px 12px var(--shadow-soft); transition: transform 0.2s; }
        .user-avatar:hover { transform: scale(1.05); }

        /* Dropdown Menu */
        .user-dropdown {
            position: absolute; top: 60px; right: 0; background: white; width: 220px;
            border-radius: 16px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); padding: 10px;
            display: none; z-index: 100; transform-origin: top right; animation: scaleIn 0.2s ease;
            border: 1px solid #f0f0f0;
        }
        .user-dropdown.active { display: block; }
        @keyframes scaleIn { from { transform: scale(0.9); opacity: 0; } to { transform: scale(1); opacity: 1; } }

        .dropdown-item {
            display: flex; align-items: center; gap: 10px; padding: 12px 15px;
            text-decoration: none; color: var(--text-dark); border-radius: 10px;
            font-size: 0.9rem; font-weight: 500; transition: background 0.2s; cursor: pointer;
        }
        .dropdown-item:hover { background: var(--very-light-blue); color: var(--dark-blue); }
        .dropdown-divider { height: 1px; background: #f0f0f0; margin: 5px 0; }
        .text-danger { color: #FF6B6B; }
        .dropdown-item.text-danger:hover { background: #FFF0F0; color: #FF6B6B; }

        /* --- Quick Add & Board Styles (Inchangés) --- */
        .quick-add { background: white; padding: 1.8rem 2rem; border-radius: 20px; box-shadow: 0 4px 20px var(--shadow-card); margin-bottom: 2rem; }
        .quick-add-title { font-size: 1.1rem; font-weight: 700; color: var(--text-dark); margin-bottom: 1.2rem; }
        .form-grid { display: grid; grid-template-columns: 2fr 2fr 1fr auto; gap: 1rem; align-items: end; }
        .form-group label { display: block; font-size: 0.85rem; font-weight: 600; color: var(--text-light); margin-bottom: 0.5rem; }
        input[type="text"], input[type="date"], input[type="password"], textarea { width: 100%; padding: 0.9rem 1rem; border: 2px solid #F0F0F0; border-radius: 12px; font-family: inherit; font-size: 0.95rem; transition: all 0.3s ease; outline: none; background: #FAFAFA; }
        input:focus, textarea:focus { border-color: var(--primary-blue); background: white; box-shadow: 0 0 0 4px rgba(178, 227, 255, 0.15); }
        .btn-add { padding: 0.9rem 2rem; background: linear-gradient(135deg, var(--primary-blue), var(--dark-blue)); color: white; border: none; border-radius: 12px; font-weight: 700; cursor: pointer; box-shadow: 0 4px 12px var(--shadow-soft); }

        .board { display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 1.5rem; align-items: start; }
        .column { background: rgba(255, 255, 255, 0.6); backdrop-filter: blur(10px); border-radius: 20px; padding: 1.5rem; border: 2px solid rgba(255, 255, 255, 0.8); }
        .column-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; }
        .column-title { font-weight: 700; color: var(--text-dark); display: flex; align-items: center; gap: 0.7rem;}
        .count-badge { background: var(--very-light-blue); padding: 0.3rem 0.7rem; border-radius: 20px; font-size: 0.85rem; font-weight: 600; color: var(--dark-blue); }

        .task-card { background: white; padding: 1.3rem; border-radius: 16px; margin-bottom: 1rem; box-shadow: 0 2px 8px var(--shadow-card); transition: all 0.3s ease; border-left: 4px solid; }
        .task-card:hover { transform: translateY(-4px); box-shadow: 0 8px 24px var(--shadow-soft); }
        .status-todo .task-card { border-left-color: var(--light-blue); }
        .status-inprogress .task-card { border-left-color: var(--primary-blue); }
        .status-done .task-card { border-left-color: var(--dark-blue); opacity: 0.85; }
        .card-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 0.8rem; }
        .card-title { font-weight: 700; font-size: 1rem; }
        .card-actions { display: flex; gap: 0.5rem; }
        .icon-btn { background: var(--very-light-blue); border: none; width: 32px; height: 32px; border-radius: 8px; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: all 0.2s; }
        .icon-btn:hover { background: var(--light-blue); transform: scale(1.1); }
        .card-desc { font-size: 0.9rem; color: var(--text-light); margin-bottom: 1rem; }
        .card-footer { display: flex; justify-content: space-between; align-items: center; padding-top: 0.8rem; border-top: 1px solid #F0F0F0; }
        .date-badge { font-size: 0.85rem; color: var(--text-lighter); }
        .move-btn { padding: 0.5rem 1rem; background: linear-gradient(135deg, var(--primary-blue), var(--dark-blue)); color: white; border: none; border-radius: 8px; font-size: 0.85rem; font-weight: 600; cursor: pointer; }

        /* --- MODAL Styles --- */
        .modal-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.5); backdrop-filter: blur(4px); display: none; justify-content: center; align-items: center; z-index: 1000; }
        .modal-overlay.active { display: flex; }
        .modal-content { background: white; padding: 2rem; border-radius: 24px; width: 90%; max-width: 500px; box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3); animation: slideUp 0.3s ease; }
        @keyframes slideUp { from { transform: translateY(30px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
        .modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; }
        .modal-title { font-size: 1.5rem; font-weight: 700; background: linear-gradient(135deg, var(--dark-blue), var(--primary-blue)); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        .modal-close { background: var(--very-light-blue); border: none; width: 36px; height: 36px; border-radius: 10px; font-size: 1.5rem; cursor: pointer; color: var(--dark-blue); }
        .modal-form-group { margin-bottom: 1.5rem; }
        .btn-save { width: 100%; padding: 1rem; background: linear-gradient(135deg, var(--primary-blue), var(--dark-blue)); color: white; border: none; border-radius: 12px; font-weight: 700; cursor: pointer; margin-top: 10px; }

        @media (max-width: 768px) { .form-grid { grid-template-columns: 1fr; } .header { flex-direction: column; gap: 1rem; text-align: center; } .header-left, .user-section { flex-direction: column; } .user-dropdown { right: 50%; transform: translateX(50%); top: 120px; } }
    </style>
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
        <div class="user-info">
            <div class="user-name"><?= htmlspecialchars($current_user) ?></div>
            <div class="user-badge">👤 Utilisateur actif</div>
        </div>

        <div class="user-avatar-container">
            <div class="user-avatar" onclick="toggleUserMenu()">
                <?= strtoupper(substr($current_user, 0, 1)) ?>
            </div>
            <div class="user-dropdown" id="userMenu">
                <div class="dropdown-item" onclick="openProfileModal()">
                    ✏️ Modifier mes données
                </div>
                <div class="dropdown-divider"></div>
                <a href="logout.php" class="dropdown-item text-danger">
                    🚪 Déconnexion
                </a>
            </div>
        </div>
    </div>
</div>

<div class="quick-add">
    <div class="quick-add-title">➕ Ajouter une nouvelle tâche</div>
    <form method="POST" class="form-grid">
        <div class="form-group"><label>Titre</label><input type="text" name="title" placeholder="Nom de la tâche..." required></div>
        <div class="form-group"><label>Description</label><input type="text" name="desc" placeholder="Détails (optionnel)"></div>
        <div class="form-group"><label>Date limite</label><input type="date" name="date"></div>
        <button type="submit" name="save_task" class="btn-add">Ajouter</button>
    </form>
</div>

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

<div class="modal-overlay" id="editModal">
    <div class="modal-content">
        <div class="modal-header"><h2 class="modal-title">✏️ Modifier la tâche</h2><button class="modal-close" onclick="closeModal('editModal')">×</button></div>
        <form method="POST">
            <input type="hidden" name="task_id" id="modalTaskId">
            <div class="modal-form-group"><label>Titre</label><input type="text" name="title" id="modalTitle" required></div>
            <div class="modal-form-group"><label>Description</label><textarea name="desc" id="modalDesc"></textarea></div>
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

            <div class="modal-form-group">
                <label>Nom d'utilisateur</label>
                <input type="text" name="new_username" value="<?= htmlspecialchars($current_user) ?>" required>
            </div>

            <div class="modal-form-group">
                <label>Nouveau mot de passe (Laisser vide si inchangé)</label>
                <input type="password" name="new_password" placeholder="••••••••">
            </div>

            <button type="submit" class="btn-save">Mettre à jour</button>
        </form>
    </div>
</div>

<?php
function render_card($t, $status) {
    $dataParams = sprintf('data-id="%s" data-title="%s" data-desc="%s" data-date="%s"', htmlspecialchars($t['id']), htmlspecialchars($t['title']), htmlspecialchars($t['desc']), htmlspecialchars($t['date']));
    ?>
    <div class="task-card">
        <div class="card-header">
            <h4 class="card-title"><?= htmlspecialchars($t['title']) ?></h4>
            <div class="card-actions">
                <button class="icon-btn edit-btn" <?= $dataParams ?> onclick="openTaskModal(this)">✏️</button>
                <form method="POST" style="display:inline;" onsubmit="return confirm('Supprimer ?');"><input type="hidden" name="task_id" value="<?= $t['id'] ?>"><input type="hidden" name="action_type" value="delete"><button class="icon-btn delete-btn">🗑️</button></form>
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

<script>
    // --- GESTION DES MODALES ---
    function closeModal(modalId) {
        document.getElementById(modalId).classList.remove('active');
    }

    // Fermer si clic en dehors
    window.onclick = function(event) {
        if (event.target.classList.contains('modal-overlay')) {
            event.target.classList.remove('active');
        }
        // Fermer le menu si clic ailleurs
        if (!event.target.matches('.user-avatar') && !event.target.matches('.user-avatar-container')) {
            var dropdowns = document.getElementsByClassName("user-dropdown");
            for (var i = 0; i < dropdowns.length; i++) {
                var openDropdown = dropdowns[i];
                if (openDropdown.classList.contains('active')) {
                    openDropdown.classList.remove('active');
                }
            }
        }
    }

    // --- GESTION TÂCHES ---
    const taskModal = document.getElementById('editModal');
    const modalTaskId = document.getElementById('modalTaskId');
    const modalTitle = document.getElementById('modalTitle');
    const modalDesc = document.getElementById('modalDesc');
    const modalDate = document.getElementById('modalDate');

    function openTaskModal(btn) {
        modalTaskId.value = btn.getAttribute('data-id');
        modalTitle.value = btn.getAttribute('data-title');
        modalDesc.value = btn.getAttribute('data-desc');
        modalDate.value = btn.getAttribute('data-date');
        taskModal.classList.add('active');
    }

    // --- GESTION MENU & PROFIL ---
    function toggleUserMenu() {
        document.getElementById("userMenu").classList.toggle("active");
    }

    function openProfileModal() {
        document.getElementById('profileModal').classList.add('active');
        // On ferme le menu pour faire propre
        document.getElementById("userMenu").classList.remove("active");
    }
</script>

</body>
</html>