/**
 * Scripts for Media > Image Sources
 */
jQuery( document ).ready(
	function($) {
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
