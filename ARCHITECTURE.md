# Architecture

This document describes the code that exists in this repository today.

It is intentionally developer-facing and implementation-specific. For operator usage, install steps, and troubleshooting, see `README.md`.

## System Shape

The plugin is a small admin-only WordPress plugin with one main PHP class and one admin screen.

Runtime entry points:

- plugin bootstrap file: `kk-bulk-date-updates.php`
- admin page view: `includes/admin/admin-page.php`
- AJAX client: `js/admin.js`

There is no frontend rendering path, REST controller, CLI command, background job runner, or persistent logging subsystem in the current codebase.

## Design Philosophy

The implemented design choices are straightforward:

- keep the feature inside wp-admin
- target a narrow problem: bulk date changes on published content
- bias toward safety with preview mode and capped selection counts
- optimize large writes for speed by bypassing `wp_update_post()`
- avoid introducing plugin-specific schema unless it becomes necessary

That philosophy explains most of the architecture:

- selection uses `WP_Query`
- writes go directly to `{$wpdb->posts}`
- activity output is returned in the AJAX response instead of being stored

## Boot and Lifecycle

Initialization flow:

1. WordPress loads `kk-bulk-date-updates.php`.
2. `plugins_loaded` calls `kk_bulk_date_updates_init()`.
3. `KK_Bulk_Date_Updates::get_instance()` constructs the singleton.
4. `init_hooks()` registers:
   - `init`
   - `admin_enqueue_scripts`
   - activation, deactivation, and uninstall hooks
5. On `init`, the plugin loads the text domain and then registers admin behavior only when `is_admin()` is true.

Lifecycle behavior:

- `activate()` adds `kk_bulk_date_updates_version`
- `deactivate()` clears the cron hook name `kk_bulk_date_updates_cron`
- `uninstall()` deletes `kk_bulk_date_updates_version` and legacy option `kk_bulk_date_updates_logs`

Important exception:

- the plugin clears a cron hook on deactivation, but no scheduling code exists in this repository

## Main Components

### `KK_Bulk_Date_Updates`

Everything server-side lives in one class:

- admin menu registration
- AJAX request handling
- form sanitization and validation
- post selection
- preview generation
- batched update execution
- activation/deactivation helpers

### `includes/admin/admin-page.php`

This file is both the HTML template and part of the UI controller layer because it contains inline jQuery that:

- shows and hides filter blocks
- locks date-field checkboxes for `match_modified_to_published`
- changes button text when dry-run is toggled
- adjusts taxonomy visibility for selected post types

### `js/admin.js`

This file handles:

- submit interception
- AJAX POSTs to `admin-ajax.php`
- loading state
- result messages
- preview insertion
- recent activity rendering

### `css/admin.css`

Pure admin presentation. No logic.

## Core Invariants and Rules

These are the important rules enforced by code today:

- operations are admin-only
- the required capability is `manage_options`
- the AJAX action is authenticated with a nonce
- only `publish` status is queried; the form does not expose status selection and the backend hardcodes it
- the backend defaults to `post` and `post_date` if values are missing
- `limit_posts` is capped at `10000`
- `count_limit` filter must be between `1` and `1000`
- preview mode shows at most 10 rows, regardless of total matches
- updates only occur when a computed new value differs from the stored value

Important implementation caveats:

- `modified_date_offset` is sanitized with `absint()`, so the effective supported range is non-negative, even though some comments imply negative offsets are possible
- `specific_date` and `random_range` exist in the UI but are not implemented in the date calculation logic

## Request and Data Flow

### Admin page flow

1. Admin opens `wp-admin/tools.php?page=kk-bulk-date-updates`.
2. `add_management_page()` renders the form from `includes/admin/admin-page.php`.
3. `admin_enqueue_scripts()` loads `css/admin.css` and `js/admin.js` only on this screen.
4. `wp_localize_script()` provides:
   - `ajaxUrl`
   - `nonce`
   - UI strings

### AJAX flow

1. `js/admin.js` serializes the form with `FormData`.
2. It appends:
   - `action=kk_bulk_date_updates_action`
   - `nonce=<localized nonce>`
