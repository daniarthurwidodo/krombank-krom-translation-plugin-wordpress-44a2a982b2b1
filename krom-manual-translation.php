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

/**
 * Plugin deactivation hook
 */
function krom_translation_deactivate() {
    // Cleanup if needed
}
register_deactivation_hook(__FILE__, 'krom_translation_deactivate');

/**
 * Plugin activation hook
 */
function krom_rewrite_flush() {
    krom_add_language_rewrite_rules();
    flush_rewrite_rules();
}
register_activation_hook(__FILE__, 'krom_rewrite_flush');

// Flush rewrite rules when plugin settings change
function krom_settings_changed() {
    flush_rewrite_rules();
}
add_action('update_option_krom_translation_settings', 'krom_settings_changed');

// Define plugin constants
define('KROM_TRANSLATION_PATH', plugin_dir_path(__FILE__));
define('KROM_TRANSLATION_URL', plugin_dir_url(__FILE__));
define('KROM_TRANSLATION_DEFAULT_LANG', 'id');

// Include language switcher functionality
require_once KROM_TRANSLATION_PATH . 'includes/language-switcher.php';

/**
 * Initialize the plugin
 */
function krom_translation_init() {
    // Register and enqueue styles
    add_action('wp_enqueue_scripts', 'krom_translation_enqueue_styles');
    
    // Add language switcher shortcode
    add_shortcode('krom_language_switcher', 'krom_language_switcher_shortcode');
    
    // Language switcher in header has been removed
    // The language switcher can still be added via shortcode [krom_language_switcher]
}
add_action('plugins_loaded', 'krom_translation_init');

/**
 * Register and enqueue styles
 */
function krom_translation_enqueue_styles() {
    wp_register_style(
        'krom-language-switcher', 
        KROM_TRANSLATION_URL . 'assets/css/language-switcher.css',
        array(),
        '1.0.0'
    );
    wp_enqueue_style('krom-language-switcher');
}