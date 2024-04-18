/**
 * Scripts for Settings > Image Sources
 */
jQuery( document ).ready(
	function($) {
		isc_thumbnail_input_checkstate();
		isc_caption_checkstate();
		isc_licenses_checkstate();
		isc_toggle_caption_position();
		$( '#isc-settings-overlay-enable' ).on( 'click', function(){ isc_caption_checkstate() } );
		$( '#isc-settings-licenses-enable' ).on( 'click', function(){ isc_licenses_checkstate() } );
		$( '.isc-settings-standard-source input' ).on( 'change', isc_toggle_standard_source_text );
		$( '#thumbnail-size-select, #use-thumbnail' ).on( 'change', function(){ isc_thumbnail_input_checkstate(); } );
		$( '#isc-settings-caption-style input' ).on( 'change', function(){ isc_toggle_caption_position(); } );

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
						$('#isc-signup-nl-error').removeClass('hidden').html(response.message);
					} else {
						$('#isc-signup-nl-success').removeClass('hidden').html(response.message);
					}
					$('#isc-signup-loader').addClass('hidden');
				}
			});
		});
		// close the newsletter signup box without signing up
		$('#isc_settings_section_signup .postbox-header .dashicons-no-alt').on( 'click', function() {
			$('#isc-signup-nl').prop( 'disabled', true );
			$('#isc-signup-loader').removeClass('hidden');
			$.ajax({
				type: 'POST',
				url: ajaxurl,
				data: {
					action: 'newsletter_close',
					nonce: isc.ajaxNonce,
				},
				dataType: 'json',
				success: function( response ) {
					$('#isc_settings_section_signup').remove();
				}
			});
		});

		// Add special characters to the source-pretext field by clicking on a button
		document.querySelectorAll('#source-pretext-buttons button').forEach( function( button ) {
			button.addEventListener('click', function() {
				var inputField = document.getElementById('source-pretext');
				inputField.value += this.textContent; // Use the button's content
				inputField.focus(); // Optionally, refocus on the input field
			});
		});
		// Show the buttons when the input is focused
		document.getElementById('source-pretext').addEventListener('focus', function() {
			document.getElementById('source-pretext-buttons').classList.remove('hidden');
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
 */
function isc_caption_checkstate() {
	var overlay_enabled = document.getElementById( 'isc-settings-overlay-enable' );

	if ( ! overlay_enabled ) {
		return;
	}
	var elements = document.querySelectorAll( '.isc_settings_section_overlay .form-table tr:not(:first-of-type)' );
	if ( overlay_enabled.checked ) {
		Array.prototype.forEach.call( elements, function(el, i) {
			el.style.display = 'table-row';
		} );
	} else {
		Array.prototype.forEach.call( elements, function(el, i) {
			el.style.display = 'none';
		} );
	}
}

/**
 * Toggle the state of the options in the Licenses settings
 */
function isc_licenses_checkstate() {
	var licenses_enabled = document.getElementById( 'isc-settings-licenses-enable' );

	if ( ! licenses_enabled ) {
		return;
	}
	var elements = document.querySelectorAll( '.isc_settings_section_licenses .form-table tr:not(:first-of-type)' );
	if ( licenses_enabled.checked ) {
		Array.prototype.forEach.call( elements, function(el, i) {
			el.style.display = 'table-row';
		} );
	} else {
		Array.prototype.forEach.call( elements, function(el, i) {
			el.style.display = 'none';
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
	var caption_style = document.querySelector( '#isc-settings-caption-style input:checked' );

	if ( ! caption_style ) {
		return;
	}

	if ( caption_style.value === 'none' ) {
		document.getElementById( 'isc-settings-caption-position-options-wrapper' ).classList.add( 'hidden' );
		// add class also to the sibling H4 element
		document.getElementById( 'isc-settings-caption-position-options-wrapper' ).previousElementSibling.classList.add( 'hidden' );
	} else {
		document.getElementById( 'isc-settings-caption-position-options-wrapper' ).classList.remove( 'hidden' );
		// remove class also from the sibling H4 element
		document.getElementById( 'isc-settings-caption-position-options-wrapper' ).previousElementSibling.classList.remove( 'hidden' );
	}
}