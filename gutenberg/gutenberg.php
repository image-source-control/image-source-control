<?php

function isc_licences_text_to_array($licences = '') {
  if($licences == '') return false;
  // split the text by line
  $licences_array = preg_split('/\r?\n/', trim($licences));
  if(count($licences_array) == 0 ) return false;
  // create the array with licence => url
  $new_licences = array();
  foreach($licences_array as $_licence) {
      if(trim($_licence) != '') {
          $temp = explode('|', $_licence);
          $new_licences[$temp[0]] = array();
          if( isset($temp[1]))
              $new_licences[$temp[0]]['url'] = esc_url ($temp[1]);
      }
  }

  if($new_licences == array()) return false;
  else return $new_licences;
}

function isc_addSourceToCoreImageBlock() {
  wp_enqueue_script(
    'source-to-core-image-block',
    plugin_dir_url(__FILE__) . 'source-to-core-image-block.js',
    array('wp-api', 'lodash', 'wp-blocks', 'wp-editor', 'wp-element', 'wp-i18n',),
    true
  );

  //Reading the options and making them avaialable for our Gutenberg block
  $options = get_option('isc_options');
  $licenses_js_array = array();
  if($options['enable_licences'] && $licences = isc_licences_text_to_array($options['licences'])) {
    $licenses_js_array[] = array('value' => '', 'label' => '--');
    foreach($licences as $_licence_name => $_licence_data) {
      $licenses_js_array[] = array('value' => $_licence_name, 'label' => $_licence_name);
    }
  }
  wp_localize_script('source-to-core-image-block', 'isc_options', $licenses_js_array );
  if(function_exists('wp_set_script_translations')) {
    wp_set_script_translations('image-source-control-isc', 'image-source-control-isc');
  }
}

//Making our custom meta fields available to wp rest api.
function isc_register_api_fields() {
 register_rest_field( 'attachment', 'isc_image_source',
    array(
      'get_callback'    => 'isc_get_attachment_meta',
      'update_callback' => 'slug_update_attachment_meta',
      'schema'          => null,
      'auth_callback' => function(){
        return current_user_can('edit_posts');
       }
    )
 );
 register_rest_field( 'attachment', 'isc_image_source_own',
    array(
       'get_callback'    => 'isc_get_attachment_meta',
       'update_callback' => 'slug_update_attachment_meta',
       'schema'          => null,
       'auth_callback' => function(){
         return current_user_can('edit_posts');
        }
    )
 );
 register_rest_field( 'attachment', 'isc_image_source_url',
    array(
       'get_callback'    => 'isc_get_attachment_meta',
       'update_callback' => 'slug_update_attachment_meta',
       'schema'          => null,
       'auth_callback' => function(){
         return current_user_can('edit_posts');
        }
    )
 );
 register_rest_field( 'attachment', 'isc_image_licence',
    array(
       'get_callback'    => 'isc_get_attachment_meta',
       'update_callback' => 'slug_update_attachment_meta',
       'schema'          => null,
       'auth_callback' => function(){
         return current_user_can('edit_posts');
        }
    )
 );
}

//Function to get a meta field, otherwise it will return a null value
function isc_get_attachment_meta($object, $field_name, $request) {
  return get_post_meta($object['id'], $field_name, true);
}
//Function to get a meta field, otherwise it becomes read only.
function slug_update_attachment_meta($value, $object, $field_name ) {
  return update_post_meta($object->ID, $field_name, $value);
}

add_action('init', function() { wp_enqueue_script( 'wp-api' ); });
add_action('rest_api_init', 'isc_register_api_fields');
add_action('enqueue_block_editor_assets', 'isc_addSourceToCoreImageBlock');
