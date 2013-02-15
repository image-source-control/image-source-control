jQuery(document).ready(function(jQuery) {

    jQuery('#use_authorname').click(function(){
        if ('disabled' == jQuery('#byauthor').attr('disabled')) {
            jQuery('#byauthor').removeAttr('disabled');
        } else {
            jQuery('#byauthor').attr({disabled : 'disabled'});
        }
    });
	
});
