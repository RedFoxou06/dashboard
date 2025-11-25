// --- UTILITAIRES ---
function closeModal(modalId) {
    document.getElementById(modalId).classList.remove('active');
}

function toggleUserMenu() {
    document.getElementById("userMenu").classList.toggle("active");
}

function openAdminPanel() {
    document.getElementById('adminPanelModal').classList.add('active');
}

// Fermeture au clic dehors
window.onclick = function(event) {
    if (event.target.classList.contains('modal-overlay')) {
        event.target.classList.remove('active');
    }
    // Ferme le menu user si on clique ailleurs
    if (!event.target.matches('.user-avatar') && !event.target.matches('.user-avatar-container')) {
        var dropdowns = document.getElementsByClassName("user-dropdown");
        for (var i = 0; i < dropdowns.length; i++) {
            if (dropdowns[i].classList.contains('active')) {
                dropdowns[i].classList.remove('active');
            }
        }
    }
}

function openProfileModal(targetName, isReadOnly) {
    document.getElementById('profileTargetUser').value = targetName;

    const nameInput = document.getElementById('profileUsername');
    nameInput.value = targetName;

    // Logique de lecture seule
    if (isReadOnly) {
        nameInput.setAttribute('readonly', true);
        nameInput.style.backgroundColor = "#e9ecef"; // Gris
        nameInput.style.cursor = "not-allowed";
        document.getElementById('profileHelp').style.display = "block";
    } else {
        nameInput.removeAttribute('readonly');
        nameInput.style.backgroundColor = "#FAFAFA"; // Blanc
        nameInput.style.cursor = "text";
        document.getElementById('profileHelp').style.display = "none";
    }

    document.getElementById('profileModal').classList.add('active');

    // On ferme le menu user s'il est ouvert pour faire propre
    const userMenu = document.getElementById("userMenu");
    if(userMenu) userMenu.classList.remove("active");
}

// --- GESTION TÂCHES (AJOUT / EDIT) ---
const modalElement = document.getElementById('taskModal');
const headerTitle = document.getElementById('modalHeaderTitle');
const inputId = document.getElementById('modalTaskId');
const inputTitle = document.getElementById('modalTitle');
const inputDesc = document.getElementById('modalDesc');
const inputDate = document.getElementById('modalDate');
const inputPriority = document.getElementById('modalPriority');

// Ouvrir en mode AJOUT
function openAddModal() {
    inputId.value = "";
    inputTitle.value = "";
    inputDesc.value = "";
    inputDate.value = "";
    inputPriority.value = "medium"; // Par défaut

    headerTitle.innerText = "✨ Nouvelle Tâche";
    modalElement.classList.add('active');
}

// Ouvrir en mode EDITION
function openEditModal(btn) {
    inputId.value = btn.getAttribute('data-id');
    inputTitle.value = btn.getAttribute('data-title');
    inputDesc.value = btn.getAttribute('data-desc');
    inputDate.value = btn.getAttribute('data-date');
    inputPriority.value = btn.getAttribute('data-priority');

    headerTitle.innerText = "✏️ Modifier la tâche";
    modalElement.classList.add('active');
}

// --- GESTION SUPPRESSION ---
function openDeleteModal(taskId) {
    document.getElementById('deleteTaskId').value = taskId;
    document.getElementById('deleteModal').classList.add('active');
}

function openDeleteUserModal(username) {
    document.getElementById('deleteUserName').value = username;
    // On affiche le nom de la personne qu'on va supprimer pour être sûr
    document.getElementById('deleteUserTargetDisplay').innerText = username;
    document.getElementById('deleteUserModal').classList.add('active');
}