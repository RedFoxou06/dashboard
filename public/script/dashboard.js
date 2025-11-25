function closeModal(modalId) { document.getElementById(modalId).classList.remove('active'); }
function toggleUserMenu() { document.getElementById("userMenu").classList.toggle("active"); }
function openProfileModal() { document.getElementById('profileModal').classList.add('active'); document.getElementById("userMenu").classList.remove("active"); }

window.onclick = function(event) {
    if (event.target.classList.contains('modal-overlay')) { event.target.classList.remove('active'); }
    if (!event.target.matches('.user-avatar') && !event.target.matches('.user-avatar-container')) {
        var dropdowns = document.getElementsByClassName("user-dropdown");
        for (var i = 0; i < dropdowns.length; i++) { if (dropdowns[i].classList.contains('active')) { dropdowns[i].classList.remove('active'); } }
    }
}

const modalElement = document.getElementById('taskModal');
const headerTitle = document.getElementById('modalHeaderTitle');
const inputId = document.getElementById('modalTaskId');
const inputTitle = document.getElementById('modalTitle');
const inputDesc = document.getElementById('modalDesc');
const inputDate = document.getElementById('modalDate');
const inputPriority = document.getElementById('modalPriority'); // Nouveau champ

function openAddModal() {
    inputId.value = ""; inputTitle.value = ""; inputDesc.value = ""; inputDate.value = "";
    inputPriority.value = "medium"; // Reset priorité
    headerTitle.innerText = "✨ Nouvelle Tâche"; modalElement.classList.add('active');
}
function openEditModal(btn) {
    inputId.value = btn.getAttribute('data-id');
    inputTitle.value = btn.getAttribute('data-title');
    inputDesc.value = btn.getAttribute('data-desc');
    inputDate.value = btn.getAttribute('data-date');
    inputPriority.value = btn.getAttribute('data-priority'); // Charge la priorité

    headerTitle.innerText = "✏️ Modifier la tâche"; modalElement.classList.add('active');
}

function openDeleteModal(taskId) {
    document.getElementById('deleteTaskId').value = taskId;
    document.getElementById('deleteModal').classList.add('active');
}