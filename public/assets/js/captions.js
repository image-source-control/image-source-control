if ( document.readyState !== 'loading' ) {
	ISCready();
} else {
	// DOMContentLoaded fires after the content is loaded, but before scripts and images.
	document.addEventListener( 'DOMContentLoaded', ISCready );
}

// Use values from isc_front_data if available, otherwise use default values
const ISC_Z_INDEX                   = isc_front_data.z_index || '9999';
const ISC_CAPTION_HORIZONTAL_MARGIN = isc_front_data.caption_horizontal_margin || 5;
const ISC_CAPTION_VERTICAL_MARGIN   = isc_front_data.caption_vertical_margin || 5;
const ISC_STYLE_STRING = (() => {
	const styles = isc_front_data.caption_style || {};
	let styleString = '';

	for ( const [property, value] of Object.entries(styles) ) {
		styleString += `${property}: ${value}; `;
	}

	return styleString.trim();
})();

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
				isc_update_caption_style( captions[i] );
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

		/**
		 * Monitor DOM changes to update captions
		 */
		isc_setup_mutation_observer();
};

/**
 * Iterate through all image source captions and set basic CSS style
 *
 * This can be used to add styles to dynamically added captions, e.g., by AJAX calls
 */
function isc_update_captions_styles() {
	const captions = document.querySelectorAll( '.isc-source .isc-source-text' );
	const l        = captions.length;
	for ( let i = 0; i < l; i++ ) {
		isc_update_caption_style( captions[i] );
	}
}

/**
 * Add basic CSS to a single image source caption
 *
 * @param caption image source caption that needs positioning
 */
function isc_update_caption_style( caption ) {
	// bail if the element already has a style attribute
	if ( caption.hasAttribute( 'style' ) ) {
		return;
	}

	caption.setAttribute( "style", ISC_STYLE_STRING );
	// Some themes handle the bottom padding of the attachment's div with the caption text (which is in between
	// the image and the bottom border) not with the div itself. The following line set the padding on the bottom equal to the top.
	caption.style.paddingBottom = window.getComputedStyle( caption )['padding-top'];
}

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

	// reset any previously set positioning to get natural dimensions
	caption.style.position = 'absolute';
	caption.style.left = '0';
	caption.style.top = '0';

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
			post = t + attpt + tmt;
			break;
		case 'top-center':
			posl = l + (Math.round( attw / 2 ) - (Math.round( tw / 2 ))) + attpl + attml;
			post = t + attpt + tmt;
			break;
		case 'top-right':
			posl = l - attpl + attml - tml + attw - tw;
			post = t + attpt + tmt;
			break;
		case 'center':
			posl = l + (Math.round( attw / 2 ) - (Math.round( tw / 2 ))) + attpl + attml;
			post = t + (Math.round( atth / 2 ) - (Math.round( th / 2 ))) + attpt + attmt;
			break;
		case 'bottom-left':
			posl = l + attpl + attml + tml;
			post = t - attpt - tmt - th + atth;
			break;
		case 'bottom-center':
			posl = l + (Math.round( attw / 2 ) - (Math.round( tw / 2 ))) + attpl + attml;
			post = t + attpt - tmt - th + atth;
			break;
		case 'bottom-right':
			posl = l - attpl + attml - tml + attw - tw;
			post = t + attpt - tmt - th + atth;
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
	// Force the caption to fit its content
	el.style.width = 'auto';
	let style = getComputedStyle(el);

	// Get the natural width of the content
	const naturalWidth = el.getBoundingClientRect().width;
	const margins = parseInt(style.marginLeft) + parseInt(style.marginRight);

	// Store this width as a data attribute to maintain consistency
	el.dataset.naturalWidth = naturalWidth;

	return naturalWidth + margins;
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

/**
 * Sets up the MutationObserver to watch for dynamically added captions.
 */
