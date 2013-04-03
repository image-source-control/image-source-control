jQuery(function(){
    jQuery('.isc_image_list_title').click(function(){
        isc_list = jQuery('.isc_image_list');
        if ( isc_list.hasClass('isc-list-up') ) {
            jQuery('.isc_image_list_title').attr('title', (isc_jstext['hide_list']));
            isc_list.toggleClass('isc-list-up isc-list-down');
            isc_list.css({
                visibility: 'hidden',
                height: '100%',
            });
            max = isc_list.height();
            isc_list.css({
            height: '0px',
            visibility: 'visible'
            }).animate(
                {height: max + 'px'},
                1500
            );
        } else {
            jQuery('.isc_image_list_title').attr('title', (isc_jstext['show_list']));
            isc_list.toggleClass('isc-list-up isc-list-down');
            isc_list.animate(
                {height: 0},
                1500
            );
        }
    });
});
