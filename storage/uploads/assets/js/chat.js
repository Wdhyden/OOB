let currentRoomId = null;
let lastMessageId = 0;
const messagesEl = document.getElementById('messages');
const inputEl = document.getElementById('message-input');
const btnSend = document.getElementById('btn-send');

function renderMessage(m) {
    const row = document.createElement('div');
    row.className = 'message-row';
    const avatar = document.createElement('div');
    avatar.className = 'message-avatar';
    if (m.avatar_path) {
        avatar.style.backgroundImage = `url('/${m.avatar_path}')`;
    }
    const bubble = document.createElement('div');
    bubble.className = 'message-bubble';

    const meta = document.createElement('div');
    meta.className = 'message-meta';
    const name = document.createElement('span');
    name.className = 'message-username';
    name.style.color = m.nickname_color || '#ff6fd8';
    name.textContent = m.username;
    const time = document.createElement('span');
    time.className = 'message-time';
    time.textContent = m.created_at;
    meta.appendChild(name);
    meta.appendChild(time);

    const content = document.createElement('div');
    content.className = 'message-content';
    if (m.message_type === 'youtube' && m.youtube_url) {
        const iframe = document.createElement('iframe');
        iframe.src = m.youtube_url;
        iframe.width = '420';
        iframe.height = '236';
        iframe.allow = 'accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture';
        iframe.allowFullscreen = true;
        content.appendChild(iframe);
    } else {
        content.textContent = m.content;
    }

    const actions = document.createElement('div');
    actions.className = 'message-actions';
    actions.innerHTML = `
        <span class="message-action" data-action="reply" data-id="${m.id}">Répondre</span>
        <span class="message-action" data-action="like" data-id="${m.id}">Like</span>
        <span class="message-action" data-action="dislike" data-id="${m.id}">Dislike</span>
    `;

    bubble.appendChild(meta);
    bubble.appendChild(content);
    bubble.appendChild(actions);
    row.appendChild(avatar);
    row.appendChild(bubble);
    row.dataset.id = m.id;
    return row;
}

function loadMessages() {
    if (!currentRoomId) return;
    const isAtBottom = messagesEl.scrollHeight - messagesEl.scrollTop - messagesEl.clientHeight < 50;

    fetch(`/app/ajax/chat_load.php?room_id=${currentRoomId}&after_id=${lastMessageId}`)
        .then(r => r.json())
        .then(data => {
            if (!data.ok) return;
            data.messages.forEach(m => {
                const el = renderMessage(m);
                messagesEl.appendChild(el);
                lastMessageId = m.id;
            });
            if (isAtBottom) {
                messagesEl.scrollTop = messagesEl.scrollHeight;
            }
        });
}

function sendMessage() {
    if (!currentRoomId) return;
    const text = inputEl.value.trim();
    if (!text) return;
    fetch('/app/ajax/chat_send.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: new URLSearchParams({
            room_id: currentRoomId,
            content: text,
            type: 'text'
        })
    }).then(r => r.json())
      .then(data => {
        if (data.ok) {
            inputEl.value = '';
            loadMessages();
        }
      });
}

document.querySelectorAll('.room-item').forEach(item => {
    item.addEventListener('click', () => {
        document.querySelectorAll('.room-item').forEach(i => i.classList.remove('active'));
        item.classList.add('active');
        currentRoomId = item.dataset.roomId;
        lastMessageId = 0;
        messagesEl.innerHTML = '';
        document.getElementById('current-room-name').textContent = item.textContent.trim();
        loadMessages();
    });
});

btnSend.addEventListener('click', sendMessage);
setInterval(loadMessages, 2500);
// Dans chat.js - ajouter après le textarea
const fileInput = document.getElementById('file-input');
const btnAttach = document.getElementById('btn-attach');

btnAttach.addEventListener('click', () => fileInput.click());

fileInput.addEventListener('change', async () => {
    if (!currentRoomId || !fileInput.files[0]) return;
    
    const formData = new FormData();
    formData.append('room_id', currentRoomId);
    formData.append('file', fileInput.files[0]);
    
    try {
        const response = await fetch('/app/ajax/chat_send.php', {
            method: 'POST',
            body: formData
        });
        const data = await response.json();
        
        if (data.ok) {
            fileInput.value = '';
            loadMessages();
        }
    } catch (err) {
        console.error('Upload failed');
    }
});
