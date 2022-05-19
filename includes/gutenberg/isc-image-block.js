(function (wp, $) {
	"use strict";

	var assign = lodash.assign;
	var addFilter = wp.hooks.addFilter;
	var __ = wp.i18n.__;
	var createHigherOrderComponent = wp.compose.createHigherOrderComponent;
	var Fragment = wp.element.Fragment;
	var el = wp.element.createElement;

	var enableSourceControlOnBlocks = ['core/image', 'core/cover', 'core/media-text', 'core/post-featured-image'];

	var licenceList = [''];

	var isc_update_meta_field = function (id, key, value, setAttributes) {
		$.ajax({
			url: ajaxurl,
			type: 'post',
			data: {
				id: id,
				key: key,
				value: value,
				nonce: iscData.nonce,
				action: 'isc_save_meta',
			},
			complete: function () {
				setAttributes({
					saving_meta: false
				});
			}
		});
	};

	var emptyMeta = {
		'isc_image_source': '',
		'isc_image_source_url': '',
		'isc_image_source_own': false,
		'isc_image_licence': '',
	};

	var currentMetaLoading = {},
		batchTimeout   = null,
		useBatch           = true,
		batchWaitTime      = 1250,
		batchCallbacks = {};

	/**
	 * Queue an image ID and "setAttribute" function, to be called in batch in one single AJAX call.
	 *
	 * @param id
	 * @param setAttributes
	 */
	var batchLoad = function ( id, setAttributes ) {
		if ( useBatch === false ) {
			// Batch call already started, load data individually for this image.
			loadImageMeta( id, setAttributes );
			return;
		}

		if ( typeof batchTimeout === 'number' ) {
			// Re-set the timeout.
			clearTimeout( batchTimeout );
			batchTimeout = null;
		}

		// Add the setAttributes callback.
		batchCallbacks[id] = setAttributes;

		batchTimeout = setTimeout(
			function () {
				useBatch = false;
				$.ajax( {
					url:     iscData.route,
					type:    'get',
					data:    {
						_wpnonce: iscData.rest_nonce,
						ids:      Object.keys( batchCallbacks ).join( '-' )
					},
					success: function ( response ) {
						if ( typeof response.data !== 'undefined' ) {
							for ( var i in response.data ) {
								iscData.postmeta[i] = response.data[i];
								batchCallbacks[i]( formatAttributesFromAjax( response.data[i] ) );
							}
						}
					}
				} );
			},
			batchWaitTime
		);
	}

	/**
	 * Load ISC postmeta for an individual image.
	 *
	 * @param id image ID.
	 * @param setAttributes callback function to call when the AJAX call is completed.
	 */
	var loadImageMeta = function ( id, setAttributes ) {
		if ( typeof currentMetaLoading[id] !== 'undefined' ) {
			return;
		}

		if ( useBatch ) {
			// The batch call is still cooking. Queue the current image.
			batchLoad( id, setAttributes );
			return;
		}

		currentMetaLoading[id] = true;

		$.ajax( {
			url:     iscData.route,
			type:    'get',
			data:    {
				ids:      id,
				_wpnonce: iscData.rest_nonce
			},
			success: function ( response ) {
				if ( typeof response.data !== 'undefined' && typeof response.data[id] !== 'undefined' ) {
					delete ( currentMetaLoading[id] );
					iscData.postmeta[id] = response.data[id];
					setAttributes( formatAttributesFromAjax( response.data[id] ) );
				}
			}
		} );
	};

	/**
	 * Format ISC fields from AJAX to be used in "setAttributes".
	 *
	 * @param data raw postmeta data.
	 * @returns {{isc_image_source_own: (boolean|*), isc_image_licence: (string|*), isc_image_source: (string|*), isc_image_source_url: (string|*)}}
	 */
	function formatAttributesFromAjax( data ) {
		return {
			isc_image_source:     data.isc_image_source,
			isc_image_source_url: data.isc_image_source_url,
			isc_image_source_own: data.isc_image_source_own,
			isc_image_licence:    data.isc_image_licence
		};
	}

	var addSourceControlAttribute = function (settings, name) {
		if (!enableSourceControlOnBlocks.includes(name)) {
			return settings;
		}

		// Use Lodash's assign to handle if attributes are undefined
		settings.attributes = assign(settings.attributes, {
				isc_image_source: {
					type: 'string'
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
				saving_meta: {
					type: 'boolean',
				default:
					false,
				},
				isc_need_ajax: {
					type: 'boolean',
				default:
					false,
				}
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

				if ( props.name === 'core/post-featured-image' ) {
					id = wp.data.select('core/editor').getEditedPostAttribute('featured_media');
				}

				// If an image has not been selected yet, do not display the source control fields.
				if (isNaN(id) || id === 0) {
					return el(BlockEdit, props);
				}

				if ('undefined' != typeof iscData.postmeta[id]) {
					props.setAttributes({
						'isc_image_source': iscData.postmeta[id]['isc_image_source'],
						'isc_image_source_url': iscData.postmeta[id]['isc_image_source_url'],
						'isc_image_source_own': iscData.postmeta[id]['isc_image_source_own'],
						'isc_image_licence': iscData.postmeta[id]['isc_image_licence'],
					});
				} else {
					// ISC fields not yet queried. Queue it.
					props.setAttributes(emptyMeta);
					if ( typeof currentMetaLoading[id] === 'undefined' ) {
						loadImageMeta(id, props.setAttributes);
					}
				}

				var isc_image_source = props.attributes.isc_image_source;
				var isc_image_source_own = props.attributes.isc_image_source_own;
				var isc_image_source_url = props.attributes.isc_image_source_url;
				var isc_image_licence = props.attributes.isc_image_licence;
				var disabled = props.attributes.saving_meta;

				var panelFields = [el(wp.components.TextControl, {
						label: __('Image Source', 'image-source-control-isc'),
						value: isc_image_source,
						disabled: disabled,
						key: 'advadsTextImageSource',
						help: __('Include the image source here.', 'image-source-control-isc'),
						onChange: function onChange(newValue) {
							if ('undefined' == typeof iscData.postmeta[id]) {
								iscData.postmeta[id] = {};
							}
							iscData.postmeta[id]['isc_image_source'] = newValue;
							props.setAttributes({
								isc_image_source: newValue,
								isc_need_ajax: true,
							});
						},
						onBlur: function (ev) {
							if (!props.attributes.isc_need_ajax) {
								return;
							}
							isc_update_meta_field(id, 'isc_image_source', ev.target.value, props.setAttributes);
							props.setAttributes({
								isc_need_ajax: false,
								saving_meta: true,
							});
						},
					}), el(wp.components.CheckboxControl, {
						label: __('Use standard source', 'image-source-control-isc'),
						checked: isc_image_source_own,
						disabled: disabled,
						key: 'advadsCheckboxImageOwn',
						onChange: function (newValue) {
							if ('undefined' == typeof iscData.postmeta[id]) {
								iscData.postmeta[id] = {};
							}
							iscData.postmeta[id]['isc_image_source_own'] = newValue;
							props.setAttributes({
								isc_image_source_own: newValue
							});
							var metaValue = newValue ? '1' : '';
							isc_update_meta_field(id, 'isc_image_source_own', metaValue, props.setAttributes);
							props.setAttributes({
								saving_meta: true,
							});
						}
					}), el(wp.components.TextControl, {
						label: __('Image Source URL', 'image-source-control-isc'),
						value: isc_image_source_url,
						disabled: disabled,
						key: 'advadsTextSourceUrl',
						help: __('URL to link the source text to.', 'image-source-control-isc'),
						onChange: function onChange(newValue) {
							if ('undefined' == typeof iscData.postmeta[id]) {
								iscData.postmeta[id] = {};
							}
							iscData.postmeta[id]['isc_image_source_url'] = newValue;
							props.setAttributes({
								isc_image_source_url: newValue,
								isc_need_ajax: true,
							});
						},
						onBlur: function (ev) {
							if (!props.attributes.isc_need_ajax) {
								return;
							}
							isc_update_meta_field(id, 'isc_image_source_url', ev.target.value, props.setAttributes);
							props.setAttributes({
								isc_need_ajax: false,
								saving_meta: true,
							});
						},
					})];

				if (iscData.option['enable_licences']) {
					panelFields.push(el(wp.components.SelectControl, {
							label: __('Image License', 'image-source-control-isc'),
							value: isc_image_licence,
							disabled: disabled,
							key: 'advadsSelectImageLicense',
							onChange: function onChange(newValue) {
								if ('undefined' == typeof iscData.postmeta[id]) {
									iscData.postmeta[id] = {};
								}
								iscData.postmeta[id]['isc_image_licence'] = newValue;
								props.setAttributes({
									isc_image_licence: newValue,
									saving_meta: true,
								});
								isc_update_meta_field(id, 'isc_image_licence', newValue, props.setAttributes);
							},
							options: licenceList,
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
		var allLicences = iscData.option.licences.split("\n");
		for (var i in allLicences) {
			var label = allLicences[i].split('|');
			licenceList.push({
				label: label[0],
				value: label[0],
			});
		}
	});

	// Updates window.iscData.postmeta when sources are edited in the media lib modal frame before inserting an image.
	$(document).on('change', '.media-modal-content .compat-item input[name*="isc_image_source"],.media-modal-content .compat-item select[name*="isc_image_licence"]', function () {
		var id = $(this).attr('id').match(/\d+/);
		if (id) {
			id = parseInt(id);
			if ('undefined' != typeof window.iscData && 'undefined' != typeof window.iscData.postmeta) {
				window.iscData.postmeta[id] = {
					isc_image_licence: $('select[name="attachments\[' + id + '\]\[isc_image_licence\]"]').val(),
					isc_image_source: $('#attachments-' + id + '-isc_image_source').val(),
					isc_image_source_own: $('input[name="attachments\[' + id + '\]\[isc_image_source_own\]"]').prop('checked'),
					isc_image_source_url: $('#attachments-' + id + '-isc_image_source_url').val(),
				};
			}
		}
	});

})(window.wp, window.jQuery)