3. WordPress routes the request to `handle_ajax_request()`.
4. The handler verifies nonce and capability.
5. `process_bulk_date_update()` sanitizes input, validates it, queries candidate post IDs, and then:
   - returns preview data for dry runs
   - or performs the live update

### Selection flow

`get_posts_to_update()` builds a `WP_Query` with:

- selected post types
- `post_status => publish`
- `posts_per_page => limit_posts`
- `orderby => date`
- `fields => ids`
- `no_found_rows => true`
- `update_post_meta_cache => false`
- `update_post_term_cache => false`
- `suppress_filters => true`

Content filters are mapped as follows:

- `none`: no additional constraint
- `date_range`: `date_query`
- `count_limit`: adjusts `posts_per_page` and `order`
- `category_tag`: builds `tax_query` over `category` and `post_tag`

Taxonomy rule worth noting:

- if both categories and tags are selected, the relation is `OR`, not `AND`

### Preview flow

`generate_preview()`:

- loads each matched post with `get_post()`
- stops after 10 preview entries
- uses `calculate_date_changes()` to build old/new values
- returns pre-rendered HTML

Preview is intentionally not a full export of all affected rows.

### Update flow

`execute_bulk_update()`:

1. raises memory/time limits for runs over 50 posts
2. disables selected WordPress hooks and suspends cache additions
3. chunks IDs into batches of 50
4. pre-fetches post rows for each batch with one SQL query
5. computes changed date values
6. writes directly to `wp_posts`
7. accumulates temporary activity rows for the response
8. runs garbage collection between batches
9. re-enables cache additions
10. flushes object cache
11. returns success/error data to the browser

## Persistence and Data Model

### WordPress tables used

The active plugin writes only to core table `wp_posts` through `$wpdb->posts`.

Columns updated:

- `post_date`
- `post_date_gmt`
- `post_modified`
- `post_modified_gmt`

Why these fields exist in this plugin:

- `post_date` and `post_date_gmt`: publish date changes
- `post_modified` and `post_modified_gmt`: modified timestamp changes, especially for the "match modified to published" flow

### WordPress options used

- `kk_bulk_date_updates_version`
- `kk_bulk_date_updates_logs` is deleted on uninstall as legacy cleanup only

### Tables that do not currently exist

`create_tables()` contains only commented example SQL for a hypothetical log table. No `dbDelta()` call runs in active code.

That means:

- there is no persistent audit history
- recent activity is request-derived UI state, not stored data

## State Transitions

At the operation level, the system behaves like this:

1. Form configured.
2. Request submitted.
3. Input sanitized.
4. Input validated.
5. Candidate IDs resolved.
6. One of two paths:
   - dry run -> preview response
   - live run -> direct DB updates
7. Response rendered in the admin UI.

Per post, the live-update path has three practical states:

- skipped because no computed value changed
- updated successfully
- failed because the post was missing from the batch result or the SQL update returned `false`

The UI does not preserve this state after page reload.

## Date Calculation Rules

Implemented methods:

- `add_days`
- `subtract_days`
- `match_modified_to_published`

Rules:

- if only `post_date` is selected, only publish date is considered
- if only `post_modified` is selected, modified date is changed from its current value plus offset
- if both are selected for add/subtract methods, modified date is recalculated from the newly computed publish date plus offset
- for `match_modified_to_published`, modified date is always derived from the current publish date plus offset

Important edge behavior:

- in preview mode, `match_modified_to_published` also shows publish date for context even when it is unchanged
- unsupported methods fall through to "return current date", which can make preview output look populated even though the live path will not write anything

## Authentication and Authorization

The plugin relies entirely on WordPress admin auth:

- screen capability: `manage_options`
- AJAX capability check: `current_user_can('manage_options')`
- AJAX nonce: `kk_bulk_date_updates_nonce`
- no `nopriv` action is registered

There is no finer-grained role model or per-post authorization layer in the plugin.

## API Architecture

The only API surface is WordPress AJAX:

- endpoint: `wp-admin/admin-ajax.php`
- action: `kk_bulk_date_updates_action`

Response shape from PHP:

- top-level `success`
- top-level `message`
- optional `data`

Current frontend caveat:

- the JS success handler prefers `response.data.message` even though the PHP success message is top-level, so success notices can fall back to the generic localized success string instead of the more specific server message

