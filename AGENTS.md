# Agent Instructions for Image Source ControlPro

## Project Overview

This repository contains the base (free) version of the Image Source Control WordPress plugin. The plugin helps website owners manage image sources efficiently by tracking, displaying, and managing image attribution and credits. The Pro version also adds a feature set to look for image appearances with the aim for users to scan for and handle unused images.

There is a premium version of the plugin, called Image Source Control Pro, which extends the functionality of the base plugin with additional features and capabilities.
The Pro code from the [image-source-control](https://github.com/image-source-control/image-source-control-pro) repository just goes into the `pro` subfolder in the root directory and should then work out of the box.

## Technology Stack

- **Language:** mainly PHP
- **Framework:** WordPress Plugin API
- **Package Manager:** Composer (with `vendor` directory. For production, relevant parts of that directory are moved to the `lib/` directory)
- **Text domain:** `image-source-control-isc`
- **File Structure:**
  - `admin/` - Admin interface and backend functionality
  - `public/` - Frontend display and public-facing functionality
  - `includes/` - Core classes and module-based logic
  - `vendor/` - Composer dependencies (not in production)
  - `lib/` - Production-ready libraries (from Composer dependencies)
  - `scripts/` - Build and utility scripts
  - `tests/` - Unit and integration tests

## General Rules

- Don't change the indentation of lines that are otherwise unchanged.
- No single-use variables.
- Do not make changes to the files and directories listed in .gitignore.
- Make the smallest possible changes to accomplish the task.
- Don't remove or modify working code unnecessarily.
- Match existing code style and patterns.
- Update documentation only when directly related to your changes.
- Write all comments in English.

## Coding Standards

- Tabs should be used at the beginning of the line for indentation, while spaces can be used mid-line for alignment.
- Use meaningful, descriptive variable names.
- Add inline comments only when necessary to explain complex logic.
- Match the existing comment style in the file.
- Follow WordPress coding standards for PHP.
- When linking out of the WordPress backend, e.g., to our homepage or manual, use `target="_blank"`. Don't use it for links within the WordPress backend.

## Strings and Translations

- Text domain: `image-source-control-isc`
- Do not create or update .po and .mo files
- Reuse existing strings wherever possible. A lower number of new strings help to keep the translation effort low. When you add new strings, add a section '## Translations' to your messages (pull request or commit) that explains, which existing strings you considered and why you didn't use them.
- When writing longer texts consisting of multiple sentences, consider splitting them up into individually translatable sentences, so that changes to one sentence don't trigger new translations of existing sentences.
- Use WordPress translation functions: `__()`, `_e()`, `_n()`, etc. with text domain `image-source-control-isc`

## File Management

- Do not modify files listed in `.gitignore`:
- When changing existing .js files that have a minified version (marked with `.min`), simply run the `php bin/minify.php path/to/file.js` helper from the base repo to automatically generate the updated minified file. You do not need to manually create or edit the minified file.
  Do not modify `lib/` or Composer files (`composer.json`, `composer.lock`) unless the current task explicitly requires it. Unrequested updates to `lib/` or Composer often introduce large, unnecessary diffs and complicate reviews. If a dependency or `lib/` change is truly necessary, document the reason in the Issue/PR and include the change in a separate, clearly labeled commit.

## Development Setup

1. Clone this repository
2. Run `composer install` to set up autoloading

## WordPress-Specific Guidelines

- Utilize WordPress APIs (Options API, Settings API, etc.)
- Utilize WordPress APIs (Functions, Options API, Settings API, etc.)
- Don’t write a custom function or lines of code if a WordPress core function already exists for that purpose
- Follow WordPress security best practices (nonces, sanitization, escaping)
- Use WordPress hooks and filters for extensibility
- Respect WordPress loading order (plugins_loaded, init, admin_init, etc.)

## Testing

All tests are in the `tests/` folder. Feel free to update or create tests as needed.

Tests are written using "wp browser", which uses Codeception under the hood.

### Additional instructions:

- never use specific IDs, e.g., when creating a post for a test. Use dynamic ones
- use snake case for method names
- never create class variables that are never used or only written, but never read
- I have three image file defined, if you need any (e.g., by using codecept_data_dir( 'test-image1.jpg' ): test-image1.jpg, test-image2.jpg, test-image3.jpg
- add proper method and class documentation
- mention the tested method in the docblock of the test method
- if a method you write a test for calls a method that already has tests, you don't need to retest that behavior again
- on the other hand, when a minor or private method is already indirectly tested by a bigger method, you don't need to add a separate test for the minor method

### Types of tests

Before writing a new test class, and if not instructed by me, discuss, which type of test would suite the use case best. Here is the overview:

1. WPUnit Tests (e.g., WPTestCase): Use methods that directly interact with WordPress functions, classes, and the database, such as factory(), go_to(), do_action(), apply_filters(), and standard PHPUnit assertions (assertTrue, assertEquals). These methods allow you to test internal logic, hooks, and database changes in isolation, benefiting from wp-browser's transactional database and global state reset for a clean slate per test. You'll primarily be calling your plugin's PHP methods and asserting their return values or side effects on the WordPress environment.
2. For Functional Tests: Leverage methods that simulate HTTP requests and inspect the server's response, like amOnPage(), sendAjaxPostRequest(), sendPost(), seeResponseCodeIs(), and seeInSource(). These are ideal for testing routes, REST API endpoints, shortcode outputs, or form submissions without the overhead of a full browser, ensuring your server-side logic and WordPress integration work correctly. You're essentially acting as a "headless browser" at the PHP level.
3. For Acceptance Tests (using WPWebDriver): Employ methods that simulate real user interactions in a browser, such as amOnPage(), click(), fillField(), see(), seeElement(), and makeScreenshot(). These are best for verifying frontend functionality, JavaScript interactions, visual elements, and complete user flows, ensuring the entire application behaves as expected from an end-user perspective.

Here are additional instructions for each of them:

#### WPUNIT Tests:

- the namespace starts with ISC\Tests\WPUnit\
- always add: use ISC\Tests\WPUnit\WPTestCase;
- always extend from WPTestCase
- remember, that var_dump doesn't work to debug something. Use the following instead:
  file_put_contents( WP_CONTENT_DIR . '/test.log', print_r( $value, true ), true ) . "\n", FILE_APPEND );
- 
  **tearDown() Method:**
- **IMPORTANT:** Always call `parent::tearDown()` at the **beginning** of your tearDown() method, not at the end
- This ensures WordPress core cleanup (posts, attachments, transactions) happens before your custom cleanup
- After `parent::tearDown()`, clean up custom tables, caches, options, and other non-standard WordPress data

Example:
```php
public function tearDown(): void {
    // FIRST: Parent cleanup
    parent::tearDown();
    
    // THEN: Your custom cleanup
    $this->index_table->clear_all();
    Index_Table::reset_oldest_entry_date_cache();
    delete_option('test_option');
}
```

#### FUNCTIONAL Tests:

**IMPORTANT: In Functional Tests, you MUST NOT use WordPress core functions directly (like `get_post_meta()`, `update_post_meta()`, `wp_get_attachment_url()`, `get_permalink()`, etc.). Use Codeception's database methods instead.**

- the namespace starts with ISC\Tests\Functional\
- for debugging, using xdebug works

##### Database Operations in Functional Tests:

**Creating Data:**
- `$I->havePostInDatabase(['post_name' => 'slug', 'post_title' => 'Title', 'post_status' => 'publish'])` - Create posts
- `$I->havePostmetaInDatabase($post_id, 'meta_key', 'value')` - Add post meta
- `$I->haveOptionInDatabase('option_name', $value)` - Add options
- For attachments: Use `$I->havePostInDatabase(['post_type' => 'attachment', 'guid' => 'https://example.com/image.jpg'])`

**Reading Data:**
- `$I->grabPostMetaFromDatabase($post_id, 'meta_key')` - Get post meta value
- `$I->grabOptionFromDatabase('option_name')` - Get option value
- `$I->grabFromDatabase('table_name', 'column', ['where' => 'value'])` - Get specific value

**Updating Data:**
- `$I->updateInDatabase('table_name', ['column' => 'new_value'], ['id' => $id])` - Update records

**Assertions:**
- `$I->seeInDatabase('table_name', ['column' => 'value'])` - Assert record exists
- `$I->dontSeeInDatabase('table_name', ['column' => 'value'])` - Assert record doesn't exist
- `$I->seePostMetaInDatabase(['post_id' => $id, 'meta_key' => 'key', 'meta_value' => 'value'])` - Assert post meta
- `$I->dontSeePostMetaInDatabase(['post_id' => $id, 'meta_key' => 'key'])` - Assert post meta doesn't exist

**Deleting Data:**
- `$I->dontHavePostMetaInDatabase(['post_id' => $id, 'meta_key' => 'key'])` - Remove post meta

##### Navigation in Functional Tests:

- Use explicit slugs when creating posts: `'post_name' => 'my-test-post'`
- Navigate using slugs: `$I->amOnPage('/my-test-post')`
- Never use `get_permalink()` or other WordPress functions

##### Constants and Timing:

- WordPress constants like `DAY_IN_SECONDS` are not available - define your own: `const DAY_IN_SECONDS = 86400;`
- When testing timestamp updates, add `sleep(1)` between setting a timestamp and checking if it changed, to ensure `time()` returns a different value

##### Common Patterns:

- Store frequently used values (like table names) as class constants for reusability
- Use `_before()` method to set up common test data or options
- To test "removal" of data: Create and index it first, then modify and re-index to verify deletion
- For multi-step tests: You can make multiple page visits and modify database between them

Access to WordPress functions like add_filter() or get_post() is not possible, unless I install WPLoader, which I haven't done yet, but would be open to if we need this more often.
A possibility to execute WordPress code, like setting a constant or a filter, is a dynamic MU plugin.
Here is an example to do so that goes into the `_before()` method:

```php
$this->muPluginPath = codecept_root_dir( '../../../wp-content/mu-plugins/mu-plugin-add-footer.php' );
if ( ! file_exists( $this->muPluginPath ) ) {
	file_put_contents( $this->muPluginPath, '<?php add_action("wp_footer", function() { echo "<img src=\'https://example.com/test-image-outside.jpg\' alt=\'Test Image Outside\' />"; });' );
}
```

The MU plugin then needs to be removed in the `_after()` method:

```php
public function _after(\AcceptanceTester $I) {
	// Delete the mu-plugin after the test
	if ( file_exists( $this->muPluginPath ) ) {
		unlink( $this->muPluginPath );
	}
}
```

#### ACCEPTANCE Tests:

- the namespace starts with ISC\Tests\Acceptance\
- for debugging, using xdebug works
- don't add a _after() method if there is nothing to clean up
- the syntax of $I->see is: $I->see( $content, $selector ); – you have previously confused both

### Running Tests

```bash
# Run all tests
vendor/bin/codecept run

# Run specific suite
vendor/bin/codecept run wpunit
vendor/bin/codecept run functional
vendor/bin/codecept run acceptance

# composer-based shorthands to run tests
composer wpunit
composer functional
composer acceptance

# Run specific test file
vendor/bin/codecept run functional tests/functional/pro/Indexer_Public_Cest.php

# Rebuild test classes after config changes (e.g., enabling WPFilesystem module)
vendor/bin/codecept build
```

## Common Tasks

### Modifying Settings
- Settings templates are in `admin/templates/settings/`
- Follow existing patterns for UI consistency

### Adding Translations
- Reuse existing strings when possible
- Document new strings in PR description with "## Translations" section

### Pull Requests

#### Updating Pull Requests

When updating a PR after reviews or task changes:
- Keep the original PR description as the main summary; though change outdated parts if necessary

### Admin Notices

The plugin is clearing all notices set with `admin_notices` on ISC admin pages
To add a notice to these pages, use the `isc_admin_notices` action hook.