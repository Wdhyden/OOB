/**
 * OOB - private_messages.php logic
 * Système de rafraîchissement type "Global Chat"
 */

let lastPmHTML = "";
let initialPmLoad = true;

/**
 * Charge les messages d'une conversation spécifique
 */
function loadMessages() {
    const chatBox = document.getElementById('chat-box');
    const roomIdInput = document.getElementById('current-room-id');

    if (!chatBox || !roomIdInput) return;

    const roomId = roomIdInput.value;
    if (!roomId) return;

    fetch(`../app/ajax/pm_load.php?room_id=${roomId}`)
        .then(response => {
            if (!response.ok) throw new Error('Network response was not ok');
            return response.text();
        })
        .then(html => {
            // Si le contenu est identique au précédent, on ne fait rien
            if (html === lastPmHTML) return;

            // Détection : l'utilisateur est-il déjà en bas du chat ?
            // On laisse une marge de 100px pour la détection
            const isAtBottom = chatBox.scrollHeight - chatBox.scrollTop <= chatBox.clientHeight + 100;

            // Mise à jour du contenu
            chatBox.innerHTML = html;
            lastPmHTML = html;

            // Scroll automatique si c'est le premier chargement ou si on était déjà en bas
            if (isAtBottom || initialPmLoad) {
                chatBox.scrollTop = chatBox.scrollHeight;
                initialPmLoad = false;
            }
        })
        .catch(error => {
            console.error('Erreur lors du chargement des PM:', error);
        });
}

/**
 * Envoi d'un message via AJAX
 */
function sendMessage(event) {
    if (event) event.preventDefault();

    const form = document.getElementById('pm-form');
    const input = document.getElementById('pm-input');
    const roomId = document.getElementById('current-room-id').value;

    if (!input || !input.value.trim() || !roomId) return;

    const formData = new FormData();
    formData.append('room_id', roomId);
    formData.append('message', input.value.trim());

    // On vide l'input immédiatement pour un effet "instantané"
    const messageBackup = input.value;
    input.value = '';

    fetch('../app/ajax/pm_send.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            loadMessages(); // Rechargement immédiat après envoi
        } else {
            console.error('Erreur envoi:', data.message);
            input.value = messageBackup; // On restaure si échec
        }
    })
    .catch(error => {
        console.error('Erreur AJAX envoi:', error);
        input.value = messageBackup;
    });
}

// Initialisation au chargement de la page
document.addEventListener('DOMContentLoaded', () => {
    // Premier chargement
    loadMessages();

    // Rafraîchissement automatique toutes les 2 secondes
    setInterval(loadMessages, 2000);

    // Écouteur sur le formulaire d'envoi
    const pmForm = document.getElementById('pm-form');
    if (pmForm) {
        pmForm.addEventListener('submit', sendMessage);
    }
});