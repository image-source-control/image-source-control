jQuery(document).ready(function(jQuery) {
    isc_thumbnail_input_checkstate();
    isc_caption_checkstate();
    jQuery('#source-on-image').click(function(){isc_caption_checkstate()});
    jQuery('#use_authorname').click(function(){
        if ('disabled' == jQuery('#byauthor').attr('disabled')) {
            jQuery('#byauthor').removeAttr('disabled');
        } else {
            jQuery('#byauthor').attr('disabled', 'disabled');
        }
    });
	jQuery('#use-thumbnail').click(function(){
        if ('disabled' == jQuery('#thumbnail-size-select').attr('disabled')) {
            jQuery('#thumbnail-size-select').removeAttr('disabled');
        } else {
            jQuery('#thumbnail-size-select').attr('disabled', 'disabled');
        }
    });
    jQuery('#thumbnail-size-select').change(function(){isc_thumbnail_input_checkstate()});
    
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
