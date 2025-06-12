<?php
/**
 * Frontend functionality for Krom Manual Translation
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Register frontend scripts and styles
 */
function krom_register_frontend_assets() {
    // Register styles
    wp_register_style(
        'krom-translation-frontend',
        KROM_TRANS_URL . 'assets/css/frontend.css',
        array(),
        KROM_TRANS_VERSION
    );
    
    // Register scripts
    wp_register_script(
        'krom-translation-frontend',
        KROM_TRANS_URL . 'assets/js/frontend.js',
        array(),
        KROM_TRANS_VERSION,
        true
    );
}
add_action('wp_enqueue_scripts', 'krom_register_frontend_assets');

/**
 * Initialize session for language storage
 */
function krom_init_session() {
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    
    // Check for language cookie and set session
    if (isset($_COOKIE['krom_language']) && !isset($_SESSION['krom_language'])) {
        $_SESSION['krom_language'] = $_COOKIE['krom_language'];
    }
}
add_action('init', 'krom_init_session');

/**
 * Add rewrite rules for language paths
 */
function krom_add_language_rewrite_rules() {
    $settings = get_option('krom_translation_settings');
    $languages = isset($settings['available_languages']) ? $settings['available_languages'] : array('en');
    
    // Add rules for each language
    foreach ($languages as $lang) {
        // Rule for homepage - modified to work properly
        add_rewrite_rule(
            '^' . $lang . '/?$',
            'index.php?lang=' . $lang,
            'top'
        );
        
        // Rule for other pages
        add_rewrite_rule(
            '^' . $lang . '/(.+)/?$',
            'index.php?lang=' . $lang . '&pagename=$matches[1]',
            'top'
        );
        
        // Rules for post type archives and taxonomies
        add_rewrite_rule(
            '^' . $lang . '/category/(.+)/?$',
            'index.php?lang=' . $lang . '&category_name=$matches[1]',
            'top'
        );
        
        add_rewrite_rule(
            '^' . $lang . '/tag/(.+)/?$',
            'index.php?lang=' . $lang . '&tag=$matches[1]',
            'top'
        );
        
        // Rule for single posts with numeric IDs
        add_rewrite_rule(
            '^' . $lang . '/([0-9]+)/?$',
            'index.php?lang=' . $lang . '&p=$matches[1]',
            'top'
        );
    }
}
add_action('init', 'krom_add_language_rewrite_rules');

/**
 * Register the language query var
 */
function krom_register_query_vars($vars) {
    $vars[] = 'lang';
    return $vars;
}
add_filter('query_vars', 'krom_register_query_vars');

/**
 * Flush rewrite rules on plugin activation
 */
function krom_flush_rewrite_rules() {
    krom_add_language_rewrite_rules();
    flush_rewrite_rules();
}

/**
 * Add language prefix to permalinks
 */
function krom_add_language_to_permalink($permalink, $post, $leavename) {
    $lang = krom_get_current_language();
    
    // Don't modify admin URLs
    if (is_admin()) {
        return $permalink;
    }
    
    // Don't add language code if it's already there
    if (preg_match('/^\/(' . implode('|', krom_get_available_languages()) . ')\//', $permalink)) {
        return $permalink;
    }
    
    // Add language code to the permalink
    $permalink = preg_replace('/^(https?:\/\/[^\/]*)(\/.*)$/', '$1/' . $lang . '$2', $permalink);
    
    return $permalink;
}
add_filter('post_link', 'krom_add_language_to_permalink', 10, 3);
add_filter('page_link', 'krom_add_language_to_permalink', 10, 3);

/**
 * Modify the home URL to include the language
 */
function krom_add_language_to_home_url($url) {
    if (is_admin()) {
        return $url;
    }
    
    $lang = krom_get_current_language();
    
    // Don't modify if already has language code
    if (preg_match('/\/' . implode('\/', krom_get_available_languages()) . '\/?$/', $url)) {
        return $url;
    }
    
    // Add trailing slash if needed
    if (substr($url, -1) !== '/') {
        $url .= '/';
    }
    
    // Add language code
    $url .= $lang . '/';
    
    return $url;
}
add_filter('home_url', 'krom_add_language_to_home_url');

/**
 * Helper function to get available languages
 */
function krom_get_available_languages() {
    $settings = get_option('krom_translation_settings');
    return isset($settings['available_languages']) ? $settings['available_languages'] : array('en');
}

/**
 * Set the current language from URL at pre_get_posts stage
 */
function krom_set_language_from_request($query) {
    // Only modify main query
    if (!$query->is_main_query()) {
        return;
    }
    
    // Get language from URL
    $lang = get_query_var('lang');
    
    if (!empty($lang)) {
        // Store in session
        if (session_status() == PHP_SESSION_ACTIVE) {
            $_SESSION['krom_language'] = $lang;
        }
        
        // Set cookie for longer persistence
        setcookie('krom_language', $lang, time() + (86400 * 30), '/');
    }
}
add_action('pre_get_posts', 'krom_set_language_from_request');