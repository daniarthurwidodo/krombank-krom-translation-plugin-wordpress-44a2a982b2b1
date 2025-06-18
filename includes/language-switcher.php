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
 * Add language rewrite rules
 */
function krom_add_language_rewrite_rules() {
    // Languages we support
    $languages = array('en', 'id');
    
    // Add rewrite rules for each language
    foreach ($languages as $lang) {
        // Homepage with language code
        add_rewrite_rule('^' . $lang . '/?$', 'index.php?lang=' . $lang, 'top');
        
        // Posts and pages with language code
        add_rewrite_rule('^' . $lang . '/(.?.+?)(?:/([0-9]+))?/?$', 'index.php?lang=' . $lang . '&name=$matches[1]&page=$matches[2]', 'top');
        
        // Custom post types with language code
        add_rewrite_rule('^' . $lang . '/([^/]+)/([^/]+)/?$', 'index.php?lang=' . $lang . '&post_type=$matches[1]&name=$matches[2]', 'top');
        
        // Categories with language code
        add_rewrite_rule('^' . $lang . '/category/(.+?)/?$', 'index.php?lang=' . $lang . '&category_name=$matches[1]', 'top');
        
        // Tags with language code
        add_rewrite_rule('^' . $lang . '/tag/(.+?)/?$', 'index.php?lang=' . $lang . '&tag=$matches[1]', 'top');
    }
    
    // Add query var for language
    add_rewrite_tag('%lang%', '([^/]+)');
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

/**
 * Filter post permalinks to include language
 */
function krom_filter_post_link($permalink, $post) {
    // Don't modify admin URLs
    if (is_admin() && !wp_doing_ajax()) {
        return $permalink;
    }
    
    // Get current language
    $current_lang = krom_get_current_language();
    
    // If permalink doesn't already contain language code
    if (!preg_match('~^' . home_url('/' . $current_lang . '/') . '~', $permalink)) {
        // Add language code to URL
        $permalink = str_replace(home_url('/'), home_url('/' . $current_lang . '/'), $permalink);
    }
    
    return $permalink;
}
add_filter('post_link', 'krom_filter_post_link', 10, 2);
add_filter('page_link', 'krom_filter_post_link', 10, 2);
add_filter('post_type_link', 'krom_filter_post_link', 10, 2);

/**
 * Add language switcher to single posts
 */
function krom_add_post_language_switcher($content) {
    // Only add to single posts
    if (!is_singular()) {
        return $content;
    }
    
    // Get current post ID
    $post_id = get_the_ID();
    if (!$post_id) {
        return $content;
    }
    
    // Get current language
    $current_lang = krom_get_current_language();
    
    // Build language switcher HTML
    $switcher = '<div class="krom-post-language-switcher" style="margin-bottom: 20px; text-align: right;">';
    $switcher .= '<strong>Language: </strong>';
    
    // Indonesian link
    $id_url = get_permalink($post_id);
    $id_url = str_replace(home_url('/' . $current_lang . '/'), home_url('/id/'), $id_url);
    $id_class = $current_lang === 'id' ? 'current' : '';
    $switcher .= '<a href="' . esc_url($id_url) . '" class="' . $id_class . '" style="margin-right: 10px; ' . ($current_lang === 'id' ? 'font-weight: bold;' : '') . '">Indonesia</a>';
    
    // English link
    $en_url = get_permalink($post_id);
    $en_url = str_replace(home_url('/' . $current_lang . '/'), home_url('/en/'), $en_url);
    $en_class = $current_lang === 'en' ? 'current' : '';
    $switcher .= '<a href="' . esc_url($en_url) . '" class="' . $en_class . '" style="' . ($current_lang === 'en' ? 'font-weight: bold;' : '') . '">English</a>';
    
    $switcher .= '</div>';
    
    // Append to content
    return $switcher . $content;
}
add_filter('the_content', 'krom_add_post_language_switcher', 5); // Lower priority to ensure it runs before content translation