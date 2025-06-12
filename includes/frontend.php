<?php
/**
 * Frontend functionality for Krom Manual Translation plugin
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Very early language detection - before anything else
 */
function krom_very_early_language_detection() {
    // Force language detection immediately
    $current_lang = krom_get_current_language();
    
    // Store in global for immediate access
    $GLOBALS['krom_current_language'] = $current_lang;
    
    // Debug logging
    error_log('Krom Translation: Very early detection - Language is: ' . $current_lang);
}
// Hook this to the earliest possible action
add_action('muplugins_loaded', 'krom_very_early_language_detection', 1);
add_action('plugins_loaded', 'krom_very_early_language_detection', 1);

/**
 * Initialize language early
 */
function krom_init_language() {
    // Start session if needed
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // Ensure language is detected
    $current_lang = krom_get_current_language();
    
    // Set current language in a global for easy access
    $GLOBALS['krom_current_language'] = $current_lang;
    
    // Debug logging
    error_log('Krom Translation: Init language - Current language: ' . $current_lang);
}
add_action('init', 'krom_init_language', 1);

/**
 * Filter menu items to show translated versions
 */
function krom_filter_nav_menu_objects($items, $args) {
    // Debug: Add error logging
    error_log('Krom Translation: Filter called with ' . count($items) . ' items');
    
    $current_lang = krom_get_current_language();
    error_log('Krom Translation: Current language is ' . $current_lang);
    
    $settings = get_option('krom_translation_settings', array(
        'default_language' => 'id',
    ));
    $default_lang = $settings['default_language'];
    
    // Debug log the default language
    error_log('Krom Translation: Default language is ' . $default_lang);
    
    // Only filter if not default language
    if ($current_lang === $default_lang) {
        error_log('Krom Translation: Current language is default, no translation needed');
        return $items;
    }
    
    $menu_translations = get_option('krom_menu_translations', array());
    error_log('Krom Translation: Found ' . count($menu_translations) . ' menu translations');
    
    foreach ($items as $item) {
        if (isset($menu_translations[$item->ID][$current_lang])) {
            $translation = $menu_translations[$item->ID][$current_lang];
            
            error_log('Krom Translation: Found translation for item ' . $item->ID . ': ' . print_r($translation, true));
            
            // Replace title if translation exists
            if (!empty($translation['title'])) {
                $original_title = $item->title;
                $item->title = $translation['title'];
                error_log('Krom Translation: Changed title from "' . $original_title . '" to "' . $item->title . '"');
            }
            
            // Replace attr_title if translation exists
            if (!empty($translation['attr_title'])) {
                $item->attr_title = $translation['attr_title'];
            }
        } else {
            error_log('Krom Translation: No translation found for item ' . $item->ID . ' (' . $item->title . ')');
        }
    }
    
    return $items;
}

/**
 * Force menu translation on wp_loaded
 */
function krom_force_menu_translation() {
    // Ensure our menu filter is properly registered
    remove_filter('wp_nav_menu_objects', 'krom_filter_nav_menu_objects');
    add_filter('wp_nav_menu_objects', 'krom_filter_nav_menu_objects', 5, 2);
    
    error_log('Krom Translation: Forced menu translation filter registration');
}
add_action('wp_loaded', 'krom_force_menu_translation');

// Also register the filter directly
add_filter('wp_nav_menu_objects', 'krom_filter_nav_menu_objects', 5, 2);

/**
 * Also add it to walker_nav_menu_start_el for additional coverage
 */
function krom_filter_nav_menu_start_el($item_output, $item, $depth, $args) {
    $current_lang = krom_get_current_language();
    $settings = get_option('krom_translation_settings', array(
        'default_language' => 'id',
    ));
    $default_lang = $settings['default_language'];
    
    // Only filter if not default language
    if ($current_lang !== $default_lang) {
        $menu_translations = get_option('krom_menu_translations', array());
        
        if (isset($menu_translations[$item->ID][$current_lang])) {
            $translation = $menu_translations[$item->ID][$current_lang];
            
            if (!empty($translation['title'])) {
                // Replace the title in the output
                $item_output = str_replace('>' . $item->title . '<', '>' . $translation['title'] . '<', $item_output);
            }
        }
    }
    
    return $item_output;
}
add_filter('walker_nav_menu_start_el', 'krom_filter_nav_menu_start_el', 10, 4);

