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


var isc_refresh = [];
var isc_forceRefresh = function(id) {
  if(isc_refresh.indexOf(id) === -1) {
    isc_refresh.push(id);
  }
}
var isc_disableRefresh = function(id) {
  var index = isc_refresh.indexOf(id);
  isc_refresh.splice(index, 1);
}

var isc_update_meta_field = function(id, field, value, setAttributes) {
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
  .then(res=>{ setAttributes({isc_image_source_fetching: false}); })
  .catch(function() {
    setAttributes({isc_image_source_fetching: false});
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
			default: '',
		},
    isc_image_source_own: {
			type: 'boolean',
			default: false,
		},
    isc_image_source_url: {
      type: 'string',
      default: '',
    },
    isc_image_licence: {
      type: 'string',
      default: '',
    },
    isc_image_source_done: {
			type: 'boolean',
			default: true,
		},
    isc_image_source_must_refresh: {
			type: 'boolean',
			default: true,
		},
    isc_image_source_fetching: {
			type: 'boolean',
			default: false,
		}
	} );

  return settings;
};

addFilter( 'blocks.registerBlockType', 'image-source-control/attributes', isc_addSourceControlAttribute );

var isc_withSourceControl = createHigherOrderComponent(function (BlockEdit) {
  return function (props) {
    // Do nothing if it's another block than our defined ones.
    if (!isc_enableSourceControlOnBlocks.includes(props.name)) {
      return React.createElement(BlockEdit, props);
    }

    //If an image has not been selected yet, do not display the source control fields.
    if(isNaN(props.attributes.id)) {
      return React.createElement(BlockEdit, props);
    }

    props.setAttributes({
      isc_image_source_must_refresh: (isc_refresh.indexOf(props.attributes.id) > -1) ? true : false
    });

    var isc_image_source = props.attributes.isc_image_source;
    var isc_image_source_own = props.attributes.isc_image_source_own;
    var isc_image_source_url = props.attributes.isc_image_source_url;
    var isc_image_licence = props.attributes.isc_image_licence;
    var id = props.attributes.id;
    var isc_image_source_done = props.attributes.isc_image_source_done;
    var isc_image_source_must_refresh = props.attributes.isc_image_source_must_refresh;
    var isc_image_source_fetching = props.attributes.isc_image_source_fetching;

    //Add source control class to the node, in case a source is set.
    if (isc_image_source && isc_image_source.trim() !== '' && isc_image_source.trim() !== isc_loading_text) {
      props.attributes.className = "has-source-control";
    }

    //If the data has not been fetched, fetch it using the endpoints we defined in gutenberg.php
    if(isc_image_source_done && isc_image_source_must_refresh && !isc_image_source_fetching) {
      props.setAttributes({
        isc_image_source_fetching: true
      });
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
          isc_image_licence: data.isc_image_licence,
          isc_image_source_must_refresh: false,
          isc_image_source_fetching: false
        });
        isc_disableRefresh(id);
      })
      .catch(function() {
        props.setAttributes({
          isc_image_source_fetching: false
        });
        //TODO: Handle errors.
      });
    }

    //This is using native backbone client, however, it does not support catching errors:
    /*
    if(isc_image_source_done) {
      wp.api.loadPromise.done( function() {
        window.media = new wp.api.models.Media({id: id });
        window.media.fetch()
        .then(function(response) {
          props.setAttributes({
            isc_image_source: response.isc_image_source,
            isc_image_source_own: (response.isc_image_source_own == '1') ? true : false,
            isc_image_source_url: response.isc_image_source_url
          });
        });
      });
    }
    */

    BlockEdit.onClick = isc_forceRefresh(id);

    var enable_licences = (isc_options.length > 0) ? {} : { display: 'none' };
    var isFetching = (isc_image_source_fetching === true) ? true : null;
    //Extends the block by adding the source control fields.
    return React.createElement(Fragment, null, React.createElement(BlockEdit, props), React.createElement(InspectorControls, null, React.createElement(PanelBody, {
      title: __('Image Source Control', 'image-source-control-isc'),
      initialOpen: true,
    }, React.createElement(TextControl, {
      label: __('Image Source', 'image-source-control-isc'),
      disabled: isFetching,
      value: isc_image_source,
      placeholder: 'Some explanation text...',
      onChange: function onChange(isc_image_source) {
        props.setAttributes({
          isc_image_source: isc_image_source,
          isc_image_source_done: false
        });
      },
      onBlur: (e) => {
        var text = e.target.value;
        isc_update_meta_field(id, 'isc_image_source', text, props.setAttributes);
        props.setAttributes({
          isc_image_source_done: true,
          isc_image_source_fetching: true
        });
      }
    }), React.createElement(CheckboxControl, {
      label: __('This is my image', 'image-source-control-isc'),
      disabled: isFetching,
      checked: isc_image_source_own,
      onChange: function onChange(isc_image_source_own) {
        props.setAttributes({
          isc_image_source_own: isc_image_source_own,
          isc_image_source_fetching: true
        });
        isc_update_meta_field(id, 'isc_image_source_own', isc_image_source_own | 0, props.setAttributes);
      }
    }), React.createElement(TextControl, {
      label: __('Image Source URL', 'image-source-control-isc'),
      disabled: isFetching,
      value: isc_image_source_url,
      placeholder: 'Some explanation text...',
      onChange: function onChange(isc_image_source_url) {
        props.setAttributes({
          isc_image_source_url: isc_image_source_url,
          isc_image_source_done: false
        });
      },
      onBlur: (e) => {
        var text = e.target.value;
        isc_update_meta_field(id, 'isc_image_source_url', text, props.setAttributes);
        props.setAttributes({
          isc_image_source_done: true,
          isc_image_source_fetching: true
        });
      }
    }),
    React.createElement(SelectControl, {
      label: __('Image License', 'image-source-control-isc'),
      disabled: isFetching,
      value: isc_image_licence,
      onChange: function onChange(isc_image_licence) {
        isc_update_meta_field(id, 'isc_image_licence', isc_image_licence, props.setAttributes);
        props.setAttributes({
          isc_image_licence: isc_image_licence,
          isc_image_source_fetching: true
        });
      },
      options: isc_options,
      style: enable_licences
    })
  )));
  };
}, 'isc_withSourceControl');

addFilter( 'editor.BlockEdit', 'image-source-control/editor', isc_withSourceControl );
