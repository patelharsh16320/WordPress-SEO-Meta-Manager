<?php
/**
 * Plugin Name: SEO Meta Manager 
 * Description: Manage and update Yoast SEO meta title and description for all posts, pages, and custom post types from a single dashboard.
 * Version: 1.0.0
 * Author: Harsh Patel
 * Author URI: https://github.com/patelharsh16320/WordPress-SEO-Meta-Manager
 */

if (!defined('ABSPATH')) exit;

// LOAD FILES
require_once plugin_dir_path(__FILE__) . 'include/admin-page.php';
require_once plugin_dir_path(__FILE__) . 'include/meta-handler.php';

// LOAD CSS
add_action('admin_enqueue_scripts', function () {
    wp_enqueue_style(
        'smm-admin-css',
        plugin_dir_url(__FILE__) . 'assets/admin.css',
        [],
        '1.0.0'
    );
});