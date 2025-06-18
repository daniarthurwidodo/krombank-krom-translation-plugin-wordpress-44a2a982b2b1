<?php
/**
 * Post translation metaboxes functionality
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Register post translation metaboxes
 */
function krom_register_translation_metaboxes() {
    // Post types to add translation metaboxes
    $post_types = apply_filters('krom_translatable_post_types', array('post', 'page'));
    
    foreach ($post_types as $post_type) {
        // Only add English translation metaboxes - Indonesian will use default editor
        add_meta_box(
            'krom_title_translation_en',
            'English Title',
            'krom_title_translation_callback',
            $post_type,
            'normal',
            'high',
            array(
                'lang' => 'en',
                '__block_editor_compatible_meta_box' => false, 
                '__back_compat_meta_box' => true
            )
        );
        
        add_meta_box(
            'krom_content_translation_en',
            'English Content',
            'krom_content_translation_callback',
            $post_type,
            'normal',
            'high',
            array(
                'lang' => 'en',
                '__block_editor_compatible_meta_box' => false, 
                '__back_compat_meta_box' => true
            )
        );
    }
}
add_action('add_meta_boxes', 'krom_register_translation_metaboxes');

/**
 * Title translation metabox callback
 */
function krom_title_translation_callback($post, $metabox) {
    $lang = $metabox['args']['lang'];
    $title_translation = get_post_meta($post->ID, '_krom_title_' . $lang, true);
    
    // Add nonce for security
    wp_nonce_field('krom_translation_metabox', 'krom_translation_nonce_' . $lang);
    
    // For debugging - show the current saved value
    echo '<!-- Current saved title for ' . $lang . ': ' . esc_html($title_translation) . ' -->';
    
    echo '<input type="text" name="krom_title_translation_' . esc_attr($lang) . '" id="krom_title_translation_' . esc_attr($lang) . '" placeholder="Enter ' . esc_attr(ucfirst($lang)) . ' title" style="width: 100%; padding: 8px; font-size: 1.2em;" value="' . esc_attr($title_translation) . '">';
}

/**
 * Content translation metabox callback with WYSIWYG editor
 */
function krom_content_translation_callback($post, $metabox) {
    $lang = $metabox['args']['lang'];
    $content_translation = get_post_meta($post->ID, '_krom_content_' . $lang, true);
    
    // Initialize WordPress editor with standard settings
    wp_editor(
        $content_translation,
        'krom_content_translation_' . $lang,
        array(
            'media_buttons' => true,
            'textarea_name' => 'krom_content_translation_' . $lang,
            'textarea_rows' => 20,
            'teeny' => false,
            'dfw' => true,
            'tinymce' => true,
            'quicktags' => true,
            'drag_drop_upload' => true,
            'editor_height' => 300
        )
    );
}

/**
 * Save post translation meta data
 */
function krom_save_translation_meta($post_id) {
    // Check for autosave
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    
    // Check permissions
    if (isset($_POST['post_type'])) {
        if ('page' === $_POST['post_type']) {
            if (!current_user_can('edit_page', $post_id)) {
                return;
            }
        } else {
            if (!current_user_can('edit_post', $post_id)) {
                return;
            }
        }
    }
    
    // Save English translations
    if (isset($_POST['krom_translation_nonce_en']) && wp_verify_nonce($_POST['krom_translation_nonce_en'], 'krom_translation_metabox')) {
        // Save English title
        if (isset($_POST['krom_title_translation_en'])) {
            update_post_meta(
                $post_id,
                '_krom_title_en',
                sanitize_text_field($_POST['krom_title_translation_en'])
            );
        }
        
        // Save English content
        if (isset($_POST['krom_content_translation_en'])) {
            update_post_meta(
                $post_id,
                '_krom_content_en',
                wp_kses_post($_POST['krom_content_translation_en'])
            );
        }
    }
    
    // Save Indonesian content from default editor
    update_post_meta(
        $post_id,
        '_krom_title_id',
        get_the_title($post_id)
    );
    
    $post = get_post($post_id);
    if ($post) {
        update_post_meta(
            $post_id,
            '_krom_content_id',
            $post->post_content
        );
    }
}
add_action('save_post', 'krom_save_translation_meta');

/**
 * Log save post data for debugging
 */
