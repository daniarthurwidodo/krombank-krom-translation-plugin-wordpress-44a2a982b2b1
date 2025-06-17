<?php
/**
 * Language Switcher functionality
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Generate HTML for language switcher
 */
function krom_language_switcher_html() {
    $current_lang = krom_get_current_language();
    
    // Create URLs for each language
    $languages = array(
        'en' => array(
            'name' => 'English',
            'url' => krom_get_language_url('en'),
        ),
        'id' => array(
            'name' => 'Indonesia',
            'url' => krom_get_language_url('id'),
        ),
    );
    
    $html = '<div class="krom-language-switcher">';
    
    foreach ($languages as $lang_code => $lang_data) {
        $active_class = ($current_lang === $lang_code) ? 'active' : '';
        $html .= sprintf(
            '<a href="%s" class="krom-lang-button %s" data-lang="%s">%s</a>',
            esc_url($lang_data['url']),
            esc_attr($active_class),
            esc_attr($lang_code),
            esc_html($lang_data['name'])
        );
    }
    
    $html .= '</div>';
    
    return $html;
}

/**
 * Get current language from URL
 */
function krom_get_current_language() {
    $url_path = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');
    $path_parts = explode('/', $url_path);
    
    // Check if first segment is a language code
    if (!empty($path_parts[0]) && in_array($path_parts[0], array('en', 'id'))) {
        return $path_parts[0];
    }
    
    return KROM_TRANSLATION_DEFAULT_LANG;
}

/**
 * Get language URL for current page
 */
function krom_get_language_url($lang_code) {
    global $wp;
    
    // Get current relative URL
    $current_url = add_query_arg(array(), $wp->request);
    
    // Check if we're on the homepage
    if (empty($current_url) || is_front_page() || is_home()) {
        // Always include language code in URL for consistency
        return home_url('/' . $lang_code . '/');
    }
    
    // For other pages, handle the language prefix
    $current_lang = krom_get_current_language();
    $current_path = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');
    $path_parts = explode('/', $current_path);
    
    // Remove current language prefix if it exists
    if (in_array($path_parts[0], array('en', 'id'))) {
        array_shift($path_parts);
    }
    
    // Rebuild path
    $clean_path = implode('/', $path_parts);
    
    // Always add language prefix for consistency
    return home_url($lang_code . '/' . $clean_path);
}

/**
 * Language switcher shortcode
 */
function krom_language_switcher_shortcode($atts) {
    return krom_language_switcher_html();
}

/**
 * URL rewrite rules for language prefixes
 */
function krom_add_language_rewrite_rules() {
    // Homepage rules for both languages (including default)
    add_rewrite_rule('^en/?$', 'index.php?lang=en', 'top');
    add_rewrite_rule('^id/?$', 'index.php?lang=id', 'top');
    
    // Post types and other pages rules
    add_rewrite_rule('^en/(.+?)/?$', 'index.php?pagename=$matches[1]&lang=en', 'top');
    add_rewrite_rule('^id/(.+?)/?$', 'index.php?pagename=$matches[1]&lang=id', 'top');
    
    // Category and taxonomy rules
    add_rewrite_rule('^en/category/(.+?)/?$', 'index.php?category_name=$matches[1]&lang=en', 'top');
    add_rewrite_rule('^id/category/(.+?)/?$', 'index.php?category_name=$matches[1]&lang=id', 'top');
}
add_action('init', 'krom_add_language_rewrite_rules');

/**
 * Add language query var
 */
function krom_add_query_vars($query_vars) {
    $query_vars[] = 'lang';
    return $query_vars;
}
add_filter('query_vars', 'krom_add_query_vars');

/**
 * Set language cookie based on URL
 */
function krom_set_language_cookie() {
    $current_lang = krom_get_current_language();
    
    if (!isset($_COOKIE['krom_language']) || $_COOKIE['krom_language'] !== $current_lang) {
        setcookie('krom_language', $current_lang, time() + (86400 * 30), '/'); // 30 days
    }
}
add_action('template_redirect', 'krom_set_language_cookie');

/**
 * Filter home URL to include language
 */
function krom_home_url($url, $path, $orig_scheme, $blog_id) {
    // Only modify if not in admin
    if (is_admin()) {
        return $url;
    }
    
    $current_lang = krom_get_current_language();
    
    // Add language prefix for all languages
    if ($path === '' || $path === '/') {
        return home_url('/' . $current_lang . '/');
    }
    
    return $url;
}
add_filter('home_url', 'krom_home_url', 10, 4);

/**
 * Handle template redirect to ensure proper homepage for language
 */
function krom_template_redirect() {
    global $wp_query;
    
    // If a language is specified
    if (get_query_var('lang')) {
        $lang = get_query_var('lang');
        
        // If we're on the homepage with a language parameter
        if (empty($wp_query->query) || count($wp_query->query) === 1 && isset($wp_query->query['lang'])) {
            // This is the homepage with language parameter
            // Make sure WordPress treats this as home
            $wp_query->is_home = true;
        }
    }
}
add_action('template_redirect', 'krom_template_redirect', 1);

/**
 * Redirect root URL to language-specific URL
 */
function krom_redirect_root_to_language() {
    // Only on frontend
    if (is_admin()) {
        return;
    }
    
    // Get the current URL path
    $current_path = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');
    
    // If we're at the root URL with no language code
    if (empty($current_path) || $current_path == 'index.php') {
        // Get preferred language from cookie or use default
        $lang = isset($_COOKIE['krom_language']) ? $_COOKIE['krom_language'] : KROM_TRANSLATION_DEFAULT_LANG;
        
        // Redirect to the language-specific URL
        wp_redirect(home_url('/' . $lang . '/'), 302);
        exit;
    }
}
add_action('template_redirect', 'krom_redirect_root_to_language', 1);

/**
 * Get current language from URL - For debugging only
 */
function krom_debug_get_current_language() {
    $current_lang = krom_get_current_language();
    
    echo '<div style="position: fixed; bottom: 10px; right: 10px; background: #fff; border: 1px solid #ccc; padding: 10px; z-index: 9999;">';
    echo 'Current Language: ' . $current_lang;
    echo '</div>';
}
add_action('wp_footer', 'krom_debug_get_current_language');