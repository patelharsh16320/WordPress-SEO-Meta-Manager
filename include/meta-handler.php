<?php
if (!defined('ABSPATH')) exit;

add_action('admin_init', function () {

    if (!current_user_can('manage_options')) return;
    if (!isset($_POST['post_id'])) return;
    if (!wp_verify_nonce($_POST['smm_nonce'], 'smm_action')) return;

    $post_id = intval($_POST['post_id']);

    if (isset($_POST['update_meta'])) {
        update_post_meta($post_id, '_yoast_wpseo_title', sanitize_text_field($_POST['meta_title']));
        update_post_meta($post_id, '_yoast_wpseo_metadesc', sanitize_textarea_field($_POST['meta_desc']));
    }

    if (isset($_POST['clear_meta'])) {
        delete_post_meta($post_id, '_yoast_wpseo_title');
        delete_post_meta($post_id, '_yoast_wpseo_metadesc');
    }
});