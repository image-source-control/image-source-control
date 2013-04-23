var isc_nb = 0;

jQuery(function(){
    /**
    * Hide/Show image list when post/page is loaded
    */
    var isc_capt_count = jQuery('.wp-caption img').length;
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
    
    /**
    * Move caption into image
    */
    jQuery('.wp-caption').each(function(){
        var main_id = jQuery(this).attr('id');
        var att_number = main_id.split('_')[1];
        var caption = jQuery(this).children().filter('.wp-caption-text').html();
        jQuery(this).find('.wp-caption-text').remove();
        jQuery(this).append(jQuery('<span />').addClass('isc-caption-text').html(caption).css({
            position: 'absolute',
            fontSize: '0.9em',
            backgroundColor: "#333",
            color: "#fff",
            opacity: "0.70",
            padding: '0 0.15em',
            textShadow: 'none',
        }));
        // Some themes handle the bottom padding of the attachment's div with the caption text (which is in between
        // the image and the bottom border) not with the div itself. The following line set the padding on the bottom equal to the top.
        jQuery(this).css('padding-bottom', jQuery(this).css('padding-top'));
        isc_update_captions_positions();
    });
    
    jQuery(window).resize(function(){
        isc_update_captions_positions();
    });
    jQuery('.wp-caption img').load(function(){
        isc_update_captions_positions();
    });
});

function isc_update_captions_positions() {
    jQuery('.wp-caption').each(function(){
        isc_update_caption_position(jQuery(this));
    });
}

function isc_update_caption_position(jQ_Obj) {
    var main_id = jQ_Obj.attr('id');
    var att_number = main_id.split('_')[1];
    var att = jQ_Obj.find('.wp-image-' + att_number);
    var attw = att.width();
    var atth = att.height();
    
    //relative position
    var l = att.position().left;
    //relative position
    var t = att.position().top;
    
    var caption = jQ_Obj.find('.isc-caption-text');
    
    //caption width + padding (after moving onto image)
    var tw = caption.outerWidth();
    //caption height + padding (idem)
    var th = caption.innerHeight();
    
    var attpl = parseInt(att.css('padding-left').substring(0, att.css('padding-left').indexOf('px')));
    var attpt = parseInt(att.css('padding-top').substring(0, att.css('padding-top').indexOf('px')));
    
    //caption horizontal margin
    var tml = 4;
    //caption vertical margin
    var tmt = 4;
    
    var pos = isc_front_data.caption_position;
    var posl = 0;
    var post = 0;
    switch (pos) {
        case 'top-left':
            posl = l + attpl + tml;
            post = t + attpt + tmt;
            break;
        case 'top-center':
            posl = l + (Math.round(attw/2) - (Math.round(tw/2)));
            post = t + attpt + tmt;
            break;
        case 'top-right':
            posl = l - attpl - tml + attw - tw;
            post = t + attpt + tmt;
            break;
        case 'center':
            posl = l + (Math.round(attw/2) - (Math.round(tw/2)));
            post = t + (Math.round(atth/2) - (Math.round(th/2)));
            break;
        case 'bottom-left':
            posl = l + attpl + tml;
            post = t - attpt - tmt - th + atth;
            break;
        case 'bottom-center':
            posl = l + (Math.round(attw/2) - (Math.round(tw/2)));
            post = t + attpt - tmt - th + atth;
            break;
        case 'bottom-right':
            posl = l - attpl - tml + attw - tw;
            post = t + attpt - tmt - th + atth;
            break;
    }
    caption.css({
        left: posl + 'px',
        top: post + 'px',
        zIndex: 9999,
    });
}
