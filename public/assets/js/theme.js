// assets/js/theme.js
(function() {
    const key = 'theme';
    const saved = localStorage.getItem(key);
    const body = document.body;
    if (saved === 'light') {
        body.classList.remove('theme-dark');
        body.classList.add('theme-light');
    } else {
        body.classList.add('theme-dark');
    }

    const btn = document.getElementById('theme-toggle');
    if (btn) {
        btn.addEventListener('click', () => {
            if (body.classList.contains('theme-dark')) {
                body.classList.remove('theme-dark');
                body.classList.add('theme-light');
                localStorage.setItem(key, 'light');
            } else {
                body.classList.remove('theme-light');
                body.classList.add('theme-dark');
                localStorage.setItem(key, 'dark');
            }
        });
    }
})();
