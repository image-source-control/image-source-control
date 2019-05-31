<?php

function isc_addSourceToCoreImageBlock() {
  wp_enqueue_script(
    'source-to-core-image-block',
    plugin_dir_url(__FILE__) . 'source-to-core-image-block.js',
    array('wp-api', 'lodash', 'wp-blocks', 'wp-editor', 'wp-element', 'wp-i18n',),
    true
  );
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
}

//Function to get a meta field, otherwise it will return a null value
function isc_get_attachment_meta($object, $field_name, $request) {
  return get_post_meta($object['id'], $field_name, true);
}
//Function to get a meta field, otherwise it becomes read only.
function slug_update_attachment_meta($value, $object, $field_name ) {
  return update_post_meta($object->ID, $field_name, $value);
}

add_action('rest_api_init', 'isc_register_api_fields');
add_action('enqueue_block_editor_assets', 'isc_addSourceToCoreImageBlock');
