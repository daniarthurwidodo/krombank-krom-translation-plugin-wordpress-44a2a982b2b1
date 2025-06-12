<?php
/**
 * Plugin Name: Krom Manual Translation
 * Description: A simple plugin to manually translate text strings.
 * Version: 1.0
 * Author: Krom
 * Text Domain: krom-manual-translation
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('KROM_TRANS_PATH', plugin_dir_path(__FILE__));
define('KROM_TRANS_URL', plugin_dir_url(__FILE__));
define('KROM_TRANS_VERSION', '1.0');

/**
 * Initialize the plugin functionality
 */
function krom_manual_translation_init() {
    // Load text domain for internationalization
    load_plugin_textdomain('krom-manual-translation', false, dirname(plugin_basename(__FILE__)) . '/languages');
    
    // Include required files
    require_once KROM_TRANS_PATH . 'includes/functions.php';
    
    // Admin-only functionality
    if (is_admin()) {
        require_once KROM_TRANS_PATH . 'includes/admin.php';
    }
    
    // Frontend functionality
    require_once KROM_TRANS_PATH . 'includes/frontend.php';
    
    // Register shortcodes
    add_shortcode('krom_translate', 'krom_translation_shortcode');
}

add_action('plugins_loaded', 'krom_manual_translation_init');

/**
 * Shortcode function for translation
 * 
 * @param array $atts Shortcode attributes
 * @param string $content Content to translate
 * @return string Translated content
 */
function krom_translation_shortcode($atts, $content = null) {
    // Extract attributes
    $atts = shortcode_atts(
        array(
            'id' => '', // Optional ID for the text
            'lang' => '', // Target language (if not using current language)
        ),
        $atts,
        'krom_translate'
    );
    
    // If no content provided, return empty
    if ($content === null) {
        return '';
    }
    
    // Get current language or use specified language
    $lang = !empty($atts['lang']) ? $atts['lang'] : krom_get_current_language();
    
    // Get translated content
    $translated = krom_get_translation($content, $lang, $atts['id']);
    
    return $translated;
}

/**
 * Plugin activation hook
 */
function krom_translation_activate() {
    // Create necessary database tables if needed
    // Initialize default settings
    add_option('krom_translation_settings', array(
        'default_language' => 'en',
        'available_languages' => array('en'),
    ));
    
    // Create necessary directories
    $upload_dir = wp_upload_dir();
    $translation_dir = $upload_dir['basedir'] . '/krom-translations';
    
    if (!file_exists($translation_dir)) {
        wp_mkdir_p($translation_dir);
    }
}
register_activation_hook(__FILE__, 'krom_translation_activate');

/**
 * Plugin deactivation hook
 */
function krom_translation_deactivate() {
    // Cleanup if needed
}
register_deactivation_hook(__FILE__, 'krom_translation_deactivate');