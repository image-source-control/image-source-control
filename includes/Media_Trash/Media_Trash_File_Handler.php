<?php

namespace ISC\Media_Trash;

/**
 * Handle file operations for media trash
 */
class Media_Trash_File_Handler {
	/**
	 * Get the trash directory path
	 *
	 * @return string
	 */
	public static function get_trash_dir(): string {
		$upload_dir = wp_upload_dir();
		return trailingslashit( $upload_dir['basedir'] ) . 'isc-trash';
	}

	/**
	 * Ensure the trash directory exists
	 *
	 * @return bool True on success, false on failure
	 */
	public static function ensure_trash_dir_exists(): bool {
		$trash_dir = self::get_trash_dir();

		if ( ! file_exists( $trash_dir ) ) {
			if ( ! wp_mkdir_p( $trash_dir ) ) {
				return false;
			}

			// Add .htaccess to prevent direct access
			$htaccess_file = $trash_dir . '/.htaccess';
			if ( ! file_exists( $htaccess_file ) ) {
				// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
				file_put_contents( $htaccess_file, "deny from all\n" );
			}

			// Add index.php to prevent directory listing
			$index_file = $trash_dir . '/index.php';
			if ( ! file_exists( $index_file ) ) {
				// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
				file_put_contents( $index_file, "<?php\n// Silence is golden.\n" );
			}
		}

		return true;
	}

	/**
	 * Get the original file path from attachment ID
	 *
	 * @param int $attachment_id Attachment ID.
	 * @return string|false Original file path or false on failure
	 */
	public static function get_original_file_path( int $attachment_id ) {
		$file = get_post_meta( $attachment_id, '_wp_attached_file', true );
		if ( empty( $file ) ) {
			return false;
		}

		$upload_dir = wp_upload_dir();
		return trailingslashit( $upload_dir['basedir'] ) . $file;
	}

	/**
	 * Get all file paths for an attachment (including sizes)
	 *
	 * @param int $attachment_id Attachment ID.
	 * @return array Array of file paths
	 */
	public static function get_all_file_paths( int $attachment_id ): array {
		$files = [];
		$original_file = self::get_original_file_path( $attachment_id );

		if ( false === $original_file ) {
			return $files;
		}

		// Add the main file
		if ( file_exists( $original_file ) ) {
			$files[] = $original_file;
		}

		// Get metadata for image sizes
		$metadata = wp_get_attachment_metadata( $attachment_id );
		if ( empty( $metadata['sizes'] ) ) {
			return $files;
		}

		$upload_dir = wp_upload_dir();
		$file_info = pathinfo( $original_file );
		$base_dir = $file_info['dirname'];

		// Add all size variants
		foreach ( $metadata['sizes'] as $size => $size_data ) {
			if ( ! empty( $size_data['file'] ) ) {
				$size_file = trailingslashit( $base_dir ) . $size_data['file'];
				if ( file_exists( $size_file ) ) {
					$files[] = $size_file;
				}
			}
		}

		return $files;
	}

	/**
	 * Move files to trash directory
	 *
	 * @param int $attachment_id Attachment ID.
	 * @return bool True on success, false on failure
	 */
	public static function move_to_trash( int $attachment_id ): bool {
		if ( ! self::ensure_trash_dir_exists() ) {
			return false;
		}

		$files = self::get_all_file_paths( $attachment_id );
		if ( empty( $files ) ) {
			return false;
		}

		$upload_dir = wp_upload_dir();
		$basedir = trailingslashit( $upload_dir['basedir'] );
		$trash_dir = self::get_trash_dir();
		$moved_files = [];

		foreach ( $files as $file ) {
			// Get relative path from uploads directory
			$relative_path = str_replace( $basedir, '', $file );
			$trash_path = trailingslashit( $trash_dir ) . $relative_path;

			// Create directory structure in trash
			$trash_file_dir = dirname( $trash_path );
			if ( ! file_exists( $trash_file_dir ) ) {
				if ( ! wp_mkdir_p( $trash_file_dir ) ) {
					// Rollback: restore already moved files
					self::rollback_move( $moved_files, $basedir );
					return false;
				}
			}

			// Move the file
			// phpcs:ignore WordPress.WP.AlternativeFunctions.rename_rename
			if ( ! rename( $file, $trash_path ) ) {
				// Rollback: restore already moved files
				self::rollback_move( $moved_files, $basedir );
				return false;
			}

			$moved_files[] = [
				'from' => $file,
				'to'   => $trash_path,
			];
		}

		return true;
	}

