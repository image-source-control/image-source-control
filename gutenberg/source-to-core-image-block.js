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
var isc_disableRefresh = function(id) {
  if(isc_refresh.indexOf(id) > -1) {
    isc_refresh.splice(isc_refresh.indexOf(id), 1);
  }
}

var isc_update_meta_field = function(id, field, value, setAttributes) {
  var opts = {};
  opts[field] = value;
  jQuery.ajax({
   url: '/wp-json/wp/v2/media/'+id,
   type: 'POST',
   headers: {
     'Cache-Control': 'no-cache',
     'X-WP-Nonce': wpApiSettings.nonce
   },
   cache: false,
   contentType: "application/json",
   dataType: 'json',
   data: JSON.stringify(opts),
   success: function(data, textStatus, jqXHR){
     //Nothing really...
   },
   error: function(jqXHR, textStatus, errorThrown) {
     //TODO: Handle errors
   },
   complete: function(jqXHR, textStatus) {
     setAttributes({isc_fetching: false});
   }
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
    isc_force_first_time_refresh: {
			type: 'boolean',
			default: true,
		},
    isc_fetching: {
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

    var isc_image_source = props.attributes.isc_image_source;
    var isc_image_source_own = props.attributes.isc_image_source_own;
    var isc_image_source_url = props.attributes.isc_image_source_url;
    var isc_image_licence = props.attributes.isc_image_licence;
    var id = props.attributes.id;
    var isc_image_source_done = props.attributes.isc_image_source_done;
    var isc_force_first_time_refresh = props.attributes.isc_force_first_time_refresh;
    var isc_fetching = props.attributes.isc_fetching;

    //Add source control class to the node, in case a source is set.
    // if (isc_image_source && isc_image_source.trim() !== '' && isc_image_source.trim() !== isc_loading_text) {
    //   props.attributes.className = 'isc-has-source-control-'+id;
    // }

    //If the data has not been fetched, fetch it using the endpoints we defined in gutenberg.php
    //We are encapsulating this on a setTimeout to lower its dispatching priorirty.
    setTimeout(function() {
      var forceRefresh = (isc_refresh.indexOf(props.attributes.id) > -1 || isc_force_first_time_refresh);
      if(isc_image_source_done && forceRefresh && !isc_fetching) {
        props.setAttributes({
          isc_fetching: true
        });
        jQuery.ajax({
         url: '/wp-json/wp/v2/media/'+id,
         headers: {
           'Cache-Control': 'no-cache',
           'X-WP-Nonce': wpApiSettings.nonce
         },
         cache: false,
         contentType: "application/json",
         dataType: 'json',
         success: function(data, textStatus, jqXHR){
           props.setAttributes({
             isc_image_source: data.isc_image_source,
             isc_image_source_own: (data.isc_image_source_own == '1') ? true : false,
             isc_image_source_url: data.isc_image_source_url,
             isc_image_licence: data.isc_image_licence,
             isc_force_first_time_refresh: false,
           });
           isc_disableRefresh(id);
         },
         error: function(jqXHR, textStatus, errorThrown) {
           //TODO: Handle errors
         },
         complete: function(jqXHR, textStatus) {
           props.setAttributes({
             isc_fetching: false
           });
         }
       });
      }
    }, 100);

    var enable_licences = (isc_options.length > 0) ? {} : { display: 'none' };
    var isFetching = (isc_fetching === true) ? true : null;
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
        if(props.attributes.isc_image_source_done) return;
        var text = e.target.value;
        isc_update_meta_field(id, 'isc_image_source', text, props.setAttributes);
        props.setAttributes({
          isc_image_source_done: true,
          isc_fetching: true
        });
      }
    }), React.createElement(CheckboxControl, {
      label: __('This is my image', 'image-source-control-isc'),
      disabled: isFetching,
      checked: isc_image_source_own,
      onChange: function onChange(isc_image_source_own) {
        props.setAttributes({
          isc_image_source_own: isc_image_source_own,
          isc_fetching: true
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
        if(props.attributes.isc_image_source_done) return;
        var text = e.target.value;
        isc_update_meta_field(id, 'isc_image_source_url', text, props.setAttributes);
        props.setAttributes({
          isc_image_source_done: true,
          isc_fetching: true
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
          isc_fetching: true
        });
      },
      options: isc_options,
      style: enable_licences
    })
  )));
  };
}, 'isc_withSourceControl');

addFilter( 'editor.BlockEdit', 'image-source-control/editor', isc_withSourceControl );

//Force Source Control Refresh when Clicking over a CORE/Image Block.
jQuery(document).ready(function() {
  jQuery('#editor').on('click', '[class*="isc-has-source-control-"]', function() {
    var isClass = jQuery(this).attr('class').match(/isc-has-source-control-[0-9]*/g)[0];
    var imageID = parseInt(isClass.split('-')[isClass.split('-').length-1]);
    var index = isc_refresh.indexOf(imageID);
    if(index === -1) isc_refresh.push(imageID);
  });
});
