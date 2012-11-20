jQuery(document).ready(function($) {

    $('#isc_add_metafields').click(function(){
        $('#isc_loading_img').show();
        var data = {
            action: 'add_meta_fields',
            whatever: 1234
        };

        // since 2.8 ajaxurl is always defined in the admin header and points to admin-ajax.php
        $.post(ajaxurl, data, function(response) {
            $('#add_metafields_result').html( response );
            $('#isc_loading_img').hide();
        });     
    });
	
});