	/**
	 * Restore files from trash directory
	 *
	 * @param int $attachment_id Attachment ID.
	 * @return bool True on success, false on failure
	 */
	public static function restore_from_trash( int $attachment_id ): bool {
		$file = get_post_meta( $attachment_id, '_wp_attached_file', true );
		if ( empty( $file ) ) {
			return false;
		}

		$upload_dir = wp_upload_dir();
		$basedir = trailingslashit( $upload_dir['basedir'] );
		$trash_dir = self::get_trash_dir();

		// Get the main file path in trash
		$trash_file = trailingslashit( $trash_dir ) . $file;
		if ( ! file_exists( $trash_file ) ) {
			return false;
		}

		$original_file = $basedir . $file;
		$original_dir = dirname( $original_file );

		// Ensure original directory exists
		if ( ! file_exists( $original_dir ) ) {
			if ( ! wp_mkdir_p( $original_dir ) ) {
				return false;
			}
		}

		// Restore main file
		// phpcs:ignore WordPress.WP.AlternativeFunctions.rename_rename
		if ( ! rename( $trash_file, $original_file ) ) {
			return false;
		}

		// Restore size variants
		$metadata = wp_get_attachment_metadata( $attachment_id );
		if ( ! empty( $metadata['sizes'] ) ) {
			$file_info = pathinfo( $file );
			$base_dir = ! empty( $file_info['dirname'] ) && '.' !== $file_info['dirname'] ? trailingslashit( $file_info['dirname'] ) : '';

			foreach ( $metadata['sizes'] as $size => $size_data ) {
				if ( ! empty( $size_data['file'] ) ) {
					$size_file_relative = $base_dir . $size_data['file'];
					$trash_size_file = trailingslashit( $trash_dir ) . $size_file_relative;
					$original_size_file = $basedir . $size_file_relative;

					if ( file_exists( $trash_size_file ) ) {
						// phpcs:ignore WordPress.WP.AlternativeFunctions.rename_rename
						rename( $trash_size_file, $original_size_file );
					}
				}
			}
		}

		return true;
	}

	/**
	 * Delete files from trash directory
	 *
	 * @param int $attachment_id Attachment ID.
	 * @return bool True on success, false on failure
	 */
	public static function delete_from_trash( int $attachment_id ): bool {
		$file = get_post_meta( $attachment_id, '_wp_attached_file', true );
		if ( empty( $file ) ) {
			return true; // Nothing to delete
		}

		$trash_dir = self::get_trash_dir();
		$trash_file = trailingslashit( $trash_dir ) . $file;

		// Delete main file
		if ( file_exists( $trash_file ) ) {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.unlink_unlink
			unlink( $trash_file );
		}

		// Delete size variants
		$metadata = wp_get_attachment_metadata( $attachment_id );
		if ( ! empty( $metadata['sizes'] ) ) {
			$file_info = pathinfo( $file );
			$base_dir = ! empty( $file_info['dirname'] ) && '.' !== $file_info['dirname'] ? trailingslashit( $file_info['dirname'] ) : '';

			foreach ( $metadata['sizes'] as $size => $size_data ) {
				if ( ! empty( $size_data['file'] ) ) {
					$size_file_relative = $base_dir . $size_data['file'];
					$trash_size_file = trailingslashit( $trash_dir ) . $size_file_relative;

					if ( file_exists( $trash_size_file ) ) {
						// phpcs:ignore WordPress.WP.AlternativeFunctions.unlink_unlink
						unlink( $trash_size_file );
					}
				}
			}
		}

		return true;
	}

	/**
	 * Rollback file moves on failure
	 *
	 * @param array  $moved_files Array of moved files with 'from' and 'to' keys.
	 * @param string $basedir     Base uploads directory.
	 */
	private static function rollback_move( array $moved_files, string $basedir ) {
		foreach ( $moved_files as $moved ) {
			if ( file_exists( $moved['to'] ) ) {
				// phpcs:ignore WordPress.WP.AlternativeFunctions.rename_rename
				rename( $moved['to'], $moved['from'] );
			}
		}
	}
}
