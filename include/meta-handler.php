<?php
if (!defined('ABSPATH')) exit;

add_action('admin_init', function () {

    if (!isset($_POST['post_id'])) return;
    if (!wp_verify_nonce($_POST['smm_nonce'], 'smm_action')) return;

    $post_id = intval($_POST['post_id']);


    // BULK META UPDATE
    if (isset($_POST['smm_bulk_update']) && !empty($_POST['post_ids'])) {
        foreach ($_POST['post_ids'] as $pid) {
            $pid = intval($pid);
            if (isset($_POST['meta_title_bulk'][$pid])) {
                update_post_meta($pid, '_yoast_wpseo_title', sanitize_text_field($_POST['meta_title_bulk'][$pid]));
            }
            if (isset($_POST['meta_desc_bulk'][$pid])) {
                update_post_meta($pid, '_yoast_wpseo_metadesc', sanitize_textarea_field($_POST['meta_desc_bulk'][$pid]));
            }
        }
    }

    // META UPDATE (single row)
    if (isset($_POST['update_meta'])) {
        update_post_meta($post_id, '_yoast_wpseo_title', sanitize_text_field($_POST['meta_title']));
        update_post_meta($post_id, '_yoast_wpseo_metadesc', sanitize_textarea_field($_POST['meta_desc']));
    }

    // TITLE & SLUG UPDATE (from modal)
    if (isset($_POST['update_title_slug'])) {
        $title = sanitize_text_field($_POST['new_title']);
        $slug  = sanitize_title($_POST['new_slug']);
        if (empty($slug)) {
            $slug = sanitize_title($title); // auto generate
        }
        wp_update_post([
            'ID' => $post_id,
            'post_title' => $title,
            'post_name' => $slug
        ]);
    }
});
