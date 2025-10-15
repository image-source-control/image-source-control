# Media Trash Module

## Overview
The Media Trash module enables WordPress Media Library trash functionality by activating MEDIA_TRASH and physically moving media files to a dedicated `isc-trash` folder inside the uploads directory. This provides a safety net for media deletions while maintaining proper file organization.

## Features
- Activates WordPress Core MEDIA_TRASH constant when enabled in settings
- Physically moves media files (including all sizes) to `isc-trash` folder when trashed
- Preserves original directory structure based on `_wp_attached_file` meta
- Restores files to original location when untrashed
- Permanently deletes files from isc-trash when deleted from trash
- Backs up and restores ISC source metadata
- Shows admin notice on Media Library pages
- Automatic 30-day retention (WordPress Core feature)

## File Structure
```
includes/Media_Trash/
├── Media_Trash.php              # Core module class
├── Media_Trash_Admin.php        # Admin hooks and ISC meta handling
└── Media_Trash_File_Handler.php # File operations (move, restore, delete)

admin/templates/
└── media-trash-notice.php       # Admin notice template

tests/wpunit/Media_Trash/
├── Media_Trash_Activation_Test.php
├── Media_Trash_Admin_Test.php
└── Media_Trash_File_Handler_Test.php
```

## Usage

### Enabling the Module
1. Go to Settings > Image Source Control > Plugin options
2. Check the "Media Trash" checkbox under Modules
3. Save settings

The MEDIA_TRASH constant is automatically defined when the module is enabled.

### Trash Workflow
1. **Trash**: Media files are moved from their original location to `isc-trash/[original-path]`
2. **Restore**: Files are moved back to their original location
3. **Permanent Delete**: Files are deleted from `isc-trash`

### Directory Structure Preservation
The original directory structure from `_wp_attached_file` is maintained:

```
Before trash:
uploads/2023/01/image.jpg
uploads/2023/01/image-150x150.jpg
uploads/2023/01/image-300x300.jpg

After trash:
uploads/isc-trash/2023/01/image.jpg
uploads/isc-trash/2023/01/image-150x150.jpg
uploads/isc-trash/2023/01/image-300x300.jpg
```

## Technical Details

### Hooks Used
- `wp_trash_post`: Triggered when attachment is trashed
  - Moves files to isc-trash
  - Backs up ISC meta data
- `untrash_post`: Triggered when attachment is restored
  - Restores files to original location
  - Restores ISC meta data
- `before_delete_post`: Triggered before permanent deletion
  - Deletes files from isc-trash
  - Cleans up ISC meta backup

### ISC Meta Backup
ISC source metadata is backed up with temporary meta fields when trashing:
- `isc_image_source` → `_isc_trash_backup_source`
- `isc_image_source_url` → `_isc_trash_backup_source_url`
- `isc_image_licence` → `_isc_trash_backup_licence`

Backup meta is restored when untrashing and deleted on permanent deletion.

### Security
The isc-trash directory includes:
- `.htaccess` file to prevent direct access
- `index.php` file to prevent directory listing

## Testing
WPUnit tests cover:
- MEDIA_TRASH activation via settings
- File movement to isc-trash with various directory structures
- Directory structure preservation
- File restoration from trash
- Permanent file deletion
- ISC meta backup and restoration
- Edge cases (missing files, permission errors)

Run tests with:
```bash
composer wpunit tests/wpunit/Media_Trash
```

## Limitations
- MEDIA_TRASH can only be defined once per WordPress installation
- File permission errors during move operations will rollback changes
- Only works with attachments (not other post types)

## Compatibility
- Requires WordPress 6.0+
- Works with year/month and custom directory structures
- Compatible with ISC Image Sources module
