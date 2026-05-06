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

// AJAX handler for live search
add_action('wp_ajax_smm_ajax_search', function () {
    if (!current_user_can('manage_options')) wp_send_json_error();

    $selected_type = $_POST['post_type_filter'] ?? 'page';
    $search = $_POST['smm_search'] ?? '';
    $order = $_POST['order'] ?? 'ASC';
    $per_page = 10;
    $paged = 1;

    $args = [
        'post_type' => $selected_type,
        'posts_per_page' => $per_page,
        'paged' => $paged,
        'post_status' => 'publish',
        'orderby' => 'title',
        'order' => $order
    ];
    if (!empty($search)) {
        $args['s'] = $search;
    }
    $query = new WP_Query($args);
    $posts = $query->posts;

    ob_start();
    if (empty($posts)) {
        echo '<tr><td colspan="5">No data found</td></tr>';
    } else {
        $i = 1;
        foreach ($posts as $post) {
            ?>
            <tr data-id="<?php echo $post->ID; ?>">
                <form method="post">
                    <?php wp_nonce_field('smm_action', 'smm_nonce'); ?>
                    <td><?php echo $i++; ?></td>
                    <td>
                        <a href="<?php echo get_permalink($post->ID); ?>" target="_blank">
                            <?php echo esc_html($post->post_title); ?>
                        </a>
                    </td>
                    <td>
                        <input type="text" name="meta_title"
                            value="<?php echo esc_attr(get_post_meta($post->ID, '_yoast_wpseo_title', true)); ?>">
                    </td>
                    <td>
                        <textarea name="meta_desc"><?php echo esc_textarea(get_post_meta($post->ID, '_yoast_wpseo_metadesc', true)); ?></textarea>
                    </td>
                    <td>
                        <input type="hidden" name="post_id" value="<?php echo $post->ID; ?>">
                        <button type="submit" name="update_meta" class="smm-btn primary">Update</button>
                        <button type="button" class="smm-btn smm-clear">Clear</button>
                        <button type="button"
                            class="smm-btn smm-edit-btn"
                            data-id="<?php echo $post->ID; ?>"
                            data-title="<?php echo esc_attr($post->post_title); ?>"
                            data-slug="<?php echo esc_attr($post->post_name); ?>">
                            Slug
                        </button>
                    </td>
                </form>
            </tr>
            <?php
        }
    }
    $html = ob_get_clean();
    wp_send_json_success(['html' => $html]);
});