There is no REST schema, no versioned API layer, and no public endpoint.

## Performance Decisions

The performance work is the most opinionated part of the codebase.

Implemented choices:

- candidate selection uses `fields => ids`
- `WP_Query` disables row counting and cache priming
- `WP_Query` sets `suppress_filters => true`, so third-party query filters are intentionally bypassed for candidate selection
- post rows are fetched per batch with a single SQL query
- writes use direct SQL instead of `wp_update_post()`
- batches are fixed at 50 posts
- memory limit is raised to `512M` and time limit to 300 seconds for runs over 50 posts
- live runs over 1000 posts require at least `512M` memory or they are rejected
- cache additions are suspended during bulk updates
- object cache is flushed after completion
- garbage collection runs between batches

Trade-offs:

- direct SQL avoids per-post WordPress overhead
- direct SQL also bypasses normal save/update hooks, revision behavior, and plugin integrations tied to those hooks
- the plugin removes all callbacks from `save_post`, `wp_insert_post_data`, and `post_updated` during the request, and only re-enables cache additions afterward; those hooks are not restored within the same request

## Failure Handling and Edge Cases

Handled failures:

- invalid nonce
- insufficient capability
- invalid form state
- empty result set
- memory guard for large live runs
- missing post in batch fetch
- failed SQL update
- generic AJAX/network/server errors in the browser

Edge cases that matter:

- category/tag filtering is only meaningful for taxonomies actually attached to the chosen post types
- the UI hides taxonomy blocks for many post types, but the backend does not deeply validate post-type/taxonomy compatibility
- `match_modified_to_published` relies on UI behavior and backend special cases rather than a dedicated state machine
- the progress bar exists in the UI, but the backend does not stream progress updates; responses are single-shot

## Security Considerations

Current protections:

- direct file access guard with `ABSPATH`
- admin-only menu registration
- nonce verification on AJAX
- capability check on AJAX
- use of sanitization functions for posted values
- prepared SQL for updates

Security-relevant caveats:

- post types are sanitized as strings but not validated against an allowlist on the server
- `get_posts_batch()` builds the `IN (...)` clause from `intval()`-cast IDs, which is safe in practice here but not prepared in the same style as the update query
- direct SQL writes intentionally skip normal WordPress save hooks, so any security, indexing, or synchronization logic attached to those hooks will not run for these updates

## Scalability Constraints

The plugin is optimized for larger admin operations, but it still runs in one synchronous request.

Practical limits:

- no queue or resumable job support
- no chunked browser progress reporting
- no persistent job record
- full object-cache flush after completion can be expensive on larger sites
- the operation still depends on PHP request limits and database write speed

The hard stops in current code are:

- `limit_posts <= 10000`
- `count_limit <= 1000`
- `memory_limit >= 512M` for live runs over 1000 posts

## Testing and Maintenance Notes

What exists:

- no automated tests
- no CI configuration
- no fixtures or integration test harness

Maintenance implications:

- preview mode is the main safety net for operators
- changes to update methods must be wired through multiple layers:
  - form fields
  - sanitization
  - validation
  - preview calculation
  - live update calculation
  - operator-facing messaging
- because the code is concentrated in one class, new features can be added quickly but complexity will also accumulate quickly

Good candidates for future refactoring if the plugin grows:

- split query building, date calculation, and execution into separate services
- replace inline admin-page script with a dedicated JS module
- add automated integration tests against a WordPress test environment
- decide whether activity history should remain ephemeral or become real persisted audit data

## WordPress-Specific Architecture Choices

This plugin follows several explicit WordPress conventions:

- admin UI is registered with `add_management_page()` under `Tools`
- assets are only enqueued on the plugin's admin page
- internationalization is initialized with `load_plugin_textdomain()`
- filtering uses core `WP_Query`, `date_query`, and `tax_query`
- dates are normalized for GMT companion columns with `get_gmt_from_date()`
- uninstall cleanup uses the Options API

It also makes one strong non-standard WordPress choice on purpose:

- live updates bypass `wp_update_post()` and write directly with `$wpdb`

That choice is the center of the plugin's performance story and the main source of compatibility trade-offs.
