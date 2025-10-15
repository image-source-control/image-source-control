#!/usr/bin/env php
<?php
/**
 * Verification script for Media Trash module
 *
 * This script verifies that all Media Trash classes can be loaded
 * and that the basic structure is correct.
 */

define( 'ISCPATH', dirname( __DIR__ ) . '/' );

// Load autoloader
require_once ISCPATH . 'lib/autoload.php';

echo "=== Media Trash Module Verification ===\n\n";

// Test class loading
$classes = [
	'ISC\Media_Trash\Media_Trash',
	'ISC\Media_Trash\Media_Trash_Admin',
	'ISC\Media_Trash\Media_Trash_File_Handler',
];

$loaded = 0;
$failed = 0;

foreach ( $classes as $class ) {
	if ( class_exists( $class ) ) {
		echo "✓ {$class} loaded successfully\n";
		$loaded++;
	} else {
		echo "✗ {$class} failed to load\n";
		$failed++;
	}
}

echo "\n=== Results ===\n";
echo "Loaded: {$loaded}/{" . count( $classes ) . "}\n";
echo "Failed: {$failed}\n";

if ( $failed === 0 ) {
	echo "\n✓ All Media Trash classes loaded successfully!\n";
	exit( 0 );
} else {
	echo "\n✗ Some classes failed to load.\n";
	exit( 1 );
}
