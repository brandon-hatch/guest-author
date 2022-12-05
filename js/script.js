;(function($) {


    $(document).ready( function($) {
        $('#BS_guest_author_image_media_manager_edit_button').click(handleEditButtonClick);
        $('#BS_guest_author_image_media_manager_remove_button').click(handleRemoveButtonClick);
    });

    function handleEditButtonClick (e) {
        e.preventDefault();
        var media_manager = $("#BS_guest_author_image_media_manager");

        console.log(media_manager.hasClass('disabled'));

        if (media_manager.hasClass('disabled'))
            return false;

        var image_frame;
        if(image_frame) {
            image_frame.open();
        }
        // Define image_frame as wp.media object
        image_frame = wp.media({
            title: 'Select Media',
            multiple : false,
            library : {
                type : 'image'
            }
        });
        image_frame.on('close',function() {
            // On close, get selections and save to the hidden input
            // plus other AJAX stuff to refresh the image preview
            var selection =  image_frame.state().get('selection');
            var gallery_ids = [];
            var my_index = 0;
            selection.each(function(attachment) {
                gallery_ids[my_index] = attachment['id'];
                my_index++;
            });
            var ids = gallery_ids.join(",");
            $('input#BS_guest_author_image_id').val(ids);
            Refresh_Image(ids);
        });

        image_frame.on('open',function() {
            // On open, get the id from the hidden input
            // and select the appropriate images in the media manager
            var selection =  image_frame.state().get('selection');
            ids = $('input#BS_guest_author_image_id').val().split(',');
            ids.forEach(function(id) {
                var attachment = wp.media.attachment(id);
                attachment.fetch();
                selection.add( attachment ? [ attachment ] : [] );
            });

        });

        image_frame.open();
    }

    function handleRemoveButtonClick (e) {
        var media_manager = $("#BS_guest_author_image_media_manager");

        if (media_manager.hasClass('disabled'))
            return false;

        var imageContainer = $('#BS_guest_author_image_media_manager_image');
        var defaultImageUrl = imageContainer.attr('data-default-image');
        var defaultImageElement = $("<img src='"+ defaultImageUrl +"' id='BS-guest-author-preview-image' />");
        imageContainer.html( defaultImageElement );

        // $('#BS_guest_author_image_media_manager').addClass('BS_default-image');
        $('input#BS_guest_author_image_id').val('');
        $("#BS_guest_author_image_media_manager_remove_button").remove();
    }

    // Ajax request to refresh the image preview
    function Refresh_Image(the_id){
        var data = {
            action: 'BS_get_image',
            id: the_id
        };

        $.get(ajaxurl, data, function(response) {
            if(response.success === true) {
                if (response.data.image.startsWith('<img')){
                    var removeButton = $("<p id=\"BS_guest_author_image_media_manager_remove_button\"><span class=\"dashicons dashicons-no-alt\"></span><span class=\"screen-reader-text\">Remove</span></p>");
                    removeButton.click(handleRemoveButtonClick);
                    $("#BS_guest_author_image_media_manager_edit_panel").append(removeButton);
                    $('#BS_guest_author_image_media_manager_image').html(response.data.image)

                }
            }
        });
    }


})(jQuery);