function smm_admin_page()
{
    $selected_type = $_GET['post_type_filter'] ?? 'page';
    $search = $_GET['smm_search'] ?? '';
    $order = $_GET['order'] ?? 'ASC';
    $per_page = 10;
    $paged = isset($_GET['paged']) ? intval($_GET['paged']) : 1;

    // ================= SINGLE UPDATE =================
    if (isset($_POST['update_meta']) && wp_verify_nonce($_POST['smm_nonce'], 'smm_action')) {
        $id = intval($_POST['post_id']);
        update_post_meta($id, '_yoast_wpseo_title', sanitize_text_field($_POST['meta_title']));
        update_post_meta($id, '_yoast_wpseo_metadesc', sanitize_textarea_field($_POST['meta_desc']));
    }
    // ================= BULK UPDATE =================
    if (isset($_POST['smm_bulk_update']) && wp_verify_nonce($_POST['smm_nonce'], 'smm_action')) {
        if (!empty($_POST['post_ids'])) {
            foreach ($_POST['post_ids'] as $id) {
                $id = intval($id);
                $title = $_POST['meta_title_bulk'][$id] ?? '';
                $desc  = $_POST['meta_desc_bulk'][$id] ?? '';
                update_post_meta($id, '_yoast_wpseo_title', sanitize_text_field($title));
                update_post_meta($id, '_yoast_wpseo_metadesc', sanitize_textarea_field($desc));
            }
        }
    }
    // ================= TITLE + SLUG UPDATE =================
    if (isset($_POST['update_title_slug']) && wp_verify_nonce($_POST['smm_nonce'], 'smm_action')) {
        $id    = intval($_POST['post_id']);
        $title = sanitize_text_field($_POST['new_title']);
        $slug  = sanitize_title($_POST['new_slug']);
        if (empty($slug)) {
            $slug = sanitize_title($title);
        }
        wp_update_post([
            'ID'         => $id,
            'post_title' => $title,
            'post_name'  => $slug
        ]);
    }
    // ================= QUERY =================
    $args = [
        'post_type' => $selected_type,
        'posts_per_page' => $per_page,
        'paged' => $paged,
        'post_status' => 'publish',
        'orderby' => 'title',
        'order' => $order
    ];
    if (!empty($search)) {
        $args['s'] = $search;
    }
    $query = new WP_Query($args);
    $posts = $query->posts;
    $base = admin_url("admin.php?page=seo-meta-manager&post_type_filter=$selected_type&smm_search=$search&order=$order");
?>
<div class="smm-wrapper">
    <div class="smm-header">
        <h1>SEO Meta Manager</h1>
        <div class="smm-controls">
            <!-- POST TYPE -->
            <form method="get" id="smm-type-form">
                <input type="hidden" name="page" value="seo-meta-manager">
                <select name="post_type_filter" onchange="this.form.submit()">
                    <option value="page" <?php selected($selected_type, 'page'); ?>>Pages</option>
                    <option value="post" <?php selected($selected_type, 'post'); ?>>Posts</option>
                </select>
            </form>
            <!-- SEARCH -->
            <input type="text" id="smm-search" placeholder="Search..." value="<?php echo esc_attr($search); ?>" style="min-width:180px;">
        </div>
    </div>
    <div class="smm-card">
        <table class="smm-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Title</th>
                    <th>Meta Title</th>
                    <th>Meta Description</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody id="smm-table-body">
                <?php if (empty($posts)): ?>
                    <tr><td colspan="5">No data found</td></tr>
                <?php endif; ?>
                <?php $i = ($paged - 1) * $per_page + 1; ?>
                <?php foreach ($posts as $post): ?>
                    <tr data-id="<?php echo $post->ID; ?>">
                        <form method="post">
                            <?php wp_nonce_field('smm_action', 'smm_nonce'); ?>
                            <td><?php echo $i++; ?></td>
                            <td>
                                <a href="<?php echo get_permalink($post->ID); ?>" target="_blank">
                                    <?php echo esc_html($post->post_title); ?>
                                </a>
                            </td>
                            <td>
                                <input type="text" name="meta_title"
                                    value="<?php echo esc_attr(get_post_meta($post->ID, '_yoast_wpseo_title', true)); ?>">
                            </td>
                            <td>
                                <textarea name="meta_desc"><?php echo esc_textarea(get_post_meta($post->ID, '_yoast_wpseo_metadesc', true)); ?></textarea>
                            </td>
                            <td>
                                <input type="hidden" name="post_id" value="<?php echo $post->ID; ?>">
                                <button type="submit" name="update_meta" class="smm-btn primary">Update</button>
                                <button type="button" class="smm-btn smm-clear">Clear</button>
                                <button type="button"
                                    class="smm-btn smm-edit-btn"
                                    data-id="<?php echo $post->ID; ?>"
                                    data-title="<?php echo esc_attr($post->post_title); ?>"
                                    data-slug="<?php echo esc_attr($post->post_name); ?>">
                                    Slug
                                </button>
                            </td>
                        </form>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <!-- BULK UPDATE -->
        <form id="smm-bulk-form" method="post">
            <?php wp_nonce_field('smm_action', 'smm_nonce'); ?>
            <input type="hidden" name="smm_bulk_update" value="1">
            <?php foreach ($posts as $post): ?>
                <input type="hidden" name="post_ids[]" value="<?php echo $post->ID; ?>">
                <input type="hidden" name="meta_title_bulk[<?php echo $post->ID; ?>]" id="bulk_title_<?php echo $post->ID; ?>">
                <input type="hidden" name="meta_desc_bulk[<?php echo $post->ID; ?>]" id="bulk_desc_<?php echo $post->ID; ?>">
            <?php endforeach; ?>
            <button class="smm-btn primary" style="margin:15px;">Update All</button>
        </form>
        <!-- PAGINATION -->
        <div class="smm-pagination">
            <?php if ($paged > 1): ?>
                <a class="smm-page-btn" href="<?php echo $base.'&paged='.($paged-1); ?>">Prev</a>
            <?php endif; ?>
            <?php for ($p = 1; $p <= $query->max_num_pages; $p++): ?>
                <a class="smm-page-btn <?php echo ($p==$paged)?'active':''; ?>"
                    href="<?php echo $base.'&paged='.$p; ?>">
                    <?php echo $p; ?>
                </a>
            <?php endfor; ?>
            <?php if ($paged < $query->max_num_pages): ?>
                <a class="smm-page-btn" href="<?php echo $base.'&paged='.($paged+1); ?>">Next</a>
            <?php endif; ?>
        </div>
    </div>
</div>
<!-- MODAL -->
<div id="smm-modal" style="display:none;position:fixed;top:0;left:0;width:100vw;height:100vh;background:rgba(0,0,0,0.6);justify-content:center;align-items:center;z-index:9999;">
    <div style="background:#fff;padding:20px;border-radius:10px;width:320px;">
        <h3>Edit Title & Slug</h3>
        <form method="post">
            <?php wp_nonce_field('smm_action', 'smm_nonce'); ?>
            <input type="hidden" name="post_id" id="modal-id">
            <label>Title</label>
            <input type="text" name="new_title" id="modal-title" style="width:100%;margin-bottom:10px;">
            <label>Slug</label>
            <input type="text" name="new_slug" id="modal-slug" style="width:100%;margin-bottom:10px;">
            <button type="submit" name="update_title_slug" class="smm-btn primary">Save</button>
            <button type="button" id="modal-close" class="smm-btn">Cancel</button>
        </form>
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function(){
    // AJAX SEARCH
    let searchInput = document.getElementById('smm-search');
    let tableBody = document.getElementById('smm-table-body');
    let typingTimer;
    let lastValue = searchInput.value;
    searchInput.addEventListener('input', function(){
        clearTimeout(typingTimer);
        typingTimer = setTimeout(function(){
            let val = searchInput.value;
            if(val === lastValue) return;
            lastValue = val;
            let data = new FormData();
            data.append('action', 'smm_ajax_search');
            data.append('smm_search', val);
            data.append('post_type_filter', document.querySelector('select[name=\"post_type_filter\"]').value);
            fetch(ajaxurl, {
                method: 'POST',
                credentials: 'same-origin',
                body: data
            })
            .then(r=>r.json())
            .then(res=>{
                if(res.success && res.data && res.data.html){
                    tableBody.innerHTML = res.data.html;
                }
            });
        }, 350);
    });

    // CLEAR
    document.body.addEventListener('click', function(e){
        if(e.target.classList.contains('smm-clear')){
            e.preventDefault();
            let row = e.target.closest('tr');
            row.querySelector('[name=\"meta_title\"]').value = '';
            row.querySelector('[name=\"meta_desc\"]').value = '';
        }
    });

    // BULK FIX
    document.getElementById('smm-bulk-form').addEventListener('submit', function(){
        document.querySelectorAll('.smm-table tbody tr').forEach(row=>{
            let id = row.dataset.id;
            let title = row.querySelector('[name=\"meta_title\"]').value;
            let desc  = row.querySelector('[name=\"meta_desc\"]').value;
            document.getElementById('bulk_title_'+id).value = title;
            document.getElementById('bulk_desc_'+id).value = desc;
        });
    });

    // MODAL LOGIC (event delegation)
    const modal = document.getElementById('smm-modal');
    document.body.addEventListener('click', function(e){
        // OPEN MODAL
        if(e.target.classList.contains('smm-edit-btn')){
            document.getElementById('modal-id').value = e.target.dataset.id;
            document.getElementById('modal-title').value = e.target.dataset.title;
            document.getElementById('modal-slug').value = e.target.dataset.slug;
            modal.style.display = 'flex';
        }
        // CLOSE MODAL
        if(e.target.id === 'modal-close'){
            modal.style.display = 'none';
        }
    });
    // CLOSE ON OUTSIDE CLICK
    window.addEventListener('click', function(e){
        if(e.target === modal){
            modal.style.display = 'none';
        }
    });
});
</script>
<?php } ?>