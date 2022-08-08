( function ( wp ) {
	if ( wp.media ) {
		// Ensure that the Modal is ready.
		wp.media.view.Modal.prototype.on( 'ready', function () {
			wp.media.view.Modal.prototype.on( 'open', function () {
				wp.media.frame.state().get( 'selection' ).on( 'selection:single', function ( event ) {
					wp.data.select( 'core' ).getEntityRecord( 'postType', 'attachment', event.id );
				} );
			} );
		} );
	}

} )( window.wp );
