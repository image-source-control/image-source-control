( function ( wp ) {
	/**
	 * Extends wp.media.view.AttachmentCompat
	 * Prevent re-rendering of custom attachments fields.
	 * Fixes issue losing focus on attachment custom fields in the modal.
	 *
	 * https://core.trac.wordpress.org/ticket/40909
	 */
	const OriginalAttachmentCompat = wp.media.view.AttachmentCompat;
	let fieldsRendered             = false;
	wp.media.view.AttachmentCompat = OriginalAttachmentCompat.extend( {
		dispose: function () {
			OriginalAttachmentCompat.prototype.dispose.apply( this, arguments );
			fieldsRendered = false;
		},
		render:  function () {
			const compat = this.model.get( 'compat' );
			if ( fieldsRendered ) {
				return;
			}
			OriginalAttachmentCompat.prototype.render.apply( this, arguments );
			if ( compat && compat.item && compat.item.indexOf( 'isc_image_source' ) !== - 1 ) {
				fieldsRendered = true;
			}
		}
	} );
} )( window.wp );
