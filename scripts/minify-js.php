<?php
/**
 * ISC - Image Source Control
 *
 * Minify JavaScript files in the plugin directory.
 * Ideal for initial runs or to update all JS files in bulk.
 * Ignores files and folders mentioned in .gitignore.
 */

ini_set( 'display_errors', 1 );
ini_set( 'display_startup_errors', 1 );
error_reporting( E_ALL );
set_error_handler(
	function ( $errno, $errstr, $errfile, $errline ) {
		echo "ERROR [$errno] $errstr ($errfile:$errline)\\n";
		return false;
	}
);

// path to uglifyjs binary locally
// todo: make this configurable
$uglify = '/opt/homebrew/bin/uglifyjs';

/**
 * Get patterns from .gitignore file.
 *
 * @param string $gitignorePath Path to the .gitignore file.
 *
 * @return array
 */
function getGitignorePatterns( $gitignorePath ) {
	$patterns = [];
	if ( file_exists( $gitignorePath ) ) {
		$lines = file( $gitignorePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES );
		foreach ( $lines as $line ) {
			$line = trim( $line );
			if ( $line === '' || $line[0] === '#' ) {
				continue;
			}
			$patterns[] = ltrim( str_replace( '\\', '/', $line ), '/' );
		}
	}
	return $patterns;
}

/**
 * Is the file ignored by .gitignore?
 *
 * @param $filePath
 * @param $patterns
 * @param $baseDir
 *
 * @return bool
 */
function isIgnored( $filePath, $patterns, $baseDir ) {
	$relativePath = ltrim( str_replace( '\\', '/', substr( $filePath, strlen( $baseDir ) ) ), '/' );

	foreach ( $patterns as $pattern ) {
		// Directory ignore
		if ( substr( $pattern, -1 ) === '/' ) {
			if ( strpos( $relativePath, rtrim( $pattern, '/' ) ) === 0 ) {
				return true;
			}
		}
		if ( fnmatch( $pattern, $relativePath ) || fnmatch( $pattern, basename( $filePath ) ) ) {
			return true;
		}
		if ( strpos( $pattern, '*' ) === false && strpos( $pattern, '.' ) === false ) {
			if ( is_dir( $baseDir . $pattern ) ) {
				if ( strpos( $relativePath, $pattern . '/' ) === 0 ) {
					return true;
				}
			}
		}
	}
	return false;
}

// Check if a directory is provided as an argument, otherwise use the plugin base directory
if ( isset( $argv[1] ) ) {
	$baseDir = realpath( $argv[1] );
	if ( $baseDir === false ) {
		die( 'Error: Invalid directory given as an argument: ' . $argv[1] . PHP_EOL );
	}
	$baseDir .= '/';
} else {
	$baseDir = realpath( __DIR__ . '/../' ) . '/';
}

$gitignore = $baseDir . '.gitignore';
$patterns  = getGitignorePatterns( $gitignore );

$rii = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $baseDir, FilesystemIterator::SKIP_DOTS ) );

foreach ( $rii as $file ) {
	if ( $file->isDir() ) {
		continue;
	}
	$filepath = $file->getPathname();

	// Ignore min.js
	if ( preg_match( '/\.js$/', $filepath ) && ! preg_match( '/\.min\.js$/', $filepath ) ) {
		if ( isIgnored( $filepath, $patterns, $baseDir ) ) {
			// echo "IGNORED (by .gitignore): $filepath\n";
			continue;
		}
		$minfile = preg_replace( '/\.js$/', '.min.js', $filepath );
		// file doesn’t exist or minfile is older than original file
		if ( ! file_exists( $minfile ) || filemtime( $minfile ) < filemtime( $filepath ) ) {
			echo "Minifying (uglifyjs): $filepath -> $minfile\n";
			// execute UglifyJS in shell
			$cmd = $uglify . ' ' . escapeshellarg( $filepath ) . ' -o ' . escapeshellarg( $minfile );
			exec( $cmd, $output, $ret );
			if ( $ret !== 0 ) {
				echo "Error: UglifyJS failed for $filepath\n";
			}
		}
	}
}
