(function (wp, $) {
	"use strict";

	var assign = lodash.assign;
	var addFilter = wp.hooks.addFilter;
	var __ = wp.i18n.__;
	var createHigherOrderComponent = wp.compose.createHigherOrderComponent;
	var Fragment = wp.element.Fragment;
	var el = wp.element.createElement;

	var enableSourceControlOnBlocks = ['core/image', 'core/cover', 'core/media-text', 'core/post-featured-image', 'generateblocks/image'];

	var licenceList = [''];

	var addSourceControlAttribute = function (settings, name) {
		if (!enableSourceControlOnBlocks.includes(name)) {
			return settings;
		}

		// Use Lodash's assign to handle if attributes are undefined
		settings.attributes = assign(settings.attributes, {
				isc_image_source: {
					type: 'string',
				},
				isc_image_source_own: {
					type: 'boolean'
				},
				isc_image_source_url: {
					type: 'string'
				},
				isc_image_licence: {
					type: 'string'
				},
			});

		return settings;
	};

	addFilter('blocks.registerBlockType', 'image-source-control/attributes', addSourceControlAttribute);

	var iscWithSourceControl = createHigherOrderComponent(function (BlockEdit) {
			return function (props) {
				// Do nothing if it's another block than our defined ones.
				if (!enableSourceControlOnBlocks.includes(props.name)) {
					return el(BlockEdit, props);
				}

				var id = props.attributes.id || props.attributes.mediaId;

				if ( ( props.name === 'core/cover' && props.attributes.useFeaturedImage === true ) ||  props.name === 'core/post-featured-image' ) {
					id = wp.data.select('core/editor').getEditedPostAttribute('featured_media');
				}

				// If an image has not been selected yet, do not display the source control fields.
				if (isNaN(id) || id === 0) {
					return el(BlockEdit, props);
				}

				if ( ! wp.data.select( 'core' ).getEntityRecord( 'postType', 'attachment', id ) ) {
					return el( BlockEdit, props );
				}

				var imageMeta = wp.data.select( 'core' ).getEntityRecord( 'postType', 'attachment', id ).meta;

				props.attributes.isc_image_source = imageMeta.isc_image_source;
				props.attributes.isc_image_source_own =  imageMeta.isc_image_source_own;
				props.attributes.isc_image_source_url =  imageMeta.isc_image_source_url;
				props.attributes.isc_image_licence =  imageMeta.isc_image_licence;

				var panelFields = [el(wp.components.TextControl, {
						label: __('Image Source', 'image-source-control-isc'),
						value: props.attributes.isc_image_source,
						key: 'advadsTextImageSource',
						help: __('Include the image source here.', 'image-source-control-isc'),
						onChange: function onChange(newValue) {
							imageMeta.isc_image_source = newValue
							wp.data.dispatch( 'core' ).editEntityRecord( 'postType', 'attachment', id, { meta: imageMeta } );
							wp.data.dispatch( 'core' ).saveEditedEntityRecord( 'postType', 'attachment', id );
							props.setAttributes({
								isc_image_source: newValue,
							});
						},
					}), el(wp.components.CheckboxControl, {
						label: __('Use standard source', 'image-source-control-isc'),
						checked: props.attributes.isc_image_source_own,
						key: 'advadsCheckboxImageOwn',
						onChange: function (newValue) {
							imageMeta.isc_image_source_own = newValue
							wp.data.dispatch( 'core' ).editEntityRecord( 'postType', 'attachment', id, { meta: imageMeta } );
							wp.data.dispatch( 'core' ).saveEditedEntityRecord( 'postType', 'attachment', id );
							props.setAttributes({
								isc_image_source_own: newValue,
							});
						}
					}), el(wp.components.TextControl, {
						label: __('Image Source URL', 'image-source-control-isc'),
						value: props.attributes.isc_image_source_url,
						key: 'advadsTextSourceUrl',
						help: __('URL to link the source text to.', 'image-source-control-isc'),
						onChange: function onChange(newValue) {
							imageMeta.isc_image_source_url = newValue
							wp.data.dispatch( 'core' ).editEntityRecord( 'postType', 'attachment', id, { meta: imageMeta } );
							wp.data.dispatch( 'core' ).saveEditedEntityRecord( 'postType', 'attachment', id );
							props.setAttributes({
								isc_image_source_url: newValue,
							});
						},
					})];

				if (iscData.option['enable_licences']) {
					panelFields.push(el(wp.components.SelectControl, {
							label: __('Image License', 'image-source-control-isc'),
							value: props.attributes.isc_image_licence,
							options: licenceList,
							key: 'advadsSelectImageLicense',
							onChange: function onChange(newValue) {
								imageMeta.isc_image_licence = newValue
								wp.data.dispatch( 'core' ).editEntityRecord( 'postType', 'attachment', id, { meta: imageMeta } );
								wp.data.dispatch( 'core' ).saveEditedEntityRecord( 'postType', 'attachment', id );
								props.setAttributes({
									isc_image_licence: newValue,
								});
							},
						}));
				}

				// Extends the block by adding the source control fields.
				return el(Fragment, null, el(BlockEdit, props), el(wp.blockEditor.InspectorControls, null, el(wp.components.PanelBody, {
							title: __('Image Source Control', 'image-source-control-isc'),
							initialOpen: true,
						}, panelFields)));

			};
		}, 'iscWithSourceControl');

	addFilter('editor.BlockEdit', 'image-source-control/editor', iscWithSourceControl);

	$(function () {
		var allLicences = iscData.option.licences.replace(/[\r]/g, '').split("\n");
		for (var i in allLicences) {
			var label = allLicences[i].split('|');
			licenceList.push({
				label: label[0],
				value: label[0],
			});
		}
	});

})(window.wp, window.jQuery)
