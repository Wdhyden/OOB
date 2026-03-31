function confirmSanction(form) {
    return confirm('Appliquer cette sanction ?\n\nCette action sera enregistrée.');
}

function confirmBan(form) {
    return confirm('BAN PERMANENT ?\n\nCette action est irréversible.');
}

// Recherche en temps réel (bonus)
const searchInput = document.querySelector('.search-form input');
searchInput.addEventListener('input', debounce(() => {
    const term = searchInput.value;
    if (term.length > 2) {
        window.location.href = `/admin/?search=${encodeURIComponent(term)}`;
    }
}, 300));

// Notifications Toast (bonus)
function showToast(message, type = 'success') {
    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    toast.textContent = message;
    document.body.appendChild(toast);
    
    setTimeout(() => {
        toast.style.opacity = '0';
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}

// Auto-refresh stats
setInterval(() => {
    if (document.visibilityState === 'visible') {
        // On pourrait ajouter AJAX pour refresh stats
    }
}, 30000);

function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}
