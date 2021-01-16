jQuery( document ).ready(
	function($) {
		isc_thumbnail_input_checkstate();
		isc_caption_checkstate();
		$( '#isc-settings-overlay-enable' ).on( 'click', function(){ isc_caption_checkstate() } );
		$( '.isc-settings-standard-source input' ).on( 'change', isc_toggle_standard_source_text );
		$( '#use-thumbnail' ).on(
			'click',
			function(){
				if ( document.getElementById( 'thumbnail-size-select' ).disabled ) {
					document.getElementById( 'thumbnail-size-select' ).removeAttribute( 'disabled' );
				} else {
					document.getElementById( 'thumbnail-size-select' ).setAttribute( 'disabled', 'disabled' );
				}
			}
		);
		$( '#thumbnail-size-select' ).on( 'change', function(){isc_thumbnail_input_checkstate()} );

		// debug function – load image-post relations
		// call post-image relation (meta fields saved for posts)
		$( '#isc-list-post-image-relation' ).on(
			'click',
			function(){
				// disable the button
				var button      = this;
				button.disabled = true;

				$.ajax(
					{
						type: 'POST',
						url: ajaxurl,
						data: {
							action: 'isc-post-image-relations',
							nonce: isc.ajaxNonce,
						},
						success:function(data, textStatus, XMLHttpRequest){
							// display return messages
							$( '#isc-post-image-relations' ).html( data );
							button.disabled = false;
						},

						error: function(MLHttpRequest, textStatus, errorThrown){
							$( '#isc-post-image-relations' ).html( errorThrown );
							button.disabled = false;
						}

					}
				);
			}
		);
		// call image-post relation (meta fields saved for posts)
		$( '#isc-list-image-post-relation' ).on(
			'click',
			function(){
				// disable the button
				var button      = this;
				button.disabled = true;

				$.ajax(
					{
						type: 'POST',
						url: ajaxurl,
						data: {
							action: 'isc-image-post-relations',
							nonce: isc.ajaxNonce,
						},
						success:function(data, textStatus, XMLHttpRequest){
							// display return messages
							$( '#isc-image-post-relations' ).html( data );
							button.disabled = false;
						},
						error: function(MLHttpRequest, textStatus, errorThrown){
							$( '#isc-image-post-relations' ).html( errorThrown );
							button.disabled = false;
						}
					}
				);
			}
		);
		// remove image-post index
		$( '#isc-clear-index' ).on(
			'click',
			function(){

				var areYouSure = confirm( isc_data.confirm_message );

				if ( ! areYouSure ) {
					return;
				}

				// disable the button
				var button      = this;
				button.disabled = true;

				$.ajax(
					{
						type: 'POST',
						url: ajaxurl,
						data: {
							action: 'isc-clear-index',
							nonce: isc.ajaxNonce,
						},
						success:function(data, textStatus, XMLHttpRequest){
							// display return messages
							$( '#isc-clear-index-feedback' ).html( data );
							button.disabled = false;
						},
						error: function(MLHttpRequest, textStatus, errorThrown){
							$( '#isc-clear-index-feedback' ).html( errorThrown );
							button.disabled = false;
						}

					}
				);
			}
		);
	}
);

/**
 * Toggle the state of the thumbnail size options in the plugin settings
 * to only enable them if the "custom" thumbnails size is used
 *
 * @todo: also disable them when the "thumbnail" option itself is unchecked
 */
function isc_thumbnail_input_checkstate(){
	if ('custom' == jQuery( '#thumbnail-size-select' ).val()) {
		jQuery( '#isc-settings-custom-width' ).removeAttr( 'disabled' );
		jQuery( '#isc-settings-custom-height' ).removeAttr( 'disabled' );
	} else {
		jQuery( '#isc-settings-custom-width' ).attr( 'disabled', 'disabled' );
		jQuery( '#isc-settings-custom-height' ).attr( 'disabled', 'disabled' );
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
	var elements = document.querySelectorAll( '.isc_settings_section_overlay input, .isc_settings_section_overlay input, select' );
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