function krom_debug_save_post($post_id) {
    // Only run in development environment
    if (!defined('WP_DEBUG') || !WP_DEBUG) {
        return;
    }
    
    // Create log directory if it doesn't exist
    $log_dir = WP_CONTENT_DIR . '/krom-debug-logs';
    if (!file_exists($log_dir)) {
        mkdir($log_dir, 0755, true);
    }
    
    // Build log message
    $log = "=== Save Post Debug: " . date('Y-m-d H:i:s') . " ===\n";
    $log .= "Post ID: " . $post_id . "\n";
    
    // Check if English translation fields were submitted
    $log .= "Nonce present: " . (isset($_POST['krom_translation_nonce_en']) ? 'Yes' : 'No') . "\n";
    
    if (isset($_POST['krom_translation_nonce_en'])) {
        $log .= "Nonce valid: " . (wp_verify_nonce($_POST['krom_translation_nonce_en'], 'krom_translation_metabox') ? 'Yes' : 'No') . "\n";
    }
    
    $log .= "English title field present: " . (isset($_POST['krom_title_translation_en']) ? 'Yes' : 'No') . "\n";
    if (isset($_POST['krom_title_translation_en'])) {
        $log .= "English title value: " . substr($_POST['krom_title_translation_en'], 0, 50) . "\n";
    }
    
    $log .= "English content field present: " . (isset($_POST['krom_content_translation_en']) ? 'Yes' : 'No') . "\n";
    if (isset($_POST['krom_content_translation_en'])) {
        $log .= "English content length: " . strlen($_POST['krom_content_translation_en']) . " chars\n";
    }
    
    // Write to log file
    file_put_contents($log_dir . '/save-post-debug.log', $log . "\n\n", FILE_APPEND);
}
add_action('save_post', 'krom_debug_save_post', 5);

/**
 * Filter post title based on language
 */
function krom_filter_post_title($title, $post_id = null) {
    // Don't filter in admin area or for non-posts
    if (is_admin() || empty($post_id)) {
        return $title;
    }
    
    // Get current language
    $current_lang = krom_get_current_language();
    
    // For English, use the translation from meta
    if ($current_lang === 'en') {
        $translated_title = get_post_meta($post_id, '_krom_title_en', true);
        if (!empty($translated_title)) {
            return $translated_title;
        }
    }
    
    // For Indonesian or fallback, use the default post title
    return $title;
}
add_filter('the_title', 'krom_filter_post_title', 10, 2);

/**
 * Filter post content based on language
 */
function krom_filter_post_content($content) {
    // Don't filter in admin area
    if (is_admin()) {
        return $content;
    }
    
    // Get current post ID
    $post_id = get_the_ID();
    if (!$post_id) {
        return $content;
    }
    
    // Get current language
    $current_lang = krom_get_current_language();
    
    // For English, use the translation from meta
    if ($current_lang === 'en') {
        $translated_content = get_post_meta($post_id, '_krom_content_en', true);
        if (!empty($translated_content)) {
            return $translated_content;
        }
    }
    
    // For Indonesian or fallback, use the default post content
    return $content;
}
add_filter('the_content', 'krom_filter_post_content', 20);  // Higher priority to ensure it runs after other content filters

/**
 * Add language indicator to admin bar
 */
function krom_admin_bar_language_indicator($wp_admin_bar) {
    // Only show for users who can edit posts
    if (!current_user_can('edit_posts')) {
        return;
    }
    
    $current_lang = krom_get_current_language();
    $lang_name = $current_lang === 'id' ? 'Indonesia' : 'English';
    
    $wp_admin_bar->add_node(array(
        'id'    => 'krom-language-indicator',
        'title' => 'Current Language: ' . $lang_name,
        'meta'  => array(
            'class' => 'krom-language-indicator',
        ),
    ));
}
add_action('admin_bar_menu', 'krom_admin_bar_language_indicator', 100);

/**
 * Add notice to explain that default editor is for Indonesian content
 */
function krom_add_admin_notice() {
    $screen = get_current_screen();
    if (!in_array($screen->base, array('post'))) {
        return;
    }
    
    echo '<div class="notice notice-info">
        <p><strong>Note:</strong> The default title and content fields are for <strong>Indonesian</strong> content. Use the English Title and English Content metaboxes below for English translations.</p>
    </div>';
}
add_action('admin_notices', 'krom_add_admin_notice');

/**
 * Add admin CSS for translation metaboxes
 */
function krom_add_translation_metabox_styles() {
    echo '<style>
        /* English metaboxes */
        #krom_title_translation_en, #krom_content_translation_en {
            background-color: #fff;
            border-left: 5px solid #0073aa;
            margin-top: 15px;
        }
        #krom_title_translation_en h2, #krom_content_translation_en h2 {
            color: #0073aa;
            font-weight: 600;
        }
        
        /* Default editor styling - highlight for Indonesian */
        #titlediv:before {
            content: "Indonesian Title";
            display: block;
            color: #d54e21;
            font-weight: 600;
            margin-bottom: 5px;
        }
        #wp-content-editor-container {
            border-left: 5px solid #d54e21 !important;
        }
        
        /* Common styles */
        #krom_title_translation_en input {
            border: 1px solid #ddd;
            border-radius: 3px;
            box-shadow: inset 0 1px 2px rgba(0,0,0,.07);
        }
        
        /* Reset any custom borders on the editor */
        #wp-krom_content_translation_en-wrap {
            background: #fff;
        }
        #wp-krom_content_translation_en-wrap .wp-editor-tools {
            background: #f5f5f5;
            border: 1px solid #e2e4e7;
            border-bottom: 0;
        }
        #wp-krom_content_translation_en-wrap .mce-container.mce-panel {
            border-color: #e2e4e7;
        }
        
        /* Hide metabox move handles */
        .postbox .handle-order-higher,
        .postbox .handle-order-lower {
            display: none !important;
        }
        
        /* Make the metabox header less clickable-looking */
        .postbox .handlediv {
            visibility: hidden;
        }
    </style>';
    
    // Add JS to prevent metabox dragging and ensure editor height
    echo '<script type="text/javascript">
        jQuery(document).ready(function($) {
            // Make translation metaboxes non-sortable
            $(".meta-box-sortables").sortable({
                items: ".postbox:not(#krom_title_translation_en, #krom_content_translation_en)"
            });
            
            // Ensure editor height matches default editor
            setTimeout(function() {
                if (typeof tinymce !== "undefined") {
                    var editor = tinymce.get("krom_content_translation_en");
                    if (editor) {
                        editor.theme.resizeTo("100%", 300);
                    }
                }
            }, 1000);
        });
    </script>';
}
add_action('admin_head', 'krom_add_translation_metabox_styles');

