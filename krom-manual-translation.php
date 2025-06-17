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