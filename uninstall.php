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
	$wpdb->delete( $wpdb->postmeta, array( 'meta_key' => 'isc_possible_usages' ), array( '%s' ) );
	$wpdb->delete( $wpdb->postmeta, array( 'meta_key' => 'isc_possible_usages_last_check' ), array( '%s' ) );

	// delete main plugin options
	delete_option( 'isc_options' );
	// delete storage
	delete_option( 'isc_storage' );
	// delete the total number of unused images (Pro)
	delete_option( 'isc_unused_images_total_items' );

	// delete user meta
	delete_metadata( 'user', null, 'isc_newsletter_subscribed', '', true );
}
