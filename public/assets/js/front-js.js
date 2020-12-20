jQuery( document ).ready( function(){
	/**
	* Move caption into image with a short delay to way for the images to load
	*/
	setTimeout( function(){
			jQuery( '.isc-source' ).each( function(){
				jQuery( this ).find( '.isc-source-text' ).css( {
					position: 'absolute',
					fontSize: '0.9em',
					backgroundColor: "#333",
					color: "#fff",
					opacity: "0.70",
					padding: '0 0.15em',
					textShadow: 'none',
					display: 'block',
				} );
				// Some themes handle the bottom padding of the attachment's div with the caption text (which is in between
				// the image and the bottom border) not with the div itself. The following line set the padding on the bottom equal to the top.
				jQuery( this ).css( 'padding-bottom', jQuery( this ).css( 'padding-top' ) );
				isc_update_caption_position( jQuery( this ) );
			} );
	}, 100 );

	/**
	 * Register resize event to check caption positions
	 */
	window.addEventListener( 'resize', function() {
		isc_update_captions_positions();
	} );
	/** Doesn’t seem needed anymore
	jQuery('.isc-source img').on('load', function(){
		isc_update_captions_positions();
	});
	*/
} );

/**
 * Iterate through image source captions and set their position on the screen
 */
function isc_update_captions_positions() {
	jQuery( '.isc-source' ).each( function(){
		isc_update_caption_position( jQuery( this ) );
	} );
}

/**
 * Position a single image source caption
 *
 * @param jQ_Obj
 */
function isc_update_caption_position(jQ_Obj) {
	var main_id    = jQ_Obj.attr( 'id' );
	var att_number = main_id.split( '_' )[2];
	// try to look for single image only in case this is a gallery
	// var att = jQ_Obj.find('.wp-image-' + att_number);
	var att = jQ_Obj.find( 'img' );
	// console.log( att );
	var attw = att.width();
	var atth = att.height();

	// relative position
	var l = att.position().left;
	// relative position
	var t = att.position().top;

	var caption = jQ_Obj.find( '.isc-source-text' );

	// caption width + padding & margin (after moving onto image)
	var tw = caption.outerWidth( true );
	// caption height + padding (idem)
	var th = caption.outerHeight( true );

	var attpl = parseInt( att.css( 'padding-left' ).substring( 0, att.css( 'padding-left' ).indexOf( 'px' ) ) );
	var attml = parseInt( att.css( 'margin-left' ).substring( 0, att.css( 'margin-left' ).indexOf( 'px' ) ) );
	var attpt = parseInt( att.css( 'padding-top' ).substring( 0, att.css( 'padding-top' ).indexOf( 'px' ) ) );
	var attmt = parseInt( att.css( 'margin-top' ).substring( 0, att.css( 'margin-top' ).indexOf( 'px' ) ) );

	// caption horizontal margin
	var tml = 5;
	// caption vertical margin
	var tmt = 5;

	var pos  = isc_front_data.caption_position;
	var posl = 0;
	var post = 0;
	switch (pos) {
		case 'top-left':
			posl = l + attpl + attml + tml;
			post = t + attpt + attmt + tmt;
			break;
		case 'top-center':
			posl = l + (Math.round( attw / 2 ) - (Math.round( tw / 2 ))) + attpl + attml;
			post = t + attpt + attmt + tmt;
			break;
		case 'top-right':
			posl = l - attpl + attml - tml + attw - tw;
			post = t + attpt + attmt + tmt;
			break;
		case 'center':
			posl = l + (Math.round( attw / 2 ) - (Math.round( tw / 2 ))) + attpl + attml;
			post = t + (Math.round( atth / 2 ) - (Math.round( th / 2 ))) + attpt + attmt;
			break;
		case 'bottom-left':
			posl = l + attpl + attml + tml;
			post = t - attpt + attmt - tmt - th + atth;
			break;
		case 'bottom-center':
			posl = l + (Math.round( attw / 2 ) - (Math.round( tw / 2 ))) + attpl + attml;
			post = t + attpt + attmt - tmt - th + atth;
			break;
		case 'bottom-right':
			posl = l - attpl + attml - tml + attw - tw;
			post = t + attpt + attmt - tmt - th + atth;
			break;
	}
	caption.css( {
		left: posl + 'px',
		top: post + 'px',
		zIndex: 9999,
	} );
}
