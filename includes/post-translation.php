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
        // Add English translation metaboxes
        add_meta_box(
            'krom_title_translation_en',
            'English Title',
            'krom_title_translation_callback',
            $post_type,
            'normal',
            'high',
            array('lang' => 'en')
        );
        
        add_meta_box(
            'krom_content_translation_en',
            'English Content',
            'krom_content_translation_callback',
            $post_type,
            'normal',
            'high',
            array('lang' => 'en')
        );
        
        // Add Indonesian translation metaboxes
        add_meta_box(
            'krom_title_translation_id',
            'Indonesian Title',
            'krom_title_translation_callback',
            $post_type,
            'normal',
            'high',
            array('lang' => 'id')
        );
        
        add_meta_box(
            'krom_content_translation_id',
            'Indonesian Content',
            'krom_content_translation_callback',
            $post_type,
            'normal',
            'high',
            array('lang' => 'id')
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
    
    // For default language (Indonesian), use the post title if the translation is empty
    if ($lang === 'id' && empty($title_translation)) {
        $title_translation = get_the_title($post->ID);
    }
    
    // Add nonce for security
    wp_nonce_field('krom_translation_metabox', 'krom_translation_nonce_' . $lang);
    
    echo '<input type="text" name="krom_title_translation_' . esc_attr($lang) . '" placeholder="Enter title" style="width: 100%; padding: 8px; font-size: 1.2em;" value="' . esc_attr($title_translation) . '">';
}

/**
 * Content translation metabox callback with WYSIWYG editor
 */
function krom_content_translation_callback($post, $metabox) {
    $lang = $metabox['args']['lang'];
    $content_translation = get_post_meta($post->ID, '_krom_content_' . $lang, true);
    
    // For default language (Indonesian), use the post content if the translation is empty
    if ($lang === 'id' && empty($content_translation)) {
        $content_translation = $post->post_content;
    }
    
    // Initialize WordPress editor
    wp_editor(
        $content_translation,
        'krom_content_translation_' . $lang,
        array(
            'media_buttons' => true,
            'textarea_name' => 'krom_content_translation_' . $lang,
            'textarea_rows' => 20,
            'editor_css' => '',
            'tinymce' => array(
                'toolbar1' => 'formatselect,bold,italic,bullist,numlist,blockquote,alignleft,aligncenter,alignright,link,unlink,wp_more,spellchecker,fullscreen,wp_adv',
                'toolbar2' => 'strikethrough,hr,forecolor,pastetext,removeformat,charmap,outdent,indent,undo,redo,wp_help',
            ),
            'quicktags' => true,
            'drag_drop_upload' => true
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
    
    // Process English translations
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
    
    // Process Indonesian translations
    if (isset($_POST['krom_translation_nonce_id']) && wp_verify_nonce($_POST['krom_translation_nonce_id'], 'krom_translation_metabox')) {
        // Save Indonesian title
        if (isset($_POST['krom_title_translation_id'])) {
            update_post_meta(
                $post_id,
                '_krom_title_id',
                sanitize_text_field($_POST['krom_title_translation_id'])
            );
        }
        
        // Save Indonesian content
        if (isset($_POST['krom_content_translation_id'])) {
            update_post_meta(
                $post_id,
                '_krom_content_id',
                wp_kses_post($_POST['krom_content_translation_id'])
            );
        }
    }
}
add_action('save_post', 'krom_save_translation_meta');

/**
 * Filter post title based on language
 */
function krom_filter_post_title($title, $post_id = null) {
    // Don't filter in admin area
    if (is_admin()) {
        return $title;
    }
    
    // Only filter if we have a post ID
    if (!$post_id) {
        return $title;
    }
    
    // Get current language
    $current_lang = krom_get_current_language();
    
    $translated_title = get_post_meta($post_id, '_krom_title_' . $current_lang, true);
    if (!empty($translated_title)) {
        return $translated_title;
    }
    
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
    
    // Only filter for single posts/pages
    if (!is_singular()) {
        return $content;
    }
    
    // Get current post ID
    $post_id = get_the_ID();
    
    // Get current language
    $current_lang = krom_get_current_language();
    
    $translated_content = get_post_meta($post_id, '_krom_content_' . $current_lang, true);
    if (!empty($translated_content)) {
        return $translated_content;
    }
    
    return $content;
}
add_filter('the_content', 'krom_filter_post_content');

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
 * Add admin CSS for translation metaboxes
 */
function krom_add_translation_metabox_styles() {
    echo '<style>
        #krom_title_translation_en, #krom_content_translation_en {
            background-color: #f0f8ff;
            border: 1px solid #0073aa;
            margin-top: 15px;
        }
        #krom_title_translation_id, #krom_content_translation_id {
            background-color: #fff8f0;
            border: 1px solid #d54e21;
            margin-top: 15px;
        }
        #krom_title_translation_en h2, #krom_content_translation_en h2 {
            color: #0073aa;
            font-weight: bold;
        }
        #krom_title_translation_id h2, #krom_content_translation_id h2 {
            color: #d54e21;
            font-weight: bold;
        }
        .krom-translation-note {
            font-style: italic;
            font-size: 13px;
            margin-top: 5px;
        }
    </style>';
}
add_action('admin_head', 'krom_add_translation_metabox_styles');

// Remove functions that are no longer needed
remove_action('edit_form_after_title', 'krom_add_admin_language_switcher');
remove_filter('admin_url', 'krom_add_language_to_admin_url', 10);
remove_action('admin_head', 'krom_hide_default_title_in_english_mode');
remove_filter('use_block_editor_for_post_type', 'krom_hide_default_editor_in_english_mode', 10);
remove_filter('gutenberg_can_edit_post_type', 'krom_hide_default_editor_in_english_mode', 10);
remove_action('admin_footer', 'krom_debug_language_detection');