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
    
    // Get the current pathname
    let path = window.location.pathname;
    
    // Remove any existing language prefix if present
    path = path.replace(/^\/(?:en|id|es|fr|de|zh)\//, '/');
    
    // Add the new language prefix
    let newPath = '/' + lang + path;
    
    // If we're at the site root with just a trailing slash
    if (path === '/') {
        newPath = '/' + lang + '/';
    }
    
    // Navigate to the new URL
    window.location.href = newPath;
}

// Initialize translation on document ready
document.addEventListener('DOMContentLoaded', function() {
    // You can add any initialization code here
});