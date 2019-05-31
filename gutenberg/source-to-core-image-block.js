"use strict";

var assign = lodash.assign;
var addFilter = wp.hooks.addFilter;
var __ = wp.i18n.__;
var createHigherOrderComponent = wp.compose.createHigherOrderComponent;
var Fragment = wp.element.Fragment;
var InspectorControls = wp.editor.InspectorControls;
var PanelBody = wp.components.PanelBody;
var SelectControl = wp.components.SelectControl;
var TextControl = wp.components.TextControl;
var CheckboxControl = wp.components.CheckboxControl;
var isc_loading_text = 'Loading...';

var isc_enableSourceControlOnBlocks = [
    'core/image',
];

var isc_update_meta_field = function(id, field, value) {
  var opts = {};
  opts[field] = value;
  fetch( '/wp-json/wp/v2/media/'+id , {
    method: 'post',
    headers: {
      'Accept': 'application/json, text/plain, */*',
      'Content-Type': 'application/json',
      'X-WP-Nonce': wpApiSettings.nonce
    },
    body: JSON.stringify(opts)
  })
  .then(res=>console.log(res))
  .catch(function() {
      //TODO: Handle errors.
  });
}

var isc_addSourceControlAttribute = function( settings, name ) {
  if ( ! isc_enableSourceControlOnBlocks.includes( name ) ) {
      return settings;
  }

  // Use Lodash's assign to gracefully handle if attributes are undefined
  settings.attributes = assign( settings.attributes, {
		isc_image_source: {
			type: 'string',
			default: isc_loading_text,
		},
    isc_image_source_own: {
			type: 'boolean',
			default: false,
		},
    isc_image_source_fetched: {
			type: 'boolean',
			default: false,
		},
    isc_image_source_url: {
			type: 'string',
			default: isc_loading_text,
		},
	} );

  return settings;
};

addFilter( 'blocks.registerBlockType', 'image-source-control/attributes/source', isc_addSourceControlAttribute );

var isc_withSourceControl = createHigherOrderComponent(function (BlockEdit) {
  return function (props) {
    // Do nothing if it's another block than our defined ones.
    if (!isc_enableSourceControlOnBlocks.includes(props.name)) {
      return React.createElement(BlockEdit, props);
    }

    var isc_image_source = props.attributes.isc_image_source;
    var isc_image_source_own = props.attributes.isc_image_source_own;
    var isc_image_source_url = props.attributes.isc_image_source_url;
    var isc_image_source_fetched = props.attributes.isc_image_source_fetched;
    var id = props.attributes.id;

    //If an image has not been selected yet, do not display the source control fields.
    if(isNaN(id)) {
      return React.createElement(Fragment, null, React.createElement(BlockEdit, props));
    }

    //Add source control class to the node, in case a source is set.
    if (isc_image_source && isc_image_source.trim() !== '' && isc_image_source.trim() !== isc_loading_text) {
      props.attributes.className = "has-source-control";
    }

    //If the data has not been fetched, fetch it using the endpoints we defined in gutenberg.php
    if(!isc_image_source_fetched) {
      fetch('/wp-json/wp/v2/media/'+id, {
        headers: {
          'X-WP-Nonce': wpApiSettings.nonce
        }
      })
      .then((response) => response.json())
      .then(function(data) {
        props.setAttributes({
          isc_image_source: data.isc_image_source,
          isc_image_source_own: (data.isc_image_source_own == '1') ? true : false,
          isc_image_source_url: data.isc_image_source_url,
          isc_image_source_fetched: true
        });
      })
      .catch(function() {
          //TODO: Handle errors.
      });
    }

    //Extends the block by adding the source control fields.
    return React.createElement(Fragment, null, React.createElement(BlockEdit, props), React.createElement(InspectorControls, null, React.createElement(PanelBody, {
      title: __('My Source Control'),
      initialOpen: true
    }, React.createElement(TextControl, {
      label: __('Image Source'),
      value: isc_image_source,
      placeholder: 'Some explanation text...',
      onChange: function onChange(isc_image_source) {
        props.setAttributes({
          isc_image_source: isc_image_source
        });
      },
      onBlur: (e) => {
        var text = e.target.value;
        isc_update_meta_field(id, 'isc_image_source', text);
      }
    }), React.createElement(CheckboxControl, {
      label: "This is my image",
      checked: isc_image_source_own,
      onChange: function onChange(isc_image_source_own) {
        props.setAttributes({
          isc_image_source_own: isc_image_source_own
        });
        isc_update_meta_field(id, 'isc_image_source_own', isc_image_source_own | 0);
      }
    }), React.createElement(TextControl, {
      label: __('Image Source URL'),
      value: isc_image_source_url,
      placeholder: 'Some explanation text...',
      onChange: function onChange(isc_image_source_url) {
        props.setAttributes({
          isc_image_source_url: isc_image_source_url
        });
      },
      onBlur: (e) => {
        var text = e.target.value;
        isc_update_meta_field(id, 'isc_image_source_url', text);
      }
    }))));
  };
}, 'isc_withSourceControl');

addFilter( 'editor.BlockEdit', 'image-source-control/source-control', isc_withSourceControl );
