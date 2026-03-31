document.getElementById('file-input').addEventListener('change', (e) => {
    const fileName = e.target.files[0]?.name || '';
    const fileNameEl = document.getElementById('file-name');
    
    if (fileName) {
        fileNameEl.textContent = fileName;
        fileNameEl.parentElement.parentElement.classList.add('file-selected');
    } else {
        fileNameEl.textContent = '';
        fileNameEl.parentElement.parentElement.classList.remove('file-selected');
    }
});

document.querySelectorAll('.btn-delete').forEach(btn => {
    btn.addEventListener('click', async () => {
        if (!confirm('Supprimer définitivement ce fichier ?')) return;
        
        const fileCard = btn.closest('.file-card');
        const fileId = fileCard.dataset.fileId;
        
        try {
            const response = await fetch('/app/ajax/drive_delete.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: new URLSearchParams({file_id: fileId})
            });
            const data = await response.json();
            
            if (data.ok) {
                fileCard.style.opacity = '0';
                fileCard.style.transform = 'translateX(-20px)';
                setTimeout(() => fileCard.remove(), 300);
            } else {
                alert('Erreur: ' + data.error);
            }
        } catch (err) {
            alert('Erreur réseau');
        }
    });
});

// Drag & drop upload (bonus)
const uploadZone = document.querySelector('.upload-zone');
uploadZone.addEventListener('dragover', (e) => {
    e.preventDefault();
    uploadZone.style.borderColor = '#ff6fd8';
});
uploadZone.addEventListener('dragleave', () => {
    uploadZone.style.borderColor = '#333';
});
uploadZone.addEventListener('drop', (e) => {
    e.preventDefault();
    uploadZone.style.borderColor = '#333';
    
    const files = e.dataTransfer.files;
    if (files.length > 0) {
        document.getElementById('file-input').files = files;
        document.getElementById('file-input').dispatchEvent(new Event('change'));
    }
});

// Auto-refresh liste (optionnel)
setInterval(() => {
    if (document.visibilityState === 'visible') {
        window.location.reload();
    }
}, 30000);
