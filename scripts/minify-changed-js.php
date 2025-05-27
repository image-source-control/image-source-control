<?php
/**
 * ISC - Image Source Control
 *
 * Minify JavaScript files in the plugin directory that have changed.
 * The script checks the current Git index for modified, added, or copied files.
 * Ignores files and folders mentioned in .gitignore.
 */

// path to uglifyjs binary locally
// todo: make this configurable
$uglify = '/opt/homebrew/bin/uglifyjs';

/**
 * Read and parse the .gitignore file to get patterns.
 *
 * @param string $gitignorePath Path to the .gitignore file.
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
 * Check if a file is ignored by .gitignore.
 */
function isIgnored( $filePath, $patterns, $baseDir ) {
	$relativePath = ltrim( str_replace( '\\', '/', substr( $filePath, strlen( $baseDir ) ) ), '/' );
	foreach ( $patterns as $pattern ) {
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

$changedJSFiles = [];
$output         = [];
exec( 'git diff --cached --name-only --diff-filter=ACM', $output );
foreach ( $output as $file ) {
	if ( preg_match( '/\.js$/', $file ) && ! preg_match( '/\.min\.js$/', $file ) ) {
		$changedJSFiles[] = $file;
	}
}

$baseDir   = realpath( __DIR__ . '/../' ) . '/';
$gitignore = $baseDir . '.gitignore';
$patterns  = getGitignorePatterns( $gitignore );

if ( ! file_exists( $uglify ) ) {
	echo "Error: UglifyJS not found at $uglify\n";
	exit( 1 );
}

$error = false;

foreach ( $changedJSFiles as $jsfile ) {
	if ( isIgnored( $jsfile, $patterns, $baseDir ) ) {
		// echo "IGNORED (by .gitignore): $jsfile\n";
		continue;
	}

	if ( file_exists( $jsfile ) ) {
		$minfile = preg_replace( '/\.js$/', '.min.js', $jsfile );
		if ( ! file_exists( $minfile ) || filemtime( $minfile ) < filemtime( $jsfile ) ) {
			echo "Minifying (uglifyjs): $jsfile -> $minfile\n";
			$output2 = [];
			$cmd     = $uglify . ' ' . escapeshellarg( $jsfile ) . ' -o ' . escapeshellarg( $minfile );
			exec( $cmd, $output2, $ret );
			if ( $ret !== 0 ) {
				echo "Error: UglifyJS failed for $jsfile\n";
				print_r( $output2 );
				$error = true;
			} else {
				exec( 'git add ' . escapeshellarg( $minfile ) );
			}
		}
	}
}

if ( $error ) {
	exit( 1 );
}
exit( 0 );
