<?php
/**
 * Core functionality for Krom Manual Translation
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Get current language
 * 
 * @return string Current language code
 */
function krom_get_current_language() {
    $settings = get_option('krom_translation_settings');
    $default = isset($settings['default_language']) ? $settings['default_language'] : 'en';
    
    // Check if language set in URL parameter
    if (isset($_GET['lang']) && krom_is_valid_language($_GET['lang'])) {
        return sanitize_text_field($_GET['lang']);
    }
    
    // Check if language set in session
    if (isset($_SESSION['krom_language'])) {
        return $_SESSION['krom_language'];
    }
    
    return $default;
}

/**
 * Check if language is valid
 * 
 * @param string $lang Language code to check
 * @return bool True if valid language
 */
function krom_is_valid_language($lang) {
    $settings = get_option('krom_translation_settings');
    $available = isset($settings['available_languages']) ? $settings['available_languages'] : array('en');
    
    return in_array($lang, $available);
}

/**
 * Get translation for content
 * 
 * @param string $content Original content
 * @param string $lang Target language
 * @param string $id Optional ID for specific text
 * @return string Translated content
 */
function krom_get_translation($content, $lang, $id = '') {
    // Load translations from JSON
    $translations = krom_load_translations();
    
    // Generate content hash if no ID provided
    $content_key = !empty($id) ? $id : md5($content);
    
    // Check if translation exists
    if (isset($translations[$content_key][$lang])) {
        return $translations[$content_key][$lang];
    }
    
    // Return original content if no translation found
    return $content;
}

/**
 * Load translations from JSON file
 * 
 * @return array Translation data
 */
function krom_load_translations() {
    $file_path = KROM_TRANS_PATH . 'translation.json';
    
    if (file_exists($file_path)) {
        $json_data = file_get_contents($file_path);
        $translations = json_decode($json_data, true);
        
        if (is_array($translations)) {
            return $translations;
        }
    }
    
    return array();
}