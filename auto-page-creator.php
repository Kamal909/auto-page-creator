<?php
/**
 * Plugin Name: Auto Page Creator
 * Description: Automatically creates default pages via admin panel with control on quantity.
 * Version: 1.0
 * Author: kamal15
 * License: GPL2
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: auto-page-creator
 */

if (!defined('ABSPATH')) {
    exit;
}

// Add submenu under Pages
add_action('admin_menu', 'apcbykml_add_admin_menu');
function apcbykml_add_admin_menu() {
    add_submenu_page(
        'edit.php?post_type=page',
        __('Auto Page Creator', 'auto-page-creator'),
        __('Auto Page Creator', 'auto-page-creator'),
        'manage_options',
        'apcbykml_auto_page_creator',
        'apcbykml_render_admin_page'
    );
}

// Enqueue Styles and Scripts
function apcbykml_enqueue_styles() {
   // Enqueue Admin Style with proper version
wp_enqueue_style(
    'apcbykml-admin-style',
    plugin_dir_url(__FILE__) . 'assets/css/admin-style.css',
    array(),
    '1.0'
);

// Enqueue Admin Script with proper version
wp_enqueue_script(
    'apcbykml-admin-script',
    plugin_dir_url(__FILE__) . 'assets/js/admin-script.js',
    array('jquery'),
    '1.0',
    true
);

}

add_action('admin_enqueue_scripts', 'apcbykml_enqueue_styles');

// Render admin page
function apcbykml_render_admin_page() {
    ?>
    <div class="apck-wrap">
        <h1><?php esc_html_e('Auto Page Creator', 'auto-page-creator'); ?></h1>
        <form method="post">
            <?php wp_nonce_field('apcbykml_create_pages_action', 'apcbykml_create_pages_nonce'); ?>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row"><?php esc_html_e('Number of Pages to Create', 'auto-page-creator'); ?></th>
                    <td>
                        <input type="number" name="apcbykml_page_count" value="10" min="1" max="100" />
                        <p class="description"><?php esc_html_e('Maximum 100 pages allowed.', 'auto-page-creator'); ?></p>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row"><?php esc_html_e('Custom Titles (comma-separated)', 'auto-page-creator'); ?></th>
                    <td>
                        <input type="text" name="apcbykml_custom_titles" placeholder="Enter custom titles" />
                    </td>
                </tr>
                  <tr valign="top">
                    <th scope="row"><?php esc_html_e('SEO Metadata', 'auto-page-creator'); ?></th>
                    <td>
                        <label for="apcbykml_seo_title"><?php esc_html_e('SEO Title', 'auto-page-creator'); ?></label>
                        <input type="text" name="apcbykml_seo_title" />
                        <label for="apcbykml_seo_desc"><?php esc_html_e('SEO Description', 'auto-page-creator'); ?></label>
                        <input type="text" name="apcbykml_seo_desc" />
                        <label for="apcbykml_seo_keywords"><?php esc_html_e('SEO Keywords', 'auto-page-creator'); ?></label>
                        <input type="text" name="apcbykml_seo_keywords" />
                    </td>
                </tr>
                    <tr valign="top">
                     <th scope="row"><label for="apcbykml_page_status"><?php esc_html_e('Page Status', 'auto-page-creator'); ?></label></th>
                    <td>
                        <select name="apcbykml_page_status">
                             <option value="draft"><?php esc_html_e('Draft', 'auto-page-creator'); ?></option>
                            <option value="publish"><?php esc_html_e('Publish', 'auto-page-creator'); ?></option>
                        </select>
                    </td>
                </tr>
            </table>
            <?php submit_button(__('Create Pages', 'auto-page-creator')); ?>
        </form>
    </div>
    <?php

    if (isset($_POST['apcbykml_page_count']) && check_admin_referer('apcbykml_create_pages_action', 'apcbykml_create_pages_nonce')) {
        $page_count = intval($_POST['apcbykml_page_count']);
        if ($page_count > 0 && $page_count <= 100) {
            apcbykml_create_pages($page_count);
        } else {
            echo '<div class="notice notice-error"><p>' . esc_html_e('Invalid number of pages.', 'auto-page-creator') . '</p></div>';
        }
    }
}

// Create pages function
function apcbykml_create_pages($count) {


    // Retrieve custom titles if provided
    if (isset($_POST['apcbykml_create_pages_nonce']) && check_admin_referer('apcbykml_create_pages_action', 'apcbykml_create_pages_nonce')) { 
        if (isset($_POST['apcbykml_custom_titles'])) {
        $base_titles = explode(',', sanitize_text_field(wp_unslash($_POST['apcbykml_custom_titles'])));
         }

    // SEO Data
    $seo_title = wp_unslash(isset($_POST['apcbykml_seo_title'])) ? sanitize_text_field(wp_unslash($_POST['apcbykml_seo_title'])) : '';
    $seo_desc = wp_unslash(isset( $_POST['apcbykml_seo_desc'])) ? sanitize_text_field(wp_unslash($_POST['apcbykml_seo_desc'])) : '';
    $seo_keywords = wp_unslash(isset($_POST['apcbykml_seo_keywords'])) ? sanitize_text_field(wp_unslash($_POST['apcbykml_seo_keywords'])) : '';
    $base_titles = wp_unslash(isset($_POST['base_titles'])) ? sanitize_text_field(wp_unslash($_POST['base_titles'])) : '';
    $apcbykml_page_status = wp_unslash(isset($_POST['apcbykml_page_status'])) ? sanitize_text_field(wp_unslash($_POST['apcbykml_page_status'])) : '';
    $created = 0;
    for ($i = 0; $i < $count; $i++) {
         // translators: %d is the number of the page with no title
        $title =isset($base_titles[$i]) ? $base_titles[$i] : 'No title';
        

            // Create Page
            $post_id = wp_insert_post([
                'post_title'   => wp_strip_all_tags($title),
                'post_content' => '',
                'post_status'  => $apcbykml_page_status,
                'post_type'    => 'page'
            ]);

            // Add SEO Meta Data
            if ($post_id && !empty($seo_title)) {
                update_post_meta($post_id, '_aioseo_title', $seo_title);
                update_post_meta($post_id, '_aioseo_description', $seo_desc);
                update_post_meta($post_id, '_aioseo_keywords', $seo_keywords);
            }

            $created++;
       
    }
            /* translators: %s is the title of the page that was created */
             echo '<div class="notice notice-success"><p>' . esc_html_e('Page(s) created.', 'auto-page-creator').'</p></div>';

        

    }


    }
    