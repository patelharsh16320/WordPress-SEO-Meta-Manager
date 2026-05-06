<?php
if (!defined('ABSPATH')) exit;

add_action('admin_menu', function () {
    add_menu_page(
        'SEO Meta Manager',
        'SEO Meta',
        'manage_options',
        'seo-meta-manager',
        'smm_admin_page',
        'dashicons-chart-area',
        20
    );
});

function smm_admin_page() {

    $selected_type = isset($_GET['post_type_filter']) ? sanitize_text_field($_GET['post_type_filter']) : 'page';

    // PAGINATION SETTINGS
    $per_page = 10;
    $paged = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;

    $args = [
        'post_type'      => $selected_type,
        'posts_per_page' => $per_page,
        'paged'          => $paged,
        'post_status'    => 'publish'
    ];

    $query = new WP_Query($args);
    $posts = $query->posts;

    $total_pages = $query->max_num_pages;

    // COUNT
    $total_pages_count = wp_count_posts('page')->publish ?? 0;
    $total_posts_count = wp_count_posts('post')->publish ?? 0;

?>

<div class="smm-wrapper">

    <div class="smm-header">
        <h1>SEO Meta Manager</h1>

        <div class="smm-controls">
            <span class="smm-label">
                Showing: <?php echo ucfirst($selected_type); ?>
            </span>

            <form method="get">
                <input type="hidden" name="page" value="seo-meta-manager">

                <select name="post_type_filter" onchange="this.form.submit()">
                    <option value="page" <?php selected($selected_type, 'page'); ?>>Pages (<?php echo $total_pages_count; ?>)</option>
                    <option value="post" <?php selected($selected_type, 'post'); ?>>Posts (<?php echo $total_posts_count; ?>)</option>
                </select>
            </form>

            <button id="smm-theme-toggle">🌙</button>
        </div>
    </div>

    <div class="smm-card">
        <table class="smm-table">
            <thead>
                <tr>
                    <th>Index</th>
                    <th>Title</th>
                    <th>Meta Title</th>
                    <th>Meta Description</th>
                    <th>Action</th>
                </tr>
            </thead>

            <tbody>

            <?php 
            $index = ($paged - 1) * $per_page + 1;

            foreach ($posts as $post): 

                $meta_title = get_post_meta($post->ID, '_yoast_wpseo_title', true);
                $meta_desc  = get_post_meta($post->ID, '_yoast_wpseo_metadesc', true);

                if (!$meta_title) $meta_title = get_the_title($post->ID);
            ?>

            <tr>
                <form method="post">
                    <?php wp_nonce_field('smm_action', 'smm_nonce'); ?>

                    <td><?php echo $index++; ?></td>

                    <td>
                        <a href="<?php echo esc_url(get_permalink($post->ID)); ?>" target="_blank">
                            <?php echo esc_html($post->post_title); ?>
                        </a>
                    </td>

                    <td>
                        <input type="text" name="meta_title" value="<?php echo esc_attr($meta_title); ?>">
                    </td>

                    <td>
                        <textarea name="meta_desc"><?php echo esc_textarea($meta_desc); ?></textarea>
                    </td>

                    <td>
                        <input type="hidden" name="post_id" value="<?php echo $post->ID; ?>">

                        <button name="update_meta" class="smm-btn primary">Update</button>
                        <button name="clear_meta" class="smm-btn">Clear</button>
                    </td>
                </form>
            </tr>

            <?php endforeach; ?>

            </tbody>
        </table>

        <!-- PAGINATION -->
        <div class="smm-pagination">
            <?php
            $base_url = admin_url('admin.php?page=seo-meta-manager&post_type_filter=' . $selected_type);

            if ($paged > 1) {
                echo '<a href="'.$base_url.'&paged='.($paged - 1).'" class="smm-page-btn">« Prev</a>';
            }

            for ($i = 1; $i <= $total_pages; $i++) {
                $active = ($i == $paged) ? 'active' : '';
                echo '<a href="'.$base_url.'&paged='.$i.'" class="smm-page-btn '.$active.'">'.$i.'</a>';
            }

            if ($paged < $total_pages) {
                echo '<a href="'.$base_url.'&paged='.($paged + 1).'" class="smm-page-btn">Next »</a>';
            }
            ?>
        </div>

    </div>

</div>

<script>
const wrapper = document.querySelector('.smm-wrapper');

if(localStorage.getItem('smm-theme') === 'dark'){
    wrapper.classList.add('smm-dark');
}

document.getElementById('smm-theme-toggle').onclick = function(){
    wrapper.classList.toggle('smm-dark');
    localStorage.setItem('smm-theme', wrapper.classList.contains('smm-dark') ? 'dark' : 'light');
}
</script>

<?php
}