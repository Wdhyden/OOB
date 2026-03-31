// Navigation SPA-like (on pourra faire plus tard avec AJAX complet)
document.querySelectorAll('.nav-btn').forEach(btn => {
    btn.addEventListener('click', () => {
        const page = btn.dataset.page;
        
        // Enlever active
        document.querySelectorAll('.nav-btn').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        
        // Redirection
        if (page === 'chat') {
            window.location.href = '/chat.php';
        } else if (page === 'messages') {
            window.location.href = '/private_messages.php';
        } else if (page === 'drive') {
            window.location.href = '/drive.php';
        } else if (page === 'profile') {
            window.location.href = '/profile.php';
        } else if (page === 'admin') {
            window.location.href = '/admin/';
        }
    });
});

// Dropdown user menu
document.querySelector('.user-menu').addEventListener('mouseenter', () => {
    document.querySelector('.dropdown').style.display = 'block';
});
document.querySelector('.user-menu').addEventListener('mouseleave', () => {
    document.querySelector('.dropdown').style.display = 'none';
});

// Auto-refresh stats (optionnel)
setInterval(() => {
    // On pourra ajouter un AJAX pour refresh stats en temps réel
}, 30000);