/**
 * Register frontend scripts and styles
 */
function krom_register_frontend_assets() {
    wp_register_script(
        'krom-translation-frontend',
        KROM_TRANS_URL . 'assets/js/frontend.js',
        array('jquery'),
        KROM_TRANS_VERSION,
        true
    );
    
    wp_register_style(
        'krom-translation-frontend',
        KROM_TRANS_URL . 'assets/css/frontend.css',
        array(),
        KROM_TRANS_VERSION
    );
    
    // Localize script with plugin data
    wp_localize_script(
        'krom-translation-frontend',
        'kromTranslation',
        array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'cookiePath' => COOKIEPATH,
            'cookieDomain' => COOKIE_DOMAIN,
            'nonce' => wp_create_nonce('krom_translation_nonce'),
            'currentLang' => krom_get_current_language(),
            'homeUrl' => home_url('/'),
        )
    );
}
add_action('wp_enqueue_scripts', 'krom_register_frontend_assets');

/**
 * Ajax handler for switching language
 */
function krom_ajax_switch_language() {
    // Verify nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'krom_translation_nonce')) {
        wp_send_json_error(array('message' => 'Invalid nonce'));
    }
    
    if (isset($_POST['language'])) {
        $language = sanitize_text_field($_POST['language']);
        
        // Set the language using our function
        if (krom_set_current_language($language)) {
            // Get the redirect URL
            $redirect_url = krom_get_language_url($language);
            
            wp_send_json_success(array(
                'language' => $language,
                'message' => 'Language switched successfully',
                'redirect_url' => $redirect_url
            ));
        } else {
            wp_send_json_error(array('message' => 'Invalid language'));
        }
    }
    
    wp_send_json_error(array('message' => 'No language specified'));
}
add_action('wp_ajax_krom_switch_language', 'krom_ajax_switch_language');
add_action('wp_ajax_nopriv_krom_switch_language', 'krom_ajax_switch_language');

/**
 * Add rewrite rules for language URLs
 */
function krom_add_rewrite_rules() {
    $settings = get_option('krom_translation_settings');
    $available_languages = isset($settings['available_languages']) ? $settings['available_languages'] : array('id', 'en');
    $default_lang = isset($settings['default_language']) ? $settings['default_language'] : 'id';
    
    foreach ($available_languages as $lang) {
        if ($lang !== $default_lang) {
            add_rewrite_rule('^' . $lang . '/?$', 'index.php?krom_lang=' . $lang, 'top');
            add_rewrite_rule('^' . $lang . '/(.*)$', 'index.php?krom_lang=' . $lang . '&krom_path=$matches[1]', 'top');
        }
    }
}
add_action('init', 'krom_add_rewrite_rules');

/**
 * Add query vars for language detection
 */
function krom_query_vars($vars) {
    $vars[] = 'krom_lang';
    $vars[] = 'krom_path';
    return $vars;
}
add_filter('query_vars', 'krom_query_vars');

/**
 * Handle language detection from query vars
 */
function krom_parse_request($wp) {
    if (isset($wp->query_vars['krom_lang'])) {
        $language = sanitize_text_field($wp->query_vars['krom_lang']);
        krom_set_current_language($language);
        
        error_log('Krom Translation: Parse request - Set language to: ' . $language);
        
        // Handle the path redirection if needed
        if (isset($wp->query_vars['krom_path'])) {
            $path = sanitize_text_field($wp->query_vars['krom_path']);
            // You might want to handle this differently based on your needs
        }
    }
}
add_action('parse_request', 'krom_parse_request');