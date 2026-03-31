let currentConvId = null;
let lastMessageId = 0;
let refreshInterval;

const convListEl = document.getElementById('conversations-list');
const messagesEl = document.getElementById('pm-messages');
const inputEl = document.getElementById('pm-message-input');
const btnSend = document.getElementById('pm-btn-send');
const searchInput = document.getElementById('user-search');
const searchResults = document.getElementById('search-results');

function renderMessage(msg) {
    const row = document.createElement('div');
    row.className = `pm-message ${msg.direction}`;
    row.dataset.id = msg.id;
    
    const bubble = document.createElement('div');
    bubble.className = 'pm-message-bubble';
    
    const avatar = document.createElement('div');
    avatar.className = 'pm-message-avatar';
    if (msg.avatar_path) {
        avatar.style.backgroundImage = `url('/${msg.avatar_path}')`;
    }
    
    const content = document.createElement('div');
    content.className = 'pm-message-content';
    
    if (msg.message_type === 'youtube' && msg.youtube_url) {
        const iframe = document.createElement('iframe');
        iframe.src = msg.youtube_url;
        iframe.width = '320';
        iframe.height = '180';
        iframe.allowFullscreen = true;
        content.appendChild(iframe);
    } else {
        content.textContent = msg.content;
    }
    
    const meta = document.createElement('div');
    meta.className = 'pm-message-meta';
    meta.innerHTML = `
        <span style="color: ${msg.nickname_color || '#ff6fd8'}">${msg.username}</span>
        <span>${new Date(msg.created_at).toLocaleTimeString('fr-FR', {hour: '2-digit', minute: '2-digit'})}</span>
    `;
    
    bubble.appendChild(avatar);
    bubble.appendChild(content);
    bubble.appendChild(meta);
    row.appendChild(bubble);
    
    return row;
}

function loadMessages() {
    if (!currentConvId) return;
    
    const isAtBottom = messagesEl.scrollHeight - messagesEl.scrollTop - messagesEl.clientHeight < 50;
    
    fetch(`/app/ajax/pm_load.php?conv_id=${currentConvId}&after_id=${lastMessageId}`)
        .then(r => r.json())
        .then(data => {
            if (!data.ok) return console.error(data.error);
            
            data.messages.forEach(msg => {
                const el = renderMessage(msg);
                messagesEl.appendChild(el);
                lastMessageId = msg.id;
            });
            
            if (isAtBottom) {
                messagesEl.scrollTop = messagesEl.scrollHeight;
            }
            
            // Update participant info
            updateParticipantInfo(data.other_user_id);
        });
}

function sendMessage() {
    if (!currentConvId) return;
    
    const text = inputEl.value.trim();
    if (!text) return;
    
    fetch('/app/ajax/pm_send.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: new URLSearchParams({
            conv_id: currentConvId,
            content: text,
            type: 'text'
        })
    })
    .then(r => r.json())
    .then(data => {
        if (data.ok) {
            inputEl.value = '';
            inputEl.style.height = 'auto';
            loadMessages();
        }
    });
}

function openConversation(convId) {
    currentConvId = convId;
    lastMessageId = 0;
    
    // Update UI
    document.querySelectorAll('.conversation-item').forEach(item => {
        item.classList.remove('active');
    });
    document.querySelector(`[data-conv-id="${convId}"]`).classList.add('active');
    
    document.getElementById('pm-chat-header').style.display = 'flex';
    messagesEl.innerHTML = '';
    
    loadMessages();
}

function startNewConversation(targetUserId) {
    fetch('/app/ajax/pm_start.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: new URLSearchParams({target_user_id: targetUserId})
    })
    .then(r => r.json())
    .then(data => {
        if (data.ok) {
            openConversation(data.conv_id);
        }
    });
}

function updateParticipantInfo(otherUserId) {
    // On pourra fetcher le statut en ligne via AJAX séparé
    fetch(`/app/ajax/user_status.php?user_id=${otherUserId}`)
        .then(r => r.json())
        .then(data => {
            const header = document.getElementById('pm-chat-header');
            const nameEl = header.querySelector('.participant-name');
            const statusEl = header.querySelector('.participant-status');
            nameEl.textContent = data.username || 'Utilisateur';
            statusEl.textContent = data.online_status === 'online' ? 'En ligne' : 'Hors ligne';
            statusEl.style.color = data.online_status === 'online' ? '#00ff88' : '#666';
        });
}

// Event listeners
document.querySelectorAll('.conversation-item').forEach(item => {
    item.addEventListener('click', () => {
        openConversation(parseInt(item.dataset.convId));
    });
});

searchInput.addEventListener('input', () => {
    const term = searchInput.value.trim();
    if (term.length > 1) {
        window.location.href = `/private_messages.php?search=${encodeURIComponent(term)}`;
    }
});

searchResults.addEventListener('click', (e) => {
    const userEl = e.target.closest('.search-user');
    if (userEl) {
        const userId = parseInt(userEl.dataset.userId);
        startNewConversation(userId);
        searchResults.style.display = 'none';
        searchInput.value = '';
    }
});

btnSend.addEventListener('click', sendMessage);
inputEl.addEventListener('keypress', (e) => {
    if (e.key === 'Enter' && !e.shiftKey) {
        e.preventDefault();
        sendMessage();
    }
});

// Auto-resize textarea
inputEl.addEventListener('input', () => {
    inputEl.style.height = 'auto';
    inputEl.style.height = Math.min(inputEl.scrollHeight, 120) + 'px';
});

// Polling
function startPolling() {
    refreshInterval = setInterval(loadMessages, 2000);
}
startPolling();
