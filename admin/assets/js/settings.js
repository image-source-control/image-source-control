/**
 * Scripts for Settings > Image Sources
 */
jQuery( document ).ready(
	function($) {
		isc_thumbnail_input_checkstate();
		isc_caption_checkstate();
		isc_toggle_caption_position();
		$( '#isc-settings-overlay-enable' ).on( 'click', function(){ isc_caption_checkstate() } );
		$( '.isc-settings-standard-source input' ).on( 'change', isc_toggle_standard_source_text );
		$( '#thumbnail-size-select, #use-thumbnail' ).on( 'change', function(){ isc_thumbnail_input_checkstate(); } );
		$( '#isc-settings-caption-style' ).on( 'change', function(){ isc_toggle_caption_position(); } );

		// Show and update preview when a position option is clicked
		$('#isc-settings-caption-pos-options button').on( 'click', function (event) {
			// Stop propagation to prevent document click event from hiding the preview immediately
			event.stopPropagation();

			$('#isc-settings-caption-pos-options button.selected').removeClass('selected');
			$(this).addClass('selected');
			$('#isc-settings-caption-position').val($(this).data('position'));

			var iframe = document.createElement('iframe');
			iframe.src = isc_settings.baseurl + 'admin/templates/settings/preview/caption-preview.html' + "?path=" + encodeURIComponent( isc_settings.baseurl ) + "&position=" + $(this).data('position') + "&pretext=" + encodeURIComponent( $('#source-pretext').val() );
			iframe.width = "250";
			iframe.height = "181";

			var preview_container = $('#isc-settings-caption-preview');
			preview_container.find('iframe').remove();
			preview_container.append(iframe);
			preview_container.removeClass('hidden');
		});

		// Hide the preview when the mouse leaves the option area or a click occurs outside the option area
		$(document).on('click mouseout', function (event) {
			if (!$(event.target).closest('#isc-settings-caption-pos-options').length) {
				$('#isc-settings-caption-preview').addClass('hidden');
			}
		});

		$('#isc-signup-nl').prop( 'disabled', false );
		$('#isc-signup-nl').on( 'click', function() {
			$('#isc-signup-nl').prop( 'disabled', true );
			$('#isc-signup-loader').removeClass('hidden');
			$.ajax({
				type: 'POST',
				url: ajaxurl,
				data: {
					action: 'newsletter_signup',
					nonce: isc.ajaxNonce,
				},
				dataType: 'json',
				success: function( response ) {
					if ( ! response.success ) {
						$('#isc-signup-nl-error').removeClass('hidden').html(response.error);
					} else {
						$('#isc-signup-nl-success').removeClass('hidden');
					}
					$('#isc-signup-loader').addClass('hidden');
				}
			});
		});
	}
);

/**
 * Toggle the state of the thumbnail size options
 */
function isc_thumbnail_input_checkstate(){
	// enable the thumbnail size select field when thumbnails are enabled in general
	if ( document.getElementById( 'use-thumbnail' ).checked ) {
		document.getElementById( 'thumbnail-size-select' ).classList.remove( 'hidden' );
	} else {
		document.getElementById( 'thumbnail-size-select' ).classList.add( 'hidden' )
	}

	// toggle the state of the thumbnail custom size options in the plugin settings to only enable them if the "custom" thumbnails size is used
	if ( 'custom' === document.getElementById( 'thumbnail-size-select' ).value && document.getElementById( 'use-thumbnail' ).checked ) {
		document.getElementById( 'isc-settings-custom-size' ).classList.remove( 'hidden' );
	} else {
		document.getElementById( 'isc-settings-custom-size' ).classList.add( 'hidden' );
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
	// Exclude disabled premium features from toggling their state
	var elements = document.querySelectorAll( '.isc_settings_section_overlay input:not(.is-pro), .isc_settings_section_overlay select' );
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

/**
 * Toggle the visibility of the position option when the caption style is changed
 */
function isc_toggle_caption_position() {
	var caption_style = document.getElementById( 'isc-settings-caption-style' );

	if ( ! caption_style ) {
		return;
	}

	if ( caption_style.checked ) {
		document.getElementById( 'isc-settings-caption-position-options-wrapper' ).classList.add( 'hidden' );
		// add class also to the sibling H4 element
		document.getElementById( 'isc-settings-caption-position-options-wrapper' ).previousElementSibling.classList.add( 'hidden' );
	} else {
		document.getElementById( 'isc-settings-caption-position-options-wrapper' ).classList.remove( 'hidden' );
		// remove class also from the sibling H4 element
		document.getElementById( 'isc-settings-caption-position-options-wrapper' ).previousElementSibling.classList.remove( 'hidden' );
	}
}