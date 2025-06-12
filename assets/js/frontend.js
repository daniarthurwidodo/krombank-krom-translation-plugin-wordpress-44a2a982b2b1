/**
 * Frontend JavaScript for Krom Manual Translation plugin
 */

// Switch language function
function kromSwitchLanguage(lang) {
    // Set cookie with longer expiration and proper path
    var expires = new Date();
    expires.setTime(expires.getTime() + (30 * 24 * 60 * 60 * 1000)); // 30 days
    
    document.cookie = 'krom_language=' + lang + 
                     '; expires=' + expires.toUTCString() + 
                     '; path=' + kromTranslation.cookiePath + 
                     '; domain=' + kromTranslation.cookieDomain + 
                     '; SameSite=Lax';
    
    // Send AJAX request to get the correct URL and update server-side session
    jQuery.ajax({
        url: kromTranslation.ajaxUrl,
        type: 'POST',
        data: {
            action: 'krom_switch_language',
            language: lang,
            nonce: kromTranslation.nonce
        },
        success: function(response) {
            if (response.success && response.data.redirect_url) {
                // Redirect to the language-specific URL
                window.location.href = response.data.redirect_url;
            } else {
                // Fallback: reload current page
                window.location.reload();
            }
        },
        error: function() {
            // Fallback: construct URL manually and redirect
            var currentUrl = window.location.pathname;
            var newUrl = constructLanguageUrl(lang, currentUrl);
            window.location.href = newUrl;
        }
    });
}

// Helper function to construct language URLs
function constructLanguageUrl(lang, currentPath) {
    var baseUrl = kromTranslation.homeUrl;
    var path = currentPath.replace(/^\/+/, ''); // Remove leading slashes
    
    // Remove existing language prefix
    var pathParts = path.split('/');
    var languages = ['en', 'id']; // You might want to make this dynamic
    
    if (pathParts.length > 0 && languages.indexOf(pathParts[0]) !== -1) {
        pathParts.shift(); // Remove language prefix
        path = pathParts.join('/');
    }
    
    // Add new language prefix (except for default language 'id')
    if (lang !== 'id') {
        path = lang + '/' + path;
    }
    
    return baseUrl + path;
}

// Initialize when document is ready
jQuery(document).ready(function($) {
    // Add smooth transitions to language switcher
    $('.krom-language-switcher a').on('click', function(e) {
        e.preventDefault();
        
        // Add loading state
        $(this).addClass('krom-switching');
        
        // Get language from onclick attribute or data attribute
        var onclick = $(this).attr('onclick');
        if (onclick) {
            var match = onclick.match(/kromSwitchLanguage\(['"]([^'"]+)['"]\)/);
            if (match) {
                kromSwitchLanguage(match[1]);
            }
        }
    });
    
    // Handle dropdown changes
    $('.krom-language-select').on('change', function() {
        var selectedLang = $(this).val();
        if (selectedLang) {
            kromSwitchLanguage(selectedLang);
        }
    });
    
    // Add visual feedback for current language
    $('.krom-language-switcher').addClass('krom-initialized');
    
    // Update active language indicator based on current URL
    updateActiveLanguage();
});

// Function to update active language indicator
function updateActiveLanguage() {
    var currentLang = kromTranslation.currentLang;
    var $ = jQuery;
    
    // Update list style
    $('.krom-language-list li').removeClass('krom-lang-active');
    $('.krom-language-list li').each(function() {
        var link = $(this).find('a');
        var onclick = link.attr('onclick');
        if (onclick) {
            var match = onclick.match(/kromSwitchLanguage\(['"]([^'"]+)['"]\)/);
            if (match && match[1] === currentLang) {
                $(this).addClass('krom-lang-active');
            }
        }
    });
    
    // Update flags style
    $('.krom-language-flags li').removeClass('krom-lang-active');
    $('.krom-language-flags li').each(function() {
        var link = $(this).find('a');
        var onclick = link.attr('onclick');
        if (onclick) {
            var match = onclick.match(/kromSwitchLanguage\(['"]([^'"]+)['"]\)/);
            if (match && match[1] === currentLang) {
                $(this).addClass('krom-lang-active');
            }
        }
    });
    
    // Update dropdown
    $('.krom-language-select').val(currentLang);
}