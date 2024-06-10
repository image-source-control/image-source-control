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
		// clear frontend storage
		$( '#isc-clear-storage' ).on(
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
							action: 'isc-clear-storage',
							nonce: isc.ajaxNonce,
						},
						success:function(data, textStatus, XMLHttpRequest){
							// display return messages
							$( '#isc-clear-storage-feedback' ).html( data );
							button.disabled = false;
						},
						error: function(MLHttpRequest, textStatus, errorThrown){
							$( '#isc-clear-storage-feedback' ).html( errorThrown );
							button.disabled = false;
						}
					}
				);
			}
		);

		/**
		 * Handle image-post index removal
		 */
		// Select the parent element that exists when the page loads since the button is added dynamically
		const isc_image_posts_index_list = document.querySelector('#isc-image-post-relations');

		isc_image_posts_index_list.addEventListener('click', function(event) {
			if (!event.target || !event.target.matches('.isc-button-delete-image-posts-index')) {
				return;
			}

			event.preventDefault();
			const el = event.target;
			var image_id = el.dataset.imageId;
			var request = new XMLHttpRequest();

			el.style.display = 'none';

			request.open('POST', ajaxurl, true);
			request.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded; charset=UTF-8');

			// Remove the row on success
			request.onload = function() {
				if (this.status >= 200 && this.status < 400) {
					el.closest('tr').remove();
				}
			};

			request.send('action=isc-clear-image-posts-index&nonce=' + isc.ajaxNonce + '&image_id=' + image_id);
		});

		/**
		 * Handle post-images index removal
		 */
		// Select the parent element that exists when the page loads since the button is added dynamically
		const isc_post_images_index_list = document.querySelector('#isc-post-image-relations');

		isc_post_images_index_list.addEventListener('click', function(event) {
			if (!event.target || !event.target.matches('.isc-button-delete-post-images-index')) {
				return;
			}

			event.preventDefault();
			const el = event.target;
			var post_id = el.dataset.iscPostId;
			var request = new XMLHttpRequest();

			el.style.display = 'none';

			request.open('POST', ajaxurl, true);
			request.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded; charset=UTF-8');

			// Remove the row on success
			request.onload = function() {
				if (this.status >= 200 && this.status < 400) {
					el.closest('tr').remove();
				}
			};

			request.send('action=isc-clear-post-images-index&nonce=' + isc.ajaxNonce + '&post_id=' + post_id);
		});
	} );