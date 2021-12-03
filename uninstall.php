<?php
if ( ! defined( 'ABSPATH' ) && ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit();
}

// check if the option to remove data on uninstall is enabled
$options = get_option( 'isc_options' );
if ( ! empty( $options['remove_on_uninstall'] ) ) {
	global $wpdb;

	// delete post meta fields
	$wpdb->delete( $wpdb->postmeta, array( 'meta_key' => 'isc_post_images' ), array( '%s' ) );
	$wpdb->delete( $wpdb->postmeta, array( 'meta_key' => 'isc_image_posts' ), array( '%s' ) );
	$wpdb->delete( $wpdb->postmeta, array( 'meta_key' => 'isc_image_source' ), array( '%s' ) );
	$wpdb->delete( $wpdb->postmeta, array( 'meta_key' => 'isc_image_source_own' ), array( '%s' ) );
	$wpdb->delete( $wpdb->postmeta, array( 'meta_key' => 'isc_image_source_url' ), array( '%s' ) );

	// delete main plugin options
	delete_option( 'isc_options' );
	// delete storage
	delete_option( 'isc_storage' );
}
