# Chronocrow Bulk Date Updates

`Chronocrow Bulk Date Updates` is a WordPress admin plugin for changing publish and modified dates across many posts in one operation.

It exists to handle date corrections and timeline shifts without editing posts one by one. The implemented workflow is deliberately admin-only, limited to published content, and biased toward previewing before writing.

## What It Does Now

The plugin adds a screen at `Tools -> Bulk Date Updates` and lets an administrator:

- select one or more public post types from the admin UI, excluding attachments
- target only published posts
- optionally narrow the selection by:
  - published date range
  - latest or oldest N posts
  - categories and/or tags
- choose which fields to affect:
  - `post_date`
  - `post_modified`
- run one of the implemented date update methods:
  - `add_days`
  - `subtract_days`
  - `match_modified_to_published`
- preview the first 10 affected rows in test mode before writing changes
- execute the update through WordPress AJAX from the admin screen
- see a temporary "Recent Activity" table for the current page session

## Why It Exists

This codebase solves a narrow operational problem: bulk date maintenance for WordPress content while staying inside wp-admin.

The implementation is optimized for large date-only updates. Instead of calling `wp_update_post()` per post, it batches work and writes date columns directly to `wp_posts` for speed.

## Main User Flows

### Preview changes

1. Open `wp-admin/tools.php?page=chronocrow-bulk-date-updates`.
2. Choose post types, filters, date fields, and an update method.
3. Leave `Test Mode` enabled.
4. Submit the form.
5. Review the preview table. Only the first 10 matching posts are shown, even when more will be affected.

### Apply changes

1. Start from the same form.
2. Uncheck `Test Mode`.
3. Submit the form.
4. The plugin updates matching rows in `wp_posts`.
5. The page shows a success or failure message and fills the temporary activity table.

### Match modified date to published date

This method is special:

- the UI locks the date-field checkboxes
- the backend updates `post_modified` from `post_date`
- `modified_date_offset` is applied in minutes
- published date is shown in preview for context, but it is not rewritten by this method unless another method changes it

## Current Scope and Non-Features

Implemented now:

- admin page under `Tools`
- AJAX-based execution through `admin-ajax.php`
- publish-date and modified-date updates
- dry-run preview
- batch processing and direct SQL writes

Present in the UI but not implemented in backend logic:

- `specific_date`
- `random_range`

Not present in this repository:

- REST API
- WP-CLI command
- scheduled/background worker
- persistent audit log table
- custom settings screen
- plugin-specific environment variables
- build step with Composer, npm, or bundling

## Tech Stack

- PHP WordPress plugin
- WordPress admin APIs
- `WP_Query` for candidate selection
- `$wpdb` for direct updates to `wp_posts`
- jQuery-based admin JavaScript
- plain CSS for the admin page

The repository does not declare a plugin-specific PHP or WordPress minimum version in code.

## Setup

## Install

1. Put the plugin directory in `wp-content/plugins/chronocrow-bulk-date-updates`.
2. Activate `Chronocrow Bulk Date Updates` in WordPress.
3. Open `Tools -> Bulk Date Updates`.

Activation currently adds one option:

- `bduk_version`

No database table is created by the active code path.

## Relevant Admin Routes and Endpoints

Admin screen:

- `wp-admin/tools.php?page=chronocrow-bulk-date-updates`

AJAX endpoint:

- `POST wp-admin/admin-ajax.php`
- `action=bduk_bulk_date_updates_action`

Request requirements enforced by the plugin:

- authenticated WordPress admin session
- capability `manage_options`
- nonce for `bduk_bulk_date_updates_nonce`

## Project Structure

```text
chronocrow-bulk-date-updates/
├── chronocrow-bulk-date-updates.php
├── uninstall.php
├── includes/
│   ├── class-bduk-plugin.php
│   ├── class-bduk-date-resolver.php
│   └── admin/
│       └── admin-page.php
├── js/
│   └── admin.js
├── css/
│   └── admin.css
└── languages/
    └── chronocrow-bulk-date-updates.pot
```

What each file does:

- `chronocrow-bulk-date-updates.php`: plugin bootstrap, constants, dependency loading, and startup
- `uninstall.php`: guarded uninstall cleanup for plugin-owned options
- `includes/class-bduk-plugin.php`: main orchestration class for admin hooks, AJAX handling, validation, preview generation, batched writes, and lifecycle helpers
- `includes/class-bduk-date-resolver.php`: shared date calculation for preview and live updates
- `includes/admin/admin-page.php`: form markup and inline UI behavior for conditional fields
- `js/admin.js`: AJAX form submission, status messages, preview rendering, activity table rendering
- `css/admin.css`: admin layout and presentation
- `languages/chronocrow-bulk-date-updates.pot`: generated translation template for WordPress.org packaging

## Operational Rules That Matter

- only published posts are targeted
- maximum `limit_posts` accepted by the backend is `10000`
- `count_limit` filter accepts `1..1000`
- preview mode shows at most 10 rows, even when more posts match
- category/tag filtering is only useful for post types that actually use those taxonomies
- the modified-date offset is sanitized as a non-negative integer in the current implementation

## Troubleshooting

### "No posts found matching the specified criteria"

Usually means one of these:

- your post-type selection has no published posts
- the date range excludes everything
- the latest/oldest filter is narrower than expected
- you chose category/tag filtering for content that does not use those taxonomies

### Large runs fail with a memory message

For non-dry-run operations over 1000 posts, the plugin rejects the request unless PHP `memory_limit` is at least `512M`.

### Preview works but update changes nothing

If you chose `Set Specific Date (TBA)` or `Random Date Range (TBA)`, that is expected in the current codebase. Those methods are exposed in the form but are not implemented in the write logic.

### The Recent Activity table is empty after reload

That table is not persisted. It is populated from the AJAX response of the current operation only.

### Categories or tags do not appear relevant for my selection

The UI only has built-in taxonomy selectors for standard post categories and tags. Pages show a message that they do not use those taxonomies by default.

### I expected drafts or private posts to be updated

This plugin does not target them. The backend hardcodes post status to `publish`.

## Notes for Operators

- Start with `Test Mode` enabled. The plugin defaults to preview for a reason.
- For larger runs, execute during a low-traffic window because the plugin flushes object cache after completion.
- If your site depends on other code reacting to normal post-save hooks, review `ARCHITECTURE.md` before using this on very large production sites.