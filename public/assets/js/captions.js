if ( document.readyState !== 'loading' ) {
	ISCready();
} else {
	// DOMContentLoaded fires after the content is loaded, but before scripts and images.
	document.addEventListener( 'DOMContentLoaded', ISCready );
}

// Use values from isc_front_data if available, otherwise use default values
const ISC_FONT_SIZE                 = isc_front_data.font_size || '0.9em';
const ISC_BACKGROUND_COLOR          = isc_front_data.background_color || '#333';
const ISC_TEXT_COLOR                = isc_front_data.text_color || '#fff';
const ISC_OPACITY                   = isc_front_data.opacity || 0.70;
const ISC_PADDING                   = isc_front_data.padding || '0 0.15em';
const ISC_DISPLAY                   = isc_front_data.display || 'block';
const ISC_Z_INDEX                   = isc_front_data.z_index || '9999';
const ISC_CAPTION_HORIZONTAL_MARGIN = isc_front_data.caption_horizontal_margin || 5;
const ISC_CAPTION_VERTICAL_MARGIN   = isc_front_data.caption_vertical_margin || 5;

/**
 * Initialize ISC after the DOM was loaded
 */
function ISCready(){
		/**
		 * Move the caption into the image with a short delay so the images can fully load
		 */
		setTimeout( function(){
			const captions = document.querySelectorAll( '.isc-source .isc-source-text' );
			const l        = captions.length;
			for ( let i = 0; i < l; i++ ) {
				captions[i].setAttribute( "style", `position: absolute; font-size: ${ISC_FONT_SIZE}; background-color: ${ISC_BACKGROUND_COLOR}; color: ${ISC_TEXT_COLOR}; opacity: ${ISC_OPACITY}; padding: ${ISC_PADDING}; text-shadow: none; display: ${ISC_DISPLAY}` );
				// Some themes handle the bottom padding of the attachment's div with the caption text (which is in between
				// the image and the bottom border) not with the div itself. The following line set the padding on the bottom equal to the top.
				captions[i].style.paddingBottom = window.getComputedStyle( captions[i] )['padding-top'];
				// position the parent element (.isc-source)
				isc_update_caption_position( captions[i].parentNode );
			}
		}, 100 );

		/**
		 * Load image source positions after the page – including images – loaded completely
		 * this resolves occasionally misplaced sources
		 */
		window.addEventListener( 'load', function() {
			// the additional timeout seems needed to make it work reliably on Firefox
			setTimeout( function(){
				isc_update_captions_positions();
			}, 100 );
		} );
		/**
		 * Register resize event to check caption positions
		 */
		window.addEventListener( 'resize', function() {
			isc_update_captions_positions();
		} );
};

/**
 * Iterate through image source captions and set their position on the screen
 */
function isc_update_captions_positions() {
	const captions = document.querySelectorAll( '.isc-source' );
	const l        = captions.length;
	for ( let i = 0; i < l; i++ ) {
		isc_update_caption_position( captions[i] );
	}
}

/**
 * Position a single image source caption
 *
 * @param el image source caption that needs positioning
 */
function isc_update_caption_position( el ) {
	const caption = el.querySelector( '.isc-source-text' );
	if ( ! caption ) {
		return;
	}

	let att = el.querySelector( 'img' );
	let is_fallback = false;
	// fall back to the current main container to get the width and height, if no image was found. This could be a background image defined in CSS
	if ( ! att ) {
		att = el;
		is_fallback = true;
	}
	// look for the actual width and height as a fallback, if these attributes are not set
	const attw = att instanceof HTMLImageElement ? att.width : att.offsetWidth;
	const atth = att instanceof HTMLImageElement ? att.height : att.offsetHeight;

	// relative position
	const l = ! is_fallback ? att.offsetLeft : 0;
	const t = ! is_fallback ? att.offsetTop : 0;

	// caption width + padding & margin (after moving onto image)
	const tw = ISCouterWidth( caption );
	// caption height + padding (idem)
	const th = ISCouterHeight( caption );

	const attpl = ! is_fallback ? parseInt( window.getComputedStyle( att )[ 'padding-left' ].substring( 0, window.getComputedStyle( att )[ 'padding-left' ].indexOf( 'px' ) ) ) : 0;
	const attpt = ! is_fallback ? parseInt( window.getComputedStyle( att )[ 'padding-top' ].substring( 0, window.getComputedStyle( att )[ 'padding-top' ].indexOf( 'px' ) ) ) : 0;
	const attml = ! is_fallback ? parseInt( window.getComputedStyle( att )[ 'margin-left' ].substring( 0, window.getComputedStyle( att )[ 'margin-left' ].indexOf( 'px' ) ) ) : 0;
	const attmt = ! is_fallback ? parseInt( window.getComputedStyle( att )[ 'margin-top' ].substring( 0, window.getComputedStyle( att )[ 'margin-top' ].indexOf( 'px' ) ) ) : 0;

	// caption horizontal margin
	const tml = ISC_CAPTION_HORIZONTAL_MARGIN;
	// caption vertical margin
	const tmt = ISC_CAPTION_VERTICAL_MARGIN;

	const pos = isc_front_data.caption_position;
	let posl  = 0;
	let post  = 0;
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
	caption.style.left   = posl + 'px';
	caption.style.top    = post + 'px';
	caption.style.zIndex = ISC_Z_INDEX;
}

/**
 * Mimics `outerWidth(true)` which includes margins
 *
 * @source http://youmightnotneedjquery.com/
 */
function ISCouterWidth(el) {
	let style = getComputedStyle( el );

	return el.offsetWidth + parseInt( style.marginLeft ) + parseInt( style.marginRight );
}

/**
 * Mimics `outerHeight(true)` which includes margins
 *
 * @source http://youmightnotneedjquery.com/
 */
function ISCouterHeight(el) {
	let style = getComputedStyle( el );

	return el.offsetHeight + parseInt( style.marginTop ) + parseInt( style.marginBottom );
}
