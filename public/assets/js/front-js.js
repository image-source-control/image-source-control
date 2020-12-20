if (document.readyState != 'loading') {
	ISCready();
} else {
	document.addEventListener( 'DOMContentLoaded', ISCready );
}

/**
 * Initialize ISC after the DOM was loaded
 */
function ISCready(){
		/**
		 * Move caption into image with a short delay to way for the images to load
		 */
		setTimeout( function(){
			var captions = document.querySelectorAll( '.isc-source .isc-source-text' );
			var l        = captions.length;
			for ( var i = 0; i < l; i++ ) {
				captions[i].setAttribute( "style", "position: absolute; font-size: 0.9em; background-color: #333; color: #fff; opacity: 0.70; padding: 0 0.15em; text-shadow: none; display: block" );
				// Some themes handle the bottom padding of the attachment's div with the caption text (which is in between
				// the image and the bottom border) not with the div itself. The following line set the padding on the bottom equal to the top.
				captions[i].style.paddingBottom = window.getComputedStyle( captions[i] )['padding-top'];
				// position the parent element (.isc-source)
				isc_update_caption_position( captions[i].parentNode );
			}
		}, 100 );

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
	var captions = document.querySelectorAll( '.isc-source' );
	var l        = captions.length;
	for ( var i = 0; i < l; i++ ) {
		isc_update_caption_position( captions[i] );
	}
}

/**
 * Position a single image source caption
 *
 * @param el image source caption that needs positioning
 */
function isc_update_caption_position( el ) {
	var main_id = el.id;

	// attachment ID. unused
	var att_id = main_id.split( '_' )[2];

	var att  = el.querySelector( 'img' );
	var attw = att.width;
	var atth = att.height;

	// relative position
	var l = att.offsetLeft;
	var t = att.offsetTop;

	var caption = el.querySelector( '.isc-source-text' );

	// caption width + padding & margin (after moving onto image)
	var tw = ISCouterWidth( caption );
	// caption height + padding (idem)
	var th = ISCouterHeight( caption );

	var attpl = parseInt( window.getComputedStyle( att )[ 'padding-left' ].substring( 0, window.getComputedStyle( att )[ 'padding-left' ].indexOf( 'px' ) ) );
	var attpt = parseInt( window.getComputedStyle( att )[ 'padding-top' ].substring( 0, window.getComputedStyle( att )[ 'padding-top' ].indexOf( 'px' ) ) );
	var attml = parseInt( window.getComputedStyle( att )[ 'margin-left' ].substring( 0, window.getComputedStyle( att )[ 'margin-left' ].indexOf( 'px' ) ) );
	var attmt = parseInt( window.getComputedStyle( att )[ 'margin-top' ].substring( 0, window.getComputedStyle( att )[ 'margin-top' ].indexOf( 'px' ) ) );

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
	caption.style.left   = posl + 'px';
	caption.style.top    = post + 'px';
	caption.style.zIndex = '9999';
}

/**
 * Polyfills to work on IE 8
 *
 * Note: there are a couple of holes, e.g., with missing addEventListener; I am not yet decided on adding full IE 8 support
 */
// source: https://gist.github.com/abbotto/19a6680bf052e8c64f6e
if ( ! window.getComputedStyle) {
	/**
	 * Implement getComputedStyle() for browsers that don’t support it, e.g., IE 8
	 *
	 * @param {(Element|null)} e
	 * @param {(null|string)=} t
	 * @return {(CSSStyleDeclaration|null)}
	 */
	window.getComputedStyle = function(e, t) {
		return this.el = e, this.getPropertyValue = function(t) {
			// @type {RegExp}
			var n = /(\-([a-z]){1})/g;
			return t == "float" && (t = "styleFloat"), n.test( t ) && (t = t.replace( n, function() {
				return arguments[2].toUpperCase();
			} )), e.currentStyle[t] ? e.currentStyle[t] : null;
		}, this;
	};
}
// mimics `outerWidth(true)` which includes margins
// source http://youmightnotneedjquery.com/
function ISCouterWidth(el) {
	var width = el.offsetWidth;
	var style = getComputedStyle( el );

	width += parseInt( style.marginLeft ) + parseInt( style.marginRight );
	return width;
}
// mimics `outerHeight(true)` which includes margins
// source http://youmightnotneedjquery.com/
function ISCouterHeight(el) {
	var height = el.offsetHeight;
	var style  = getComputedStyle( el );

	height += parseInt( style.marginTop ) + parseInt( style.marginBottom );
	return height;
}
