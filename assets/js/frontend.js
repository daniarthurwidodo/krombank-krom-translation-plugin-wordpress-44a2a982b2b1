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
    // You can add any initialization code here
});