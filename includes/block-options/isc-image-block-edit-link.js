/**
 * Add a link to the media modal when Image Source Control fields for image blocks are disabled.
 *
 * @param {object} wp - The WordPress global object.
 */

( function ( wp ) {
	'use strict';

	var __                          = wp.i18n.__;
	var el                          = wp.element.createElement;
	var createHigherOrderComponent  = wp.compose.createHigherOrderComponent;
	var MediaUpload                 = wp.blockEditor.MediaUpload;
	var PanelBody                   = wp.components.PanelBody;
	var InspectorControls           = wp.blockEditor.InspectorControls;
	var enableSourceControlOnBlocks = ['core/image', 'core/cover', 'core/media-text', 'core/post-featured-image'];

	var iscWithoutSources = createHigherOrderComponent(
		function ( BlockEdit ) {
			return function ( props ) {
				// Do nothing if it's another block than our defined ones.
				if ( ! enableSourceControlOnBlocks.includes( props.name ) ) {
					return el( BlockEdit, props );
				}

				var id = props.attributes.id || props.attributes.mediaId;

				// If an image has not been selected yet, do not display the source control fields.
				if ( isNaN( id ) || id === 0 ) {
					return el( BlockEdit, props );
				}

				var selectMediaButton = el(
					MediaUpload,
					{
						onSelect:     function ( media ) {
							// nothing happens on select
						},
						allowedTypes: ['image'],
						value:        id,
						render:       function ( obj ) {
							var Tooltip = wp.components.Tooltip;
							var Button  = wp.components.Button;

							return el(
								React.Fragment,
								{},
								el(
									Tooltip,
									{
										text: el(
											React.Fragment,
											{},
											__( 'Click here to edit image source information in the media modal.', 'image-source-control-isc' ),
											el( 'br' ),
											__( 'You can also enable the block options in the plugin settings to see the image source fields below.', 'image-source-control-isc' )
										)
									},
									el(
										Button,
										{
											onClick:   obj.open,
											className: 'components-icon-button components-toolbar__control',
											label:     __( 'Edit image source', 'image-source-control-isc' )
										},
										__( 'Edit image source', 'image-source-control-isc' )
									)
								)
							);
						}
					}
				);

				var panelBody = el(
					PanelBody,
					{
						title:       'Image Source Control',
						initialOpen: true
					},
					selectMediaButton
				);

				return el(
					wp.element.Fragment,
					{},
					el( BlockEdit, props ),
					el( InspectorControls, {}, panelBody )
				);
			};
		},
		'withoutSources'
	);

	wp.hooks.addFilter( 'editor.BlockEdit', 'image-source-control/without-sources', iscWithoutSources );
} )( window.wp );