/**
 * Disable metabox interface for translation boxes
 */
function krom_disable_metabox_interface() {
    global $current_screen;
    
    if ($current_screen->base === 'post') {
        echo '<style type="text/css">
            /* Only disable moving, but keep toggle functionality */
            #krom_title_translation_en h2.hndle, 
            #krom_content_translation_en h2.hndle {
                cursor: pointer !important;
            }
            
            /* Make sure the editor has sufficient padding */
            #krom_content_translation_en .inside {
                padding: 0;
                margin: 0;
            }
            
            /* Fix toolbar appearance */
            #wp-krom_content_translation_en-editor-tools {
                background-color: #f5f5f5;
                padding: 6px 10px;
                border-bottom: 1px solid #e2e4e7;
            }
            
            /* Ensure consistent button styling */
            #wp-krom_content_translation_en-media-buttons {
                float: left;
            }
            
            /* Better editor height */
            #wp-krom_content_translation_en-editor-container iframe {
                min-height: 300px;
            }
        </style>';
    }
}
add_action('admin_head', 'krom_disable_metabox_interface');

/**
 * Ensure TinyMCE editor is properly initialized
 */
function krom_ensure_editor_init() {
    global $current_screen;
    
    if ($current_screen->base === 'post') {
        echo '<script type="text/javascript">
            jQuery(document).ready(function($) {
                // Re-init editors if needed
                $(document).on("click", ".wp-editor-tabs button", function() {
                    var $wrap = $(this).closest(".wp-editor-wrap");
                    var editorId = $wrap.find("textarea").attr("id");
                    
                    if ($(this).hasClass("switch-tmce") && editorId) {
                        if (typeof tinymce !== "undefined") {
                            tinymce.execCommand("mceRemoveEditor", false, editorId);
                            tinymce.execCommand("mceAddEditor", false, editorId);
                        }
                    }
                });
            });
        </script>';
    }
}
add_action('admin_footer', 'krom_ensure_editor_init');

/**
 * Fix "View Post" link in admin
 */
function krom_fix_admin_view_link($permalink, $post) {
    // Only modify in admin
    if (!is_admin()) {
        return $permalink;
    }
    
    // Get default language for admin preview
    $lang = KROM_TRANSLATION_DEFAULT_LANG;
    
    // If permalink doesn't already contain language code
    if (!preg_match('~^' . home_url('/' . $lang . '/') . '~', $permalink)) {
        // Add language code to URL
        $permalink = str_replace(home_url('/'), home_url('/' . $lang . '/'), $permalink);
    }
    
    return $permalink;
}
add_filter('preview_post_link', 'krom_fix_admin_view_link', 10, 2);
add_filter('get_sample_permalink_html', function($html, $post_id, $title, $slug, $post) {
    $permalink = get_permalink($post);
    $lang = KROM_TRANSLATION_DEFAULT_LANG;
    
    // Make sure language code is in the permalink
    if (!preg_match('~/' . $lang . '/~', $permalink)) {
        $permalink = str_replace(home_url('/'), home_url('/' . $lang . '/'), $permalink);
    }
    
    // Update the "View Post" link HTML
    $html = preg_replace('~href="([^"]+)"~', 'href="' . $permalink . '"', $html);
    
    return $html;
}, 10, 5);

// Remove functions that are no longer needed
remove_action('edit_form_after_title', 'krom_add_admin_language_switcher');
remove_filter('admin_url', 'krom_add_language_to_admin_url', 10);
remove_action('admin_head', 'krom_hide_default_title_in_english_mode');
remove_filter('use_block_editor_for_post_type', 'krom_hide_default_editor_in_english_mode', 10);
remove_filter('gutenberg_can_edit_post_type', 'krom_hide_default_editor_in_english_mode', 10);
remove_action('admin_footer', 'krom_debug_language_detection');