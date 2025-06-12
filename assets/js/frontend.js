/**
 * Krom Translation Frontend JS
 */

// Switch language function
function kromSwitchLanguage(lang) {
    // Store language in session/cookie
    if (typeof(Storage) !== "undefined") {
        sessionStorage.setItem("krom_language", lang);
    } else {
        // Fallback to cookie
        document.cookie = "krom_language=" + lang + ";path=/";
    }
    
    // Add language parameter to current URL
    let currentUrl = new URL(window.location.href);
    currentUrl.searchParams.set('lang', lang);
    
    // Reload the page with the new language
    window.location.href = currentUrl.toString();
}

// Initialize translation on document ready
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

    // You can add any initialization code here
});