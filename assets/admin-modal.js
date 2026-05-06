// SEO Meta Manager Modal Logic
jQuery(document).ready(function($) {
    // Edit button
    $(document).on('click', '.smm-edit-slug', function(e) {
        e.preventDefault();
        var postId = $(this).data('id');
        var title = $(this).closest('tr').find('input[name="meta_title"]').val();
        var desc = $(this).closest('tr').find('textarea[name="meta_desc"]').val();
        $('#smm-modal-postid').val(postId);
        $('#smm-modal-title').val(title);
        $('#smm-modal-desc').val(desc);
        $('#smm-edit-modal').show();
    });
    // Close modal
    $(document).on('click', '.smm-modal-close', function() {
        $('#smm-edit-modal').hide();
    });
    // Clear button
    $(document).on('click', '.smm-clear', function(e) {
        e.preventDefault();
        var $row = $(this).closest('tr');
        $row.find('input[name="meta_title"]').val('');
        $row.find('textarea[name="meta_desc"]').val('');
    });
});
