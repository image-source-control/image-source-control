/**
 * Scripts for Settings > Image Sources
 */
jQuery( document ).ready(
	function($) {
		isc_thumbnail_input_checkstate();
		isc_caption_checkstate();
		$( '#isc-settings-overlay-enable' ).on( 'click', function(){ isc_caption_checkstate() } );
		$( '.isc-settings-standard-source input' ).on( 'change', isc_toggle_standard_source_text );
		$( '#thumbnail-size-select, #use-thumbnail' ).on( 'change', function(){ isc_thumbnail_input_checkstate(); } );
	}
);

/**
 * Toggle the state of the thumbnail size options
 */
function isc_thumbnail_input_checkstate(){
	// enable the thumbnail size select field when thumbnails are enabled in general
	if ( document.getElementById( 'use-thumbnail' ).checked ) {
		document.getElementById( 'thumbnail-size-select' ).removeAttribute( 'disabled' );
	} else {
		document.getElementById( 'thumbnail-size-select' ).setAttribute( 'disabled', 'disabled' );
	}

	// toggle the state of the thumbnail custom size options in the plugin settings to only enable them if the "custom" thumbnails size is used
	if ( 'custom' == document.getElementById( 'thumbnail-size-select' ).value && document.getElementById( 'use-thumbnail' ).checked ) {
		document.getElementById( 'isc-settings-custom-width' ).removeAttribute( 'disabled' );
		document.getElementById( 'isc-settings-custom-height' ).removeAttribute( 'disabled' );
	} else {
		document.getElementById( 'isc-settings-custom-width' ).setAttribute( 'disabled', 'disabled' );
		document.getElementById( 'isc-settings-custom-height' ).setAttribute( 'disabled', 'disabled' );
	}
}

/**
 * Toggle the state of the options in the Overlay settings
 * disable them if the Overlay position option is not enabled
 */
function isc_caption_checkstate() {
	var overlay_option = document.getElementById( 'isc-settings-overlay-enable' );

	if ( ! overlay_option ) {
		return;
	}
	var elements = document.querySelectorAll( '.isc_settings_section_overlay input, .isc_settings_section_overlay input, .isc_settings_section_overlay select' );
	if ( overlay_option.checked ) {
		Array.prototype.forEach.call( elements, function(el, i) {
			el.removeAttribute( 'disabled' );
		} );
	} else {
		Array.prototype.forEach.call( elements, function(el, i) {
			el.setAttribute( 'disabled', 'disabled' );
		} );
	}
}

/**
 * Toggle the state of the Custom Text option in the Standard Sources settings
 */
function isc_toggle_standard_source_text() {
	var standard_source_custom = document.getElementById( 'isc-custom-text-select' );

	if ( ! standard_source_custom ) {
		return;
	}

	if ( standard_source_custom.checked ) {
		document.getElementById( 'isc-custom-text' ).removeAttribute( 'disabled' );
	} else {
		document.getElementById( 'isc-custom-text' ).setAttribute( 'disabled', 'disabled' );
	}
}