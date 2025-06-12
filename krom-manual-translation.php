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
    add_shortcode('krom_language_switcher', 'krom_language_switcher_shortcode');
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
 * Shortcode function for language switcher
 * 
 * @param array $atts Shortcode attributes
 * @return string Language switcher HTML
 */
function krom_language_switcher_shortcode($atts) {
    // Extract attributes
    $atts = shortcode_atts(
        array(
            'style' => 'dropdown', // dropdown, list, flags
            'show_names' => 'true', // show language names
        ),
        $atts,
        'krom_language_switcher'
    );
    
    // Get available languages
    $settings = get_option('krom_translation_settings');
    $languages = isset($settings['available_languages']) ? $settings['available_languages'] : array('en');
    $current_lang = krom_get_current_language();
    
    // Language display names (you can expand this list)
    $lang_names = array(
        'en' => 'English',
        'id' => 'Indonesia',  
    );
    
    // Build the language switcher based on style
    $output = '<div class="krom-language-switcher krom-style-' . esc_attr($atts['style']) . '">';
    
    switch($atts['style']) {
        case 'dropdown':
            $output .= '<select class="krom-language-select" onchange="kromSwitchLanguage(this.value)">';
            foreach($languages as $lang) {
                $selected = ($lang == $current_lang) ? 'selected' : '';
                $name = isset($lang_names[$lang]) ? $lang_names[$lang] : $lang;
                $output .= '<option value="' . esc_attr($lang) . '" ' . $selected . '>' . esc_html($name) . '</option>';
            }
            $output .= '</select>';
            break;
            
        case 'flags':
            $output .= '<ul class="krom-language-flags">';
            foreach($languages as $lang) {
                $active = ($lang == $current_lang) ? 'krom-lang-active' : '';
                $name = isset($lang_names[$lang]) ? $lang_names[$lang] : $lang;
                $output .= '<li class="' . esc_attr($active) . '">';
                $output .= '<a href="#" onclick="kromSwitchLanguage(\'' . esc_attr($lang) . '\'); return false;">';
                $output .= '<img src="' . KROM_TRANS_URL . 'assets/images/flags/' . esc_attr($lang) . '.png" alt="' . esc_attr($name) . '">';
                if($atts['show_names'] === 'true') {
                    $output .= ' <span>' . esc_html($name) . '</span>';
                }
                $output .= '</a></li>';
            }
            $output .= '</ul>';
            break;
            
        case 'list':
        default:
            $output .= '<ul class="krom-language-list">';
            foreach($languages as $lang) {
                $active = ($lang == $current_lang) ? 'krom-lang-active' : '';
                $name = isset($lang_names[$lang]) ? $lang_names[$lang] : $lang;
                $output .= '<li class="' . esc_attr($active) . '">';
                $output .= '<a href="#" onclick="kromSwitchLanguage(\'' . esc_attr($lang) . '\'); return false;">';
                $output .= esc_html($name);
                $output .= '</a></li>';
            }
            $output .= '</ul>';
            break;
    }
    
    $output .= '</div>';
    
    // Add necessary JavaScript
    wp_enqueue_script('krom-translation-frontend');
    wp_enqueue_style('krom-translation-frontend');
    
    return $output;
}

/**
 * Plugin activation hook
 */
function krom_translation_activate() {
    // Create necessary database tables if needed
    // Initialize default settings
    add_option('krom_translation_settings', array(
        'default_language' => 'en',
        'available_languages' => array('en', 'id'),
    ));
    
    // Ensure rewrite rules will be flushed
    update_option('krom_flush_needed', 'yes');
}
register_activation_hook(__FILE__, 'krom_translation_activate');

/**
 * Check if rewrite rules need to be flushed
 */
function krom_check_flush_rules() {
    if (get_option('krom_flush_needed') === 'yes') {
        flush_rewrite_rules();
        delete_option('krom_flush_needed');
    }
}
add_action('wp_loaded', 'krom_check_flush_rules');

/**
 * Plugin deactivation hook
 */
function krom_translation_deactivate() {
    // Cleanup if needed
}
register_deactivation_hook(__FILE__, 'krom_translation_deactivate');