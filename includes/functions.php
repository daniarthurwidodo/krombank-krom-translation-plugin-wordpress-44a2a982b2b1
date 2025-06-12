<?php
/**
 * Common functions for Krom Manual Translation plugin
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
    // Check if we already determined the language in this request
    static $current_language = null;
    if ($current_language !== null) {
        return $current_language;
    }
    
    // Check for language in URL first (highest priority)
    $url_lang = krom_get_language_from_url();
    if ($url_lang) {
        // Set cookie and session when language is detected from URL
        krom_set_current_language($url_lang);
        $current_language = $url_lang;
        return $current_language;
    }
    
    // Check for language in GET parameter
    if (isset($_GET['lang'])) {
        $lang = sanitize_text_field($_GET['lang']);
        $settings = get_option('krom_translation_settings');
        $available_languages = isset($settings['available_languages']) ? $settings['available_languages'] : array('id', 'en');
        if (in_array($lang, $available_languages)) {
            krom_set_current_language($lang);
            $current_language = $lang;
            return $current_language;
        }
    }
    
    // Check for language in cookie
    if (isset($_COOKIE['krom_language'])) {
        $lang = sanitize_text_field($_COOKIE['krom_language']);
        // Validate that the language is available
        $settings = get_option('krom_translation_settings');
        $available_languages = isset($settings['available_languages']) ? $settings['available_languages'] : array('id', 'en');
        if (in_array($lang, $available_languages)) {
            $current_language = $lang;
            return $current_language;
        }
    }
    
    // Check for language in session
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (isset($_SESSION['krom_language'])) {
        $lang = sanitize_text_field($_SESSION['krom_language']);
        $settings = get_option('krom_translation_settings');
        $available_languages = isset($settings['available_languages']) ? $settings['available_languages'] : array('id', 'en');
        if (in_array($lang, $available_languages)) {
            $current_language = $lang;
            return $current_language;
        }
    }
    
    // Get settings
    $settings = get_option('krom_translation_settings');
    $default_lang = isset($settings['default_language']) ? $settings['default_language'] : 'id'; // Default to Indonesian
    
    $current_language = $default_lang;
    return $current_language;
}

/**
 * Get language from URL path
 * 
 * @return string|false Language code if found in URL, false otherwise
 */
function krom_get_language_from_url() {
    // Get current URL path
    $request_uri = $_SERVER['REQUEST_URI'];
    $parsed_url = parse_url($request_uri);
    $path = isset($parsed_url['path']) ? trim($parsed_url['path'], '/') : '';
    
    // Get available languages
    $settings = get_option('krom_translation_settings');
    $available_languages = isset($settings['available_languages']) ? $settings['available_languages'] : array('id', 'en');
    
    // Check if path starts with a language code
    $path_parts = explode('/', $path);
    if (!empty($path_parts[0]) && in_array($path_parts[0], $available_languages)) {
        return $path_parts[0];
    }
    
    // Check for subdomain language detection (optional)
    $host = $_SERVER['HTTP_HOST'];
    $host_parts = explode('.', $host);
    if (count($host_parts) > 2 && in_array($host_parts[0], $available_languages)) {
        return $host_parts[0];
    }
    
    return false;
}

/**
 * Set current language
 * 
 * @param string $language Language code to set
 */
function krom_set_current_language($language) {
    // Validate language
    $settings = get_option('krom_translation_settings');
    $available_languages = isset($settings['available_languages']) ? $settings['available_languages'] : array('id', 'en');
    
    if (in_array($language, $available_languages)) {
        // Set cookie (30 days)
        if (!headers_sent()) {
            setcookie('krom_language', $language, time() + (30 * 24 * 60 * 60), COOKIEPATH, COOKIE_DOMAIN);
        }
        
        // Set session
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION['krom_language'] = $language;
        
        // Set global
        $GLOBALS['krom_current_language'] = $language;
        
        return true;
    }
    
    return false;
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
 * Get translation for a text string
 * 
 * @param string $text Original text
 * @param string $language Target language
 * @param string $id Optional text ID
 * @return string Translated text or original if no translation found
 */
function krom_get_translation($text, $language, $id = '') {
    // Get settings
    $settings = get_option('krom_translation_settings');
    $default_lang = isset($settings['default_language']) ? $settings['default_language'] : 'id';
    
    // If requesting default language, return original text
    if ($language === $default_lang) {
        return $text;
    }
    
    // Get translations
    $translations = get_option('krom_translations', array());
    
    // Try to find by ID first if provided
    if (!empty($id)) {
        foreach ($translations as $original => $trans_data) {
            if (isset($trans_data['id']) && $trans_data['id'] === $id) {
                if (isset($trans_data[$language]) && !empty($trans_data[$language])) {
                    return $trans_data[$language];
                }
                break;
            }
        }
    }
    
    // Find by original text
    if (isset($translations[$text]) && isset($translations[$text][$language]) && !empty($translations[$text][$language])) {
        return $translations[$text][$language];
    }
    
    // Return original if no translation found
    return $text;
}

/**
 * Translate text (convenience function)
 * 
 * @param string $text Text to translate
 * @param string $id Optional text ID
 * @return string Translated text
 */
function krom_translate_text($text, $id = '') {
    $language = krom_get_current_language();
    return krom_get_translation($text, $language, $id);
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

/**
 * Get URL for a specific language
 * 
 * @param string $language Language code
 * @param string $url Optional URL, uses current URL if not provided
 * @return string URL with language prefix
 */
function krom_get_language_url($language, $url = '') {
    if (empty($url)) {
        $url = $_SERVER['REQUEST_URI'];
    }
    
    $settings = get_option('krom_translation_settings');
    $default_lang = isset($settings['default_language']) ? $settings['default_language'] : 'id';
    
    // Parse the URL
    $parsed_url = parse_url($url);
    $path = isset($parsed_url['path']) ? trim($parsed_url['path'], '/') : '';
    $query = isset($parsed_url['query']) ? '?' . $parsed_url['query'] : '';
    $fragment = isset($parsed_url['fragment']) ? '#' . $parsed_url['fragment'] : '';
    
    // Remove existing language prefix
    $path_parts = explode('/', $path);
    $available_languages = isset($settings['available_languages']) ? $settings['available_languages'] : array('id', 'en');
    
    if (!empty($path_parts[0]) && in_array($path_parts[0], $available_languages)) {
        array_shift($path_parts);
        $path = implode('/', $path_parts);
    }
    
    // Add language prefix (except for default language)
    if ($language !== $default_lang) {
        $path = $language . '/' . $path;
    }
    
    // Reconstruct URL
    $new_url = '/' . ltrim($path, '/') . $query . $fragment;
    
    return $new_url;
}