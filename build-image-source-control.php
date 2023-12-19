<?php
/**
 * Create a --no-dev distribution with composer and move the autoloader files to /lib
 */

// Define paths
$pluginComposerJson = __DIR__ . '/composer.json';

// Function to run Composer install --no-dev
function runComposerInstallNoDev( $composerJsonPath ) {
	$currentDir = getcwd();
	chdir( dirname( $composerJsonPath ) );
	//
	exec( 'composer install --no-dev --optimize-autoloader' );
	chdir( $currentDir );
}

// Function to run Composer install (with dev)
function runComposerInstallDev( $composerJsonPath ) {
	$currentDir = getcwd();
	chdir( dirname( $composerJsonPath ) );
	//
	exec( 'composer install' );
	chdir( $currentDir );
	echo "Returned back to dev setup.\n";
}

// Run Composer with --no-dev
runComposerInstallNoDev( $pluginComposerJson );

// Function to create directory if it doesn't exist
function createDirectoryIfNotExists( $dir ) {
	if ( ! is_dir( $dir ) ) {
		if ( ! mkdir( $dir, 0755, true ) ) {
			error_log( "Failed to create directory: $dir" );
			exit( "Failed to create directory: $dir" );
		}
	}
}

// Create /lib and /lib/composer directories
createDirectoryIfNotExists( __DIR__ . '/lib' );
createDirectoryIfNotExists( __DIR__ . '/lib/composer' );

$srcAutoload = __DIR__ . '/vendor/autoload.php';

// Copy the main autoloader file
if ( ! file_exists( $srcAutoload ) || ! copy( $srcAutoload, __DIR__ . '/lib/autoload.php' ) ) {
	error_log( "Failed to copy autoloader.php file" );
	exit( "Failed to copy autoloader file." );
}

// Begin to copy all files from the composer directory
$srcComposerDir  = __DIR__ . '/vendor/composer';
$destComposerDir = __DIR__ . '/lib/composer';
$dir             = opendir( $srcComposerDir );
@mkdir( $destComposerDir );

$blacklist = [ 'installed.php' ];  // Files to not copy
while ( false !== ( $file = readdir( $dir ) ) ) {
	if ( $file != '.' && $file != '..' && ! in_array( $file, $blacklist ) ) {
		copy( $srcComposerDir . '/' . $file, $destComposerDir . '/' . $file );
	}
}

closedir( $dir );

echo "Essential autoloader files successfully copied to /lib.\n";

// Run Composer without --no-dev
runComposerInstallDev( $pluginComposerJson );