function isc_setup_mutation_observer() {
	// Get selectors for elements to observe from PHP, default to an empty array
	const elementsToObserveSelectors = isc_front_data.observe_elements_selectors || [];

	if ( elementsToObserveSelectors.length === 0 ) {
		return; // Do nothing if no selectors are provided
	}

	const observer = new MutationObserver( ( mutationsList, observerInstance) => {
		let newCaptionsDetected = false;
		for ( const mutation of mutationsList ) {
			// We are interested in mutations where nodes were added.
			if ( mutation.type === 'childList' && mutation.addedNodes.length > 0 ) {
				// Iterate through each node that was added.
				// An AJAX response might add multiple sibling elements at once.
				for ( const addedNode of mutation.addedNodes ) {
					// We only operate on actual HTML elements.
					if ( addedNode.nodeType === Node.ELEMENT_NODE ) {
						// Check for the presence of the caption text
						if ( addedNode.querySelector( '.isc-source .isc-source-text' ) ) {
							newCaptionsDetected = true;
							// Found what we're looking for, no need to check further
							// added nodes in this particular mutation.
							break;
						}
					}
				}
			}
			// If we've detected new captions in any of the mutations,
			// we can stop checking the rest of the mutations.
			if ( newCaptionsDetected ) {
				break;
			}
		}

		if ( newCaptionsDetected ) {
			console.log('ISC MutationObserver: New captions detected. Updating styles and positions.');
			isc_update_captions_styles();
			isc_update_captions_positions();
		}
	});

	elementsToObserveSelectors.forEach( selector => {
		const targetNode = document.querySelector(selector);
		if ( targetNode ) {
			observer.observe( targetNode, { childList: true, subtree: true } );
		}
	} );
}

/**
 * Caption Positioning When Images Load Dynamically
 *
 * This script ensures that image captions are correctly positioned even when images load dynamically,
 * lazy-loading occurs, or layout changes happen. It achieves this through a combination of event listeners
 * and a MutationObserver that watches for relevant changes.
 *
 * Features:
 * - Observes changes to images inside `.isc-source` elements.
 * - Listens for `load` events on images to update caption positions.
 * - Uses a MutationObserver to detect relevant DOM changes while ignoring `.isc-source-text` elements to prevent infinite loops.
 * - Debounces updates to avoid excessive function calls.
 *
 * How it Works:
 * - `updateAllCaptions()`: Updates all caption positions, ensuring changes are applied efficiently.
 * - `observeDOMChanges()`: Watches for changes in image attributes (`src`, `width`, `height`, `style`) and dynamically added `.isc-source` elements.
 * - `observeImageLoads()`: Ensures captions update when images finish loading.
 * - `initCaptionFix()`: Initializes the script once the DOM is ready.
 */
(function () {
	let updateTimeout;
	let isUpdatingCaptions = false;

	/**
	 * Function to update all captions' positions (debounced to avoid excessive calls)
	 */
	function updateAllCaptions() {
		if (isUpdatingCaptions) return; // Prevent recursive updates

		isUpdatingCaptions = true;
		clearTimeout(updateTimeout);
		updateTimeout = setTimeout(() => {
			isc_update_captions_positions();
			isUpdatingCaptions = false;
		}, 100); // Debounce: Only update once within 100ms
	}

	/**
	 * Observe changes in the DOM for dynamically loaded images
	 */
	function observeDOMChanges() {
		const observer = new MutationObserver((mutations) => {
			let needsUpdate = false;

			// Using for...of for better performance with early exit
			for (const mutation of mutations) {
				// Ignore changes to .isc-source-text elements (captions)
				if (mutation.target.closest(".isc-source-text")) {
					continue;
				}

				// Only trigger updates if images inside .isc-source are modified
				if (
					mutation.type === "childList" ||
					(mutation.type === "attributes" &&
						mutation.target.closest(".isc-source") &&
						["src", "width", "height", "style"].includes(mutation.attributeName)
					)
				) {
					needsUpdate = true;
					break; // Exit early once we know we need an update
				}
			}

			if (needsUpdate) {
				updateAllCaptions();
			}
		});

		// Observe the entire document but filter for .isc-source elements
		observer.observe(document.body, {
			childList: true,
			subtree: true, // Observe all new elements dynamically added
			attributes: true,
			attributeFilter: ["src", "width", "height", "style"]
		});

		// Cleanup on page unload
		window.addEventListener('unload', () => observer.disconnect(), { once: true });
	}

	/**
	 * Attach event listeners to images to update captions when they load
	 */
	function observeImageLoads() {
		const images = document.querySelectorAll(".isc-source img");

		images.forEach((img) => {
			if (img.complete && img.naturalWidth !== 0) {
				// Only update if image is actually loaded with dimensions
				isc_update_caption_position(img.closest(".isc-source"));
			}

			img.addEventListener("load", function () {
				isc_update_caption_position(img.closest(".isc-source"));
			}, { once: true }); // Ensures the event only fires once
		});
	}

	/**
	 * Initialize the solution
	 */
	function initCaptionFix() {
		observeImageLoads();
		observeDOMChanges();
	}

	// Run when the document is ready
	if (document.readyState !== "loading") {
		initCaptionFix();
	} else {
		document.addEventListener("DOMContentLoaded", initCaptionFix);
	}
})();
