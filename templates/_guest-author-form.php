<?php
$name = get_post_meta( $post->ID, 'BS_guest_author_name', true );
$url = get_post_meta( $post->ID, 'BS_guest_author_url', true );
$description = get_post_meta( $post->ID, 'BS_guest_author_description', true );
$image_id = get_post_meta( $post->ID, 'BS_guest_author_image_id', true );
?>
<div class="BS_guest_author_form_container">
    <div class="BS_guest_author_main form-field">
        <p class="label">
            <label for="BS_guest_author_name"><?php esc_attr_e('Author Name', 'guest-author'); ?></label>
        </p>
        <input type="text" placeholder="Enter author name" id="BS_guest_author_name" name="BS_guest_author_name" value="<?php echo esc_attr( $name ); ?>">
    </div>
    <div id="BS_guest_author_the_rest">
        <div id="BS_guest_author_info-container">
            <div class="form-field">
                <p class="label">
                    <label for="BS_guest_author_url"><?php esc_attr_e('Author URL', 'guest-author'); ?></label>
                </p>
                <input type="text" id="BS_guest_author_url" name="BS_guest_author_url" value="<?php echo esc_attr( $url ); ?>">
            </div>
            <div class="form-field">
                <p class="label">
                    <label for="BS_guest_author_description"><?php esc_attr_e('Author Description', 'guest-author'); ?></label>
                </p>
                <textarea type="text" id="BS_guest_author_description" name="BS_guest_author_description"><?php echo esc_textarea( $description ); ?></textarea>
            </div>
        </div>
        <div id="BS_guest_author_image-container">
            <div class="form-field">
                <p class="label">
                    <label for="BS_guest_author_image_url"><?php esc_attr_e('Author Image', 'guest-author'); ?></label>
                </p>
                <div id="BS_guest_author_image_media_manager">
                    <div id="BS_guest_author_image_media_manager_edit_panel">
                        <p id="BS_guest_author_image_media_manager_edit_button"><span class="dashicons dashicons-edit"></span><span class="screen-reader-text"><?php esc_attr_e('Edit', 'guest-author'); ?></span></p>
                        <?php if( intval( $image_id ) > 0 ) { ?>
                            <p id="BS_guest_author_image_media_manager_remove_button"><span class="dashicons dashicons-no-alt"></span><span class="screen-reader-text"><?php esc_attr_e('Remove', 'guest-author'); ?></span></p>
                        <?php } ?>
                    </div>
                    <div id="BS_guest_author_image_media_manager_image" data-default-image="<?php echo esc_url(plugins_url( '../img/default.jpg' , __FILE__ )); ?>">
                        <?php
                        if( intval( $image_id ) > 0 ) {
                            // Change with the image size you want to use
                            echo wp_get_attachment_image( $image_id, 'medium', false, array( 'id' => 'BS-guest-author-preview-image' ) );
                        } else {
                            ?>
                            <img src="<?php echo esc_url(plugins_url( '../img/default.jpg' , __FILE__ )); ?>" id="BS-guest-author-preview-image">
                            <?php
                        }
                        ?>
                    </div>
                </div>
            </div>
            <input type="hidden" name="BS_guest_author_image_id" id="BS_guest_author_image_id" value="<?php echo esc_attr( $image_id ); ?>" class="regular-text" />
        </div>
        <div class="clear"></div>
    </div>
    <script>
        var BS_guest_author_name = document.getElementById('BS_guest_author_name');
        var BS_guest_author_the_rest = document.getElementById('BS_guest_author_the_rest');

        if (BS_guest_author_name.value.length === 0) {
            addClass(BS_guest_author_the_rest, 'disabled');
        }

        BS_guest_author_name.addEventListener('keyup', function ( e ) {
            if (e.target.value.length > 0)
                removeClass(BS_guest_author_the_rest, 'disabled');
            else
                addClass(BS_guest_author_the_rest, 'disabled');
        });

        function addClass(el, className) { if ( el.className.indexOf(className) < 0 ) el.className += ' ' + className; }
        function removeClass(el, className) { el.className = el.className.replace(className, '')
        }
    </script>
</div>
