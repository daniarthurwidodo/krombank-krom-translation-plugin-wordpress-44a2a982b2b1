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