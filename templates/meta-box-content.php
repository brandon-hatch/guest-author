<?php global $post;

$translations = get_translations_for_domain('default');
$author_type = get_post_meta($post->ID, 'BS_author_type', true);
if ( empty($author_type) ) {
    $author_type = 'BS_author_is_user';
}
?>
<div class="BS_guest_author_container <?php if ( BS_is_gutenberg() ): echo "gutenberg-style"; endif ?>">
    <div class="tabs">
        <input type="radio" class="tab-control" name="BS_author_type" id="author_is_user" value="BS_author_is_user" <?php if ($author_type === 'BS_author_is_user') echo 'checked'; ?>>
        <label class="tab-control-controller" for="author_is_user"><?php echo esc_html($translations->translate('User')); ?></label>

        <input type="radio" class="tab-control" name="BS_author_type" id="author_is_guest" value="BS_author_is_guest" <?php if ($author_type === 'BS_author_is_guest') echo 'checked'; ?>>
        <label class="tab-control-controller" for="author_is_guest"><?php echo esc_attr_e('Guest', 'guest-author'); ?></label>

        <div class="content">
            <div id="author_is_user_tab" class="tab">
                    <?php
                    if (BS_is_gutenberg ()) {
                      ?>

                        <p><?php esc_attr_e('Go to', 'guest-author') ?> <b>"<?php echo esc_html($translations->translate('Status & Visibility')); ?>"</b></p>

                   <?php } else {
                        if (function_exists( 'post_author_meta_box' )) {
                            post_author_meta_box($post);
                        }
                    }
                      ?>
            </div>

            <div id="author_is_guest_tab" class="tab">
                    <?php include_once "_guest-author-form.php"; ?>
            </div>
        </div>
    </div>
    <style>
        .BS_guest_author_container #author_is_user:checked ~ .content #author_is_user_tab,
        .BS_guest_author_container #author_is_guest:checked ~ .content #author_is_guest_tab {
            display: block;
        }
    </style>
</div>
