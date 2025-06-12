<?php
/**
 * Admin functionality for Krom Manual Translation
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Register admin menu
 */
function krom_register_admin_menu() {
    add_menu_page(
        'Krom Translation',
        'Krom Translation',
        'manage_options',
        'krom-translation',
        'krom_admin_settings_page',
        'dashicons-translation',
        100
    );
    
    add_submenu_page(
        'krom-translation',
        'Settings',
        'Settings',
        'manage_options',
        'krom-translation',
        'krom_admin_settings_page'
    );
    
    add_submenu_page(
        'krom-translation',
        'Translations',
        'Translations',
        'manage_options',
        'krom-translation-editor',
        'krom_admin_translations_page'
    );
}
add_action('admin_menu', 'krom_register_admin_menu');

/**
 * Register admin scripts and styles
 */
function krom_register_admin_assets() {
    // Register styles
    wp_register_style(
        'krom-translation-admin',
        KROM_TRANS_URL . 'assets/css/admin.css',
        array(),
        KROM_TRANS_VERSION
    );
    
    // Register scripts
    wp_register_script(
        'krom-translation-admin',
        KROM_TRANS_URL . 'assets/js/admin.js',
        array('jquery'),
        KROM_TRANS_VERSION,
        true
    );
}
add_action('admin_enqueue_scripts', 'krom_register_admin_assets');

/**
 * Settings page
 */
