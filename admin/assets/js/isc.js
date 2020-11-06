jQuery( document ).ready(
	function($) {
		isc_thumbnail_input_checkstate();
		isc_caption_checkstate();
		$( '#isc-settings-overlay-enable' ).click( function(){isc_caption_checkstate()} );
		$( '.isc-settings-standard-source input' ).change( isc_toggle_standard_source_text );
		$( '#use-thumbnail' ).click(
			function(){
				if ('disabled' == $( '#thumbnail-size-select' ).attr( 'disabled' )) {
					$( '#thumbnail-size-select' ).removeAttr( 'disabled' );
				} else {
					$( '#thumbnail-size-select' ).attr( 'disabled', 'disabled' );
				}
			}
		);
		$( '#thumbnail-size-select' ).change( function(){isc_thumbnail_input_checkstate()} );

		// debug function – load image-post relations
		// call post-image relation (meta fields saved for posts)
		$( '#isc-list-post-image-relation' ).click(
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
		$( '#isc-list-image-post-relation' ).click(
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
		$( '#isc-clear-index' ).click(
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
	if (false == jQuery( '#isc-settings-overlay-enable' ).prop( 'checked' )) {
		jQuery( '.isc_settings_section_overlay' ).find( 'input, select' ).attr( 'disabled', 'disabled' );
	} else {
		jQuery( '.isc_settings_section_overlay' ).find( 'input, select' ).removeAttr( 'disabled' );
	}
}

/**
 * Toggle the state of the Custom Text option in the Standard Sources settings
 */
function isc_toggle_standard_source_text() {
	if ( jQuery( '#isc-custom-text-select' ).is( ':checked' ) ) {
		jQuery( '#isc-custom-text' ).removeAttr( 'disabled' );
	} else {
		jQuery( '#isc-custom-text' ).attr( 'disabled', 'disabled' );
	}
}
