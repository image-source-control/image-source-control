jQuery(document).ready(function(jQuery) {
    isc_thumbnail_input_checkstate();
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
        jQuery('#custom-width').removeAttr('disabled').css('background-color', 'inherit');
        jQuery('#custom-height').removeAttr('disabled').css('background-color', 'inherit');
    } else {
        jQuery('#custom-width').attr('disabled', 'disabled').css('background-color', '#eee');
        jQuery('#custom-height').attr('disabled', 'disabled').css('background-color', '#eee');
    }
}