function krom_admin_settings_page() {
    // Enqueue admin assets
    wp_enqueue_style('krom-translation-admin');
    wp_enqueue_script('krom-translation-admin');
    
    // Save settings
    if (isset($_POST['krom_save_settings']) && check_admin_referer('krom_translation_settings')) {
        // Get and sanitize settings
        $default_language = isset($_POST['default_language']) ? sanitize_text_field($_POST['default_language']) : 'en';
        $available_languages = isset($_POST['available_languages']) ? array_map('sanitize_text_field', $_POST['available_languages']) : array('en');
        
        // Save settings
        update_option('krom_translation_settings', array(
            'default_language' => $default_language,
            'available_languages' => $available_languages,
        ));
        
        // Show success message
        add_settings_error('krom_translation_settings', 'settings_updated', 'Settings saved successfully.', 'updated');
    }
    
    // Get current settings
    $settings = get_option('krom_translation_settings');
    $default_language = isset($settings['default_language']) ? $settings['default_language'] : 'en';
    $available_languages = isset($settings['available_languages']) ? $settings['available_languages'] : array('en');
    
    // Available languages (you can expand this list)
    $lang_options = array(
        'en' => 'English',
        'id' => 'Indonesia',
        'es' => 'Español',
        'fr' => 'Français',
        'de' => 'Deutsch',
        'zh' => '中文',
    );
    
    // Render settings page
    ?>
    <div class="wrap">
        <h1>Krom Translation Settings</h1>
        
        <?php settings_errors('krom_translation_settings'); ?>
        
        <form method="post" action="">
            <?php wp_nonce_field('krom_translation_settings'); ?>
            
            <table class="form-table">
                <tr>
                    <th scope="row">Default Language</th>
                    <td>
                        <select name="default_language">
                            <?php foreach ($lang_options as $code => $name): ?>
                                <option value="<?php echo esc_attr($code); ?>" <?php selected($default_language, $code); ?>>
                                    <?php echo esc_html($name); ?> (<?php echo esc_html($code); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Available Languages</th>
                    <td>
                        <?php foreach ($lang_options as $code => $name): ?>
                            <label>
                                <input type="checkbox" name="available_languages[]" value="<?php echo esc_attr($code); ?>" 
                                    <?php checked(in_array($code, $available_languages)); ?>>
                                <?php echo esc_html($name); ?> (<?php echo esc_html($code); ?>)
                            </label><br>
                        <?php endforeach; ?>
                    </td>
                </tr>
            </table>
            
            <p class="submit">
                <input type="submit" name="krom_save_settings" class="button button-primary" value="Save Settings">
            </p>
        </form>
    </div>
    <?php
}

/**
 * Translations editor page
 */
function krom_admin_translations_page() {
    // Enqueue admin assets
    wp_enqueue_style('krom-translation-admin');
    wp_enqueue_script('krom-translation-admin');
    
    // Save translations
    if (isset($_POST['krom_save_translations']) && check_admin_referer('krom_translation_editor')) {
        $translations = array();
        
        if (isset($_POST['translation_key']) && is_array($_POST['translation_key'])) {
            foreach ($_POST['translation_key'] as $index => $key) {
                if (empty($key)) continue;
                
                $translations[$key] = array();
                
                // Get available languages
                $settings = get_option('krom_translation_settings');
                $languages = isset($settings['available_languages']) ? $settings['available_languages'] : array('en');
                
                // Process each language
                foreach ($languages as $lang) {
                    if (isset($_POST['translation_' . $lang][$index])) {
                        $translations[$key][$lang] = wp_kses_post($_POST['translation_' . $lang][$index]);
                    }
                }
            }
        }
        
        // Save to JSON file
        $file_path = KROM_TRANS_PATH . 'translation.json';
        file_put_contents($file_path, json_encode($translations, JSON_PRETTY_PRINT));
        
        // Show success message
        add_settings_error('krom_translation_editor', 'translations_updated', 'Translations saved successfully.', 'updated');
    }
    
    // Load current translations
    $translations = krom_load_translations();
    
    // Get available languages
    $settings = get_option('krom_translation_settings');
    $languages = isset($settings['available_languages']) ? $settings['available_languages'] : array('en');
    
    // Language display names (you can expand this list)
    $lang_names = array(
        'en' => 'English',
        'id' => 'Indonesia',
        'es' => 'Español',
        'fr' => 'Français',
        'de' => 'Deutsch',
        'zh' => '中文',
    );
    
    // Render translations editor
    ?>
    <div class="wrap">
        <h1>Krom Translation Editor</h1>
        
        <?php settings_errors('krom_translation_editor'); ?>
        
        <form method="post" action="">
            <?php wp_nonce_field('krom_translation_editor'); ?>
            
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th width="20%">Text ID / Hash</th>
                        <?php foreach ($languages as $lang): ?>
                            <th><?php echo isset($lang_names[$lang]) ? esc_html($lang_names[$lang]) : esc_html($lang); ?></th>
                        <?php endforeach; ?>
                        <th width="5%">Actions</th>
                    </tr>
                </thead>
                <tbody id="krom-translations-table">
                    <?php if (empty($translations)): ?>
                        <tr class="translation-row">
                            <td>
                                <input type="text" name="translation_key[]" class="widefat" placeholder="Text ID or leave empty for auto-hash">
                            </td>
                            <?php foreach ($languages as $lang): ?>
                                <td>
                                    <textarea name="translation_<?php echo esc_attr($lang); ?>[]" class="widefat" rows="3"></textarea>
                                </td>
                            <?php endforeach; ?>
                            <td>
                                <button type="button" class="button remove-translation">Remove</button>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($translations as $key => $trans): ?>
                            <tr class="translation-row">
                                <td>
                                    <input type="text" name="translation_key[]" value="<?php echo esc_attr($key); ?>" class="widefat">
                                </td>
                                <?php foreach ($languages as $lang): ?>
                                    <td>
                                        <textarea name="translation_<?php echo esc_attr($lang); ?>[]" class="widefat" rows="3"><?php 
                                            echo isset($trans[$lang]) ? esc_textarea($trans[$lang]) : ''; 
                                        ?></textarea>
                                    </td>
                                <?php endforeach; ?>
                                <td>
                                    <button type="button" class="button remove-translation">Remove</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
            
            <p>
                <button type="button" id="add-translation" class="button">Add Translation</button>
            </p>
            
            <p class="submit">
                <input type="submit" name="krom_save_translations" class="button button-primary" value="Save Translations">
            </p>
        </form>
        
        <script>
            jQuery(document).ready(function($) {
                // Add new translation row
                $('#add-translation').on('click', function() {
                    var row = $('.translation-row:first').clone();
                    row.find('input, textarea').val('');
                    $('#krom-translations-table').append(row);
                });
                
                // Remove translation row
                $('#krom-translations-table').on('click', '.remove-translation', function() {
                    if ($('.translation-row').length > 1) {
                        $(this).closest('.translation-row').remove();
                    } else {
                        alert('You need at least one translation row.');
                    }
                });
            });
        </script>
    </div>
    <?php
}