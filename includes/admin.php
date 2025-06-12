<?php
/**
 * Admin functionality for Krom Manual Translation plugin
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Register admin menu pages
 */
function krom_register_admin_menu() {
    // Add main menu item
    add_menu_page(
        __('Krom Translation', 'krom-manual-translation'),
        __('Translation', 'krom-manual-translation'),
        'manage_options',
        'krom-translation',
        'krom_admin_main_page',
        'dashicons-translation',
        70
    );
    
    // Add submenu items
    add_submenu_page(
        'krom-translation',
        __('Manage Translations', 'krom-manual-translation'),
        __('Manage Translations', 'krom-manual-translation'),
        'manage_options',
        'krom-translation',
        'krom_admin_main_page'
    );
    
    add_submenu_page(
        'krom-translation',
        __('Menu Translations', 'krom-manual-translation'),
        __('Menu Translations', 'krom-manual-translation'),
        'manage_options',
        'krom-menu-translations',
        'krom_admin_menu_translations_page'
    );
    
    add_submenu_page(
        'krom-translation',
        __('Settings', 'krom-manual-translation'),
        __('Settings', 'krom-manual-translation'),
        'manage_options',
        'krom-translation-settings',
        'krom_admin_settings_page'
    );
}
add_action('admin_menu', 'krom_register_admin_menu');

/**
 * Main admin page content
 */
function krom_admin_main_page() {
    // Check user capabilities
    if (!current_user_can('manage_options')) {
        return;
    }
    
    // Process form submission
    if (isset($_POST['krom_save_translation']) && check_admin_referer('krom_translation_action', 'krom_translation_nonce')) {
        // Handle form submission for translations
        krom_process_translation_form();
    }
    
    // Get available languages
    $settings = get_option('krom_translation_settings');
    $languages = isset($settings['available_languages']) ? $settings['available_languages'] : array('en', 'id');
    $default_lang = isset($settings['default_language']) ? $settings['default_language'] : 'en';
    
    // Get existing translations
    $translations = get_option('krom_translations', array());
    
    // Display the admin page
    ?>
    <div class="wrap">
        <h1><?php echo esc_html__('Manage Translations', 'krom-manual-translation'); ?></h1>
        
        <form method="post" action="">
            <?php wp_nonce_field('krom_translation_action', 'krom_translation_nonce'); ?>
            
            <div class="krom-admin-tabs">
                <ul class="krom-tabs-nav">
                    <li class="active"><a href="#add-new"><?php echo esc_html__('Add New Translation', 'krom-manual-translation'); ?></a></li>
                    <li><a href="#existing"><?php echo esc_html__('Existing Translations', 'krom-manual-translation'); ?></a></li>
                </ul>
                
                <div class="krom-tab-content active" id="add-new">
                    <table class="form-table">
                        <tr>
                            <th scope="row"><?php echo esc_html__('Original Text', 'krom-manual-translation'); ?></th>
                            <td>
                                <textarea name="krom_original_text" rows="3" class="large-text" required></textarea>
                                <p class="description"><?php echo esc_html__('The original text to be translated (in the default language)', 'krom-manual-translation'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php echo esc_html__('Translation ID', 'krom-manual-translation'); ?></th>
                            <td>
                                <input type="text" name="krom_translation_id" class="regular-text" />
                                <p class="description"><?php echo esc_html__('Optional. A unique identifier for this translation.', 'krom-manual-translation'); ?></p>
                            </td>
                        </tr>
                        
                        <?php foreach ($languages as $lang): ?>
                            <?php if ($lang != $default_lang): ?>
                                <tr>
                                    <th scope="row"><?php echo esc_html(sprintf(__('Translation (%s)', 'krom-manual-translation'), $lang)); ?></th>
                                    <td>
                                        <textarea name="krom_translation_<?php echo esc_attr($lang); ?>" rows="3" class="large-text"></textarea>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </table>
                    
                    <p class="submit">
                        <input type="submit" name="krom_save_translation" class="button button-primary" value="<?php echo esc_attr__('Save Translation', 'krom-manual-translation'); ?>" />
                    </p>
                </div>
                
                <div class="krom-tab-content" id="existing">
                    <table class="widefat striped">
                        <thead>
                            <tr>
                                <th><?php echo esc_html__('Original Text', 'krom-manual-translation'); ?></th>
                                <th><?php echo esc_html__('ID', 'krom-manual-translation'); ?></th>
                                <?php foreach ($languages as $lang): ?>
                                    <?php if ($lang != $default_lang): ?>
                                        <th><?php echo esc_html($lang); ?></th>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                                <th><?php echo esc_html__('Actions', 'krom-manual-translation'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($translations)): ?>
                                <?php foreach ($translations as $original => $trans_data): ?>
                                    <tr>
                                        <td><?php echo esc_html($original); ?></td>
                                        <td><?php echo esc_html(isset($trans_data['id']) ? $trans_data['id'] : ''); ?></td>
                                        <?php foreach ($languages as $lang): ?>
                                            <?php if ($lang != $default_lang): ?>
                                                <td><?php echo esc_html(isset($trans_data[$lang]) ? $trans_data[$lang] : ''); ?></td>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                        <td>
                                            <a href="<?php echo esc_url(add_query_arg(array('action' => 'edit', 'text' => urlencode($original)))); ?>" class="button button-small"><?php echo esc_html__('Edit', 'krom-manual-translation'); ?></a>
                                            <a href="<?php echo esc_url(add_query_arg(array('action' => 'delete', 'text' => urlencode($original), 'krom_translation_nonce' => wp_create_nonce('krom_translation_action')))); ?>" class="button button-small button-link-delete" onclick="return confirm('<?php echo esc_js(__('Are you sure you want to delete this translation?', 'krom-manual-translation')); ?>');"><?php echo esc_html__('Delete', 'krom-manual-translation'); ?></a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="<?php echo 3 + count($languages) - 1; ?>"><?php echo esc_html__('No translations found.', 'krom-manual-translation'); ?></td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </form>
    </div>
    
    <style>
        .krom-admin-tabs {
            margin-top: 20px;
        }
        .krom-tabs-nav {
            display: flex;
            margin: 0;
            padding: 0;
            list-style: none;
            border-bottom: 1px solid #ccc;
        }
        .krom-tabs-nav li {
            margin: 0 0 -1px 0;
        }
        .krom-tabs-nav li a {
            display: block;
            padding: 10px 15px;
            text-decoration: none;
            background: #f1f1f1;
            border: 1px solid #ccc;
        }
        .krom-tabs-nav li.active a {
            background: #fff;
            border-bottom-color: #fff;
        }
        .krom-tab-content {
            display: none;
            padding: 20px;
            border: 1px solid #ccc;
            border-top: none;
        }
        .krom-tab-content.active {
            display: block;
        }
    </style>
    
    <script>
    jQuery(document).ready(function($) {
        $('.krom-tabs-nav a').on('click', function(e) {
            e.preventDefault();
            var target = $(this).attr('href');
            
            // Update active tab
            $('.krom-tabs-nav li').removeClass('active');
            $(this).parent().addClass('active');
            
            // Update tab content
            $('.krom-tab-content').removeClass('active');
            $(target).addClass('active');
        });
    });
    </script>
    <?php
}

/**
 * Menu translations admin page
 */
function krom_admin_menu_translations_page() {
    // Check user capabilities
    if (!current_user_can('manage_options')) {
        return;
    }
    
    // Get all menus
    $menus = wp_get_nav_menus();
    $menu_translations = get_option('krom_menu_translations', array());
    $settings = get_option('krom_translation_settings', array(
        'default_language' => 'en',
        'available_languages' => array('en', 'id'),
    ));
    
    ?>
    <div class="wrap">
        <h1><?php echo esc_html__('Menu Translations', 'krom-manual-translation'); ?></h1>
        
        <div class="notice notice-info">
            <p><?php echo esc_html__('You can translate menu items directly from the', 'krom-manual-translation'); ?> 
            <a href="<?php echo admin_url('nav-menus.php'); ?>"><?php echo esc_html__('Menus page', 'krom-manual-translation'); ?></a>. 
            <?php echo esc_html__('This page shows an overview of all menu translations.', 'krom-manual-translation'); ?></p>
        </div>
        
        <?php if (empty($menus)): ?>
            <div class="notice notice-warning">
                <p><?php echo esc_html__('No menus found.', 'krom-manual-translation'); ?> 
                <a href="<?php echo admin_url('nav-menus.php'); ?>"><?php echo esc_html__('Create a menu first', 'krom-manual-translation'); ?></a>.</p>
            </div>
        <?php else: ?>
            <?php foreach ($menus as $menu): ?>
                <?php 
                $menu_items = wp_get_nav_menu_items($menu->term_id);
                if (empty($menu_items)) continue;
                ?>
                
                <div class="postbox" style="margin-top: 20px;">
                    <div class="postbox-header">
                        <h2 class="hndle"><?php echo esc_html(sprintf(__('Menu: %s', 'krom-manual-translation'), $menu->name)); ?></h2>
                    </div>
                    <div class="inside">
                        <table class="widefat striped">
                            <thead>
                                <tr>
                                    <th><?php echo esc_html__('Menu Item', 'krom-manual-translation'); ?></th>
                                    <?php foreach ($settings['available_languages'] as $lang): ?>
                                        <?php if ($lang !== $settings['default_language']): ?>
                                            <th><?php echo esc_html(krom_get_language_name($lang)); ?></th>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                    <th><?php echo esc_html__('Actions', 'krom-manual-translation'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($menu_items as $item): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo esc_html($item->title); ?></strong>
                                            <?php if (!empty($item->description)): ?>
                                                <br><small><?php echo esc_html($item->description); ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <?php foreach ($settings['available_languages'] as $lang): ?>
                                            <?php if ($lang !== $settings['default_language']): ?>
                                                <td>
                                                    <?php 
                                                    $translation = isset($menu_translations[$item->ID][$lang]['title']) ? $menu_translations[$item->ID][$lang]['title'] : '';
                                                    if ($translation): ?>
                                                        <strong><?php echo esc_html($translation); ?></strong>
                                                        <?php 
                                                        $attr_title = isset($menu_translations[$item->ID][$lang]['attr_title']) ? $menu_translations[$item->ID][$lang]['attr_title'] : '';
                                                        if ($attr_title): ?>
                                                            <br><small><?php echo esc_html($attr_title); ?></small>
                                                        <?php endif; ?>
                                                    <?php else: ?>
                                                        <em style="color: #999;"><?php echo esc_html__('Not translated', 'krom-manual-translation'); ?></em>
                                                    <?php endif; ?>
                                                </td>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                        <td>
                                            <a href="<?php echo admin_url('nav-menus.php?action=edit&menu=' . $menu->term_id); ?>" class="button button-small">
                                                <?php echo esc_html__('Edit', 'krom-manual-translation'); ?>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
        
        <div style="margin-top: 30px;">
            <h3><?php echo esc_html__('Quick Actions', 'krom-manual-translation'); ?></h3>
            <p>
                <a href="<?php echo admin_url('nav-menus.php'); ?>" class="button button-primary">
                    <?php echo esc_html__('Manage Menus', 'krom-manual-translation'); ?>
                </a>
                <a href="<?php echo admin_url('admin.php?page=krom-translation-settings'); ?>" class="button">
                    <?php echo esc_html__('Translation Settings', 'krom-manual-translation'); ?>
                </a>
            </p>
        </div>
    </div>
    <?php
}

/**
 * Settings admin page content
 */
function krom_admin_settings_page() {
    // Check user capabilities
    if (!current_user_can('manage_options')) {
        return;
    }
    
    // Process form submission
    if (isset($_POST['krom_save_settings']) && check_admin_referer('krom_settings_action', 'krom_settings_nonce')) {
        // Handle form submission for settings
        $settings = array(
            'default_language' => sanitize_text_field($_POST['krom_default_language']),
            'available_languages' => isset($_POST['krom_available_languages']) ? array_map('sanitize_text_field', $_POST['krom_available_languages']) : array('id'),
        );
        
        update_option('krom_translation_settings', $settings);
        echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Settings saved successfully.', 'krom-manual-translation') . '</p></div>';
    }
    
    // Get current settings with Indonesian as default
    $settings = get_option('krom_translation_settings', array(
        'default_language' => 'id', // Changed to Indonesian
        'available_languages' => array('id', 'en'), // Indonesian first
    ));
    
    // Available languages with Indonesian first
    $available_languages = array(
        'id' => __('Indonesia', 'krom-manual-translation'),
        'en' => __('English', 'krom-manual-translation'),
        'fr' => __('French', 'krom-manual-translation'),
        'es' => __('Spanish', 'krom-manual-translation'),
        'de' => __('German', 'krom-manual-translation'),
        'it' => __('Italian', 'krom-manual-translation'),
        'ja' => __('Japanese', 'krom-manual-translation'),
        'ko' => __('Korean', 'krom-manual-translation'),
        'zh' => __('Chinese', 'krom-manual-translation'),
    );
    
    // Display the settings page
    ?>
    <div class="wrap">
        <h1><?php echo esc_html__('Translation Settings', 'krom-manual-translation'); ?></h1>
        
        <form method="post" action="">
            <?php wp_nonce_field('krom_settings_action', 'krom_settings_nonce'); ?>
            
            <table class="form-table">
                <tr>
                    <th scope="row"><?php echo esc_html__('Default Language', 'krom-manual-translation'); ?></th>
                    <td>
                        <select name="krom_default_language">
                            <?php foreach ($available_languages as $code => $name): ?>
                                <option value="<?php echo esc_attr($code); ?>" <?php selected($settings['default_language'], $code); ?>><?php echo esc_html($name); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <p class="description"><?php echo esc_html__('This is the primary language for your content.', 'krom-manual-translation'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php echo esc_html__('Available Languages', 'krom-manual-translation'); ?></th>
                    <td>
                        <?php foreach ($available_languages as $code => $name): ?>
                            <label style="display: block; margin-bottom: 5px;">
                                <input type="checkbox" name="krom_available_languages[]" value="<?php echo esc_attr($code); ?>" <?php checked(in_array($code, $settings['available_languages'])); ?>>
                                <?php echo esc_html($name); ?>
                                <?php if ($code === $settings['default_language']): ?>
                                    <em>(<?php echo esc_html__('Default', 'krom-manual-translation'); ?>)</em>
                                <?php endif; ?>
                            </label>
                        <?php endforeach; ?>
                        <p class="description"><?php echo esc_html__('Select which languages should be available on your site.', 'krom-manual-translation'); ?></p>
                    </td>
                </tr>
            </table>
            
            <p class="submit">
                <input type="submit" name="krom_save_settings" class="button button-primary" value="<?php echo esc_attr__('Save Settings', 'krom-manual-translation'); ?>" />
            </p>
        </form>
    </div>
    <?php
}

/**
 * Process translation form submissions
 */
function krom_process_translation_form() {
    // Get form data
    $original_text = isset($_POST['krom_original_text']) ? wp_kses_post($_POST['krom_original_text']) : '';
    $translation_id = isset($_POST['krom_translation_id']) ? sanitize_text_field($_POST['krom_translation_id']) : '';
    
    if (empty($original_text)) {
        echo '<div class="notice notice-error is-dismissible"><p>' . esc_html__('Original text is required.', 'krom-manual-translation') . '</p></div>';
        return;
    }
    
    // Get available languages
    $settings = get_option('krom_translation_settings');
    $languages = isset($settings['available_languages']) ? $settings['available_languages'] : array('en', 'id');
    $default_lang = isset($settings['default_language']) ? $settings['default_language'] : 'en';
    
    // Get existing translations or initialize empty array
    $translations = get_option('krom_translations', array());
    
    // Prepare translation data for this text
    $translation_data = isset($translations[$original_text]) ? $translations[$original_text] : array();
    
    // Add or update translation ID if provided
    if (!empty($translation_id)) {
        $translation_data['id'] = $translation_id;
    }
    
    // Process translations for each language
    foreach ($languages as $lang) {
        if ($lang != $default_lang) {
            $field_name = 'krom_translation_' . $lang;
            if (isset($_POST[$field_name])) {
                $translation_data[$lang] = wp_kses_post($_POST[$field_name]);
            }
        }
    }
    
    // Save the updated translation
    $translations[$original_text] = $translation_data;
    update_option('krom_translations', $translations);
    
    echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Translation saved successfully.', 'krom-manual-translation') . '</p></div>';
}

/**
 * Enqueue admin scripts and styles
 */
function krom_admin_enqueue_scripts($hook) {
    // Only load on our plugin pages
    if (strpos($hook, 'krom-translation') !== false) {
        wp_enqueue_script('jquery');
    }
}
add_action('admin_enqueue_scripts', 'krom_admin_enqueue_scripts');

/**
 * Add translation fields to menu items in nav-menus.php
 */
function krom_add_menu_item_translation_fields($item_id, $item, $depth, $args) {
    // Get available languages
    $settings = get_option('krom_translation_settings', array(
        'default_language' => 'en',
        'available_languages' => array('en', 'id'),
    ));
    
    $languages = $settings['available_languages'];
    $default_lang = $settings['default_language'];
    
    // Get existing menu item translations
    $menu_translations = get_option('krom_menu_translations', array());
    $item_translations = isset($menu_translations[$item_id]) ? $menu_translations[$item_id] : array();
    
    echo '<div class="krom-menu-translations" style="margin-top: 10px; padding: 10px; background: #f9f9f9; border-left: 4px solid #0073aa;">';
    echo '<h4 style="margin-top: 0;">' . esc_html__('Translations', 'krom-manual-translation') . '</h4>';
    
    foreach ($languages as $lang) {
        if ($lang !== $default_lang) {
            $lang_name = krom_get_language_name($lang);
            $title_value = isset($item_translations[$lang]['title']) ? $item_translations[$lang]['title'] : '';
            $attr_title_value = isset($item_translations[$lang]['attr_title']) ? $item_translations[$lang]['attr_title'] : '';
            
            echo '<div style="margin-bottom: 15px;">';
            echo '<h5 style="margin: 5px 0;">' . esc_html(sprintf(__('%s Translation', 'krom-manual-translation'), $lang_name)) . '</h5>';
            
            // Title field
            echo '<label style="display: block; margin-bottom: 5px;">';
            echo '<strong>' . esc_html__('Navigation Label', 'krom-manual-translation') . ':</strong><br>';
            echo '<input type="text" name="krom_menu_translation[' . esc_attr($item_id) . '][' . esc_attr($lang) . '][title]" ';
            echo 'value="' . esc_attr($title_value) . '" class="widefat" ';
            echo 'placeholder="' . esc_attr(sprintf(__('Enter %s translation for: %s', 'krom-manual-translation'), $lang_name, $item->title)) . '" />';
            echo '</label>';
            
            // Attribute title field
            echo '<label style="display: block; margin-top: 8px;">';
            echo '<strong>' . esc_html__('Title Attribute', 'krom-manual-translation') . ':</strong><br>';
            echo '<input type="text" name="krom_menu_translation[' . esc_attr($item_id) . '][' . esc_attr($lang) . '][attr_title]" ';
            echo 'value="' . esc_attr($attr_title_value) . '" class="widefat" ';
            echo 'placeholder="' . esc_attr(sprintf(__('Enter %s title attribute', 'krom-manual-translation'), $lang_name)) . '" />';
            echo '</label>';
            echo '</div>';
        }
    }
    
    echo '</div>';
}
add_action('wp_nav_menu_item_custom_fields', 'krom_add_menu_item_translation_fields', 10, 4);

/**
 * Save menu item translations
 */
function krom_save_menu_item_translations($menu_id, $menu_item_db_id, $menu_item_args) {
    if (isset($_POST['krom_menu_translation'][$menu_item_db_id])) {
        $translations = $_POST['krom_menu_translation'][$menu_item_db_id];
        
        // Get existing menu translations
        $menu_translations = get_option('krom_menu_translations', array());
        
        // Sanitize and save translations
        $menu_translations[$menu_item_db_id] = array();
        foreach ($translations as $lang => $trans_data) {
            $menu_translations[$menu_item_db_id][$lang] = array(
                'title' => sanitize_text_field($trans_data['title']),
                'attr_title' => sanitize_text_field($trans_data['attr_title']),
            );
        }
        
        // Remove empty translations
        $menu_translations[$menu_item_db_id] = array_filter($menu_translations[$menu_item_db_id], function($lang_data) {
            return !empty($lang_data['title']) || !empty($lang_data['attr_title']);
        });
        
        if (empty($menu_translations[$menu_item_db_id])) {
            unset($menu_translations[$menu_item_db_id]);
        }
        
        update_option('krom_menu_translations', $menu_translations);
    }
}
add_action('wp_update_nav_menu_item', 'krom_save_menu_item_translations', 10, 3);

/**
 * Clean up translations when menu items are deleted
 */
function krom_cleanup_deleted_menu_items($menu_id, $menu_item_db_id) {
    $menu_translations = get_option('krom_menu_translations', array());
    if (isset($menu_translations[$menu_item_db_id])) {
        unset($menu_translations[$menu_item_db_id]);
        update_option('krom_menu_translations', $menu_translations);
    }
}
add_action('wp_delete_nav_menu_item', 'krom_cleanup_deleted_menu_items', 10, 2);

/**
 * Add CSS for menu translation fields
 */
function krom_add_nav_menu_admin_styles() {
    global $pagenow;
    
    if ($pagenow === 'nav-menus.php') {
        ?>
        <style>
        .krom-menu-translations {
            border-radius: 3px;
        }
        .krom-menu-translations h4 {
            color: #0073aa;
            font-size: 14px;
        }
        .krom-menu-translations h5 {
            color: #333;
            font-size: 13px;
            font-weight: 600;
        }
        .krom-menu-translations input[type="text"] {
            margin-top: 3px;
        }
        .krom-menu-translations label strong {
            font-size: 12px;
            color: #555;
        }
        </style>
        <?php
    }
}
add_action('admin_head', 'krom_add_nav_menu_admin_styles');

/**
 * Add JavaScript for enhanced menu translation experience
 */
function krom_add_nav_menu_admin_scripts() {
    global $pagenow;
    
    if ($pagenow === 'nav-menus.php') {
        ?>
        <script>
        jQuery(document).ready(function($) {
            // Auto-populate translation placeholders when original title changes
            $(document).on('input', '.edit-menu-item-title', function() {
                var $this = $(this);
                var newTitle = $this.val();
                var $container = $this.closest('.menu-item-settings');
                
                // Update placeholders in translation fields
                $container.find('.krom-menu-translations input[type="text"]').each(function() {
                    var $input = $(this);
                    var placeholder = $input.attr('placeholder');
                    if (placeholder && placeholder.indexOf('Enter') === 0) {
                        var parts = placeholder.split(': ');
                        if (parts.length > 1) {
                            parts[1] = newTitle;
                            $input.attr('placeholder', parts.join(': '));
                        }
                    }
                });
            });
            
            // Add visual indicator for translated items
            $('.krom-menu-translations').each(function() {
                var $this = $(this);
                var hasTranslations = false;
                
                $this.find('input[type="text"]').each(function() {
                    if ($(this).val().trim() !== '') {
                        hasTranslations = true;
                        return false;
                    }
                });
                
                if (hasTranslations) {
                    $this.closest('.menu-item').find('.menu-item-title').append(' <span style="color: #0073aa; font-size: 12px;">[T]</span>');
                }
            });
        });
        </script>
        <?php
    }
}
add_action('admin_footer', 'krom_add_nav_menu_admin_scripts');

/**
 * Get language name by code
 */
function krom_get_language_name($code) {
    $languages = array(
        'en' => __('English', 'krom-manual-translation'),
        'id' => __('Indonesia', 'krom-manual-translation'),
        'fr' => __('French', 'krom-manual-translation'),
        'es' => __('Spanish', 'krom-manual-translation'),
        'de' => __('German', 'krom-manual-translation'),
        'it' => __('Italian', 'krom-manual-translation'),
        'ja' => __('Japanese', 'krom-manual-translation'),
        'ko' => __('Korean', 'krom-manual-translation'),
        'zh' => __('Chinese', 'krom-manual-translation'),
    );
    
    return isset($languages[$code]) ? $languages[$code] : $code;
}