jQuery(document).ready(function($) {
    isc_thumbnail_input_checkstate();
    isc_caption_checkstate();
    $('#source-on-image').click(function(){isc_caption_checkstate()});
    $('#use_authorname').click(function(){
        if ('disabled' == $('#byauthor').attr('disabled')) {
            $('#byauthor').removeAttr('disabled');
        } else {
            $('#byauthor').attr('disabled', 'disabled');
        }
    });
	$('#use-thumbnail').click(function(){
        if ('disabled' == $('#thumbnail-size-select').attr('disabled')) {
            $('#thumbnail-size-select').removeAttr('disabled');
        } else {
            $('#thumbnail-size-select').attr('disabled', 'disabled');
        }
    });
    $('#thumbnail-size-select').change(function(){isc_thumbnail_input_checkstate()});

    // debug function – load image-post relations
    // call post-image relation (meta fields saved for posts)
    $('#isc-list-post-image-relation').click(function(){
        $.ajax({
            type: 'POST',
            url: ajaxurl,
            data: {action: 'isc-post-image-relations'},
            success:function(data, textStatus, XMLHttpRequest){
                // display return messages
                $('#isc-post-image-relations').html(data);
            },

            error: function(MLHttpRequest, textStatus, errorThrown){
                $('#isc-post-image-relations').html(errorThrown);
            }

        });
    });
    // call image-post relation (meta fields saved for posts)
    $('#isc-list-image-post-relation').click(function(){
        $.ajax({
            type: 'POST',
            url: ajaxurl,
            data: {action: 'isc-image-post-relations'},
            success:function(data, textStatus, XMLHttpRequest){
                // display return messages
                $('#isc-image-post-relations').html(data);
            },

            error: function(MLHttpRequest, textStatus, errorThrown){
                $('#isc-image-post-relations').html(errorThrown);
            }

        });
    });
});

function isc_thumbnail_input_checkstate(){
    if ('custom' == jQuery('#thumbnail-size-select').val()) {
        jQuery('#custom-width').removeAttr('disabled').css('background-color', '#fff');
        jQuery('#custom-height').removeAttr('disabled').css('background-color', '#fff');
    } else {
        jQuery('#custom-width').attr('disabled', 'disabled').css('background-color', '#eee');
        jQuery('#custom-height').attr('disabled', 'disabled').css('background-color', '#eee');
    }
}

function isc_caption_checkstate() {
    if (false == jQuery('#source-on-image').prop('checked')) {
        jQuery('#source-pretext').attr('disabled', 'disabled').css('background-color', '#eee');
    } else {
        jQuery('#source-pretext').removeAttr('disabled');
        jQuery('#source-pretext').css('background-color', '#fff');
    }
}
