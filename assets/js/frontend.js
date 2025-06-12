document.addEventListener('DOMContentLoaded', function() {
    const switcher = document.querySelector('.krom-language-switcher');
    if (!switcher) return;

    switcher.addEventListener('click', function(e) {
        const link = e.target.closest('a');
        if (!link) return;

        e.preventDefault();
        switcher.classList.add('switching');
        
        // Small delay to show the transition
        setTimeout(() => {
            window.location.href = link.href;
        }, 200);
    });
});