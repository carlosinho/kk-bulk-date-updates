# Roadmap

## Status

`v0.20` is the current implemented version.

The plugin is usable as an admin-only bulk date maintenance tool for published WordPress content. Core flows are in place: selecting posts, previewing changes, and applying bulk updates through AJAX. The major unfinished work already visible in the codebase is that `specific_date` and `random_range` are exposed in the UI but still not implemented in backend date calculation and write paths.

## Roadmap

### v0.02 - Initial working plugin

- [x] WordPress plugin bootstrap in `kk-bulk-date-updates.php`
- [x] Admin screen added under `Tools -> Bulk Date Updates`
- [x] AJAX submission flow through `wp-admin/admin-ajax.php` with `action=bduk_bulk_date_updates_action`
- [x] Nonce verification and `manage_options` capability checks
- [x] Bulk update support for `post_date` and `post_modified`
- [x] Implemented update methods:
  - [x] `add_days`
  - [x] `subtract_days`
  - [x] `match_modified_to_published`
- [x] Dry-run preview mode
- [x] Basic validation and sanitization for form input

### v0.03 - Safer targeting and clearer UX

- [x] Post status handling simplified to published content only
- [x] Post selection expanded to public post types from the admin UI, excluding attachments
- [x] Form behavior improved with conditional controls and clearer method-specific UI
- [x] Modified-date offset handling added to the UI and backend
- [x] Temporary "Recent Activity" table added to show the current operation result on the page

### v0.10 - Current release

- [x] Content filters implemented in `get_posts_to_update()`:
  - [x] no filter
  - [x] published date range
  - [x] latest/oldest count limit
  - [x] category/tag filter
- [x] Preview output shows old/new date values for matching posts, capped to the first 10 rows
- [x] Query optimization for candidate selection:
  - [x] `fields => ids`
  - [x] `no_found_rows => true`
  - [x] `update_post_meta_cache => false`
  - [x] `update_post_term_cache => false`
  - [x] `suppress_filters => true`
- [x] Direct database writes to `{$wpdb->posts}` instead of `wp_update_post()`
- [x] Batch processing implemented with 50 posts per batch
- [x] Batch prefetch implemented with one query per batch in `get_posts_batch()`
- [x] Memory/time safeguards for larger runs:
  - [x] raise `memory_limit` to `512M` for runs over 50 posts
  - [x] raise execution time limit to 300 seconds for runs over 50 posts
  - [x] reject live runs over 1000 posts when PHP memory is below `512M`
- [x] Garbage collection and batch memory cleanup between batches
- [x] Cache management:
  - [x] suspend cache additions during bulk updates
  - [x] flush object cache after completion
- [x] Rewrite flush calls removed from activation/deactivation
- [x] Performance feedback included in AJAX success responses

### v0.20 — Tightening

- [x] Potential refactor.
  - Should we refactor and improve after the originally written code, which is from last year? Can any of the plugin functionality be implemented in a more efficient way? I'm not looking for changes for the sake of them or fixing security issues that are purely hypothetical and will never happen. I'm looking for actual sub-par execution/implementation.
  - Done:
    - [x] Extract shared date resolution into `includes/class-bduk-date-resolver.php`
    - [x] Move `BDUK_Plugin` into `includes/class-bduk-plugin.php` and slim `kk-bulk-date-updates.php` down to bootstrap duties
    - [x] DRY preview and live update paths through the resolver
    - [x] Batch-fetch preview posts instead of per-post `get_post()` calls
    - [x] Normalize AJAX responses with `wp_send_json_success()` / `wp_send_json_error()`
    - [x] Fix disabled form fields not being submitted during AJAX
    - [x] Remove dead code (`create_log_entry()`, unused hook state storage, unused JS helpers)

### v0.30 — WordPress.org release
- [ ] Prep plugin for WordPress.org submission.
- [ ] Deploy to WordPress.org SVN.

### Backlog / Future
- [ ] Implement `specific_date` end to end
  - [ ] sanitize and validate the posted date/time fields
  - [ ] calculate preview values
  - [ ] write live updates
  - [ ] return correct activity log output
- [ ] Implement `random_range` end to end
  - [ ] sanitize and validate the posted range fields
  - [ ] calculate preview values
  - [ ] write live updates
  - [ ] return correct activity log output
- [ ] Add real automated tests; there are currently no tests or CI files in the repository
- [ ] Decide whether recent activity should stay ephemeral or become persisted audit history
- [ ] Refactor the main plugin class if the feature set grows; most server-side behavior currently lives in one class
- [ ] Move inline admin-page behavior out of `includes/admin/admin-page.php` if the UI keeps expanding

## Known Issues / Tech Debt

- `specific_date` and `random_range` are selectable in the form but are not implemented in backend logic
- The plugin has no persistent log table; "Recent Activity" only exists in the current AJAX response
- The plugin writes directly to `wp_posts`, which is the main performance win, but it bypasses normal `wp_update_post()` behavior
- During a live run, the plugin removes all callbacks from `save_post`, `wp_insert_post_data`, and `post_updated` for the rest of the request and does not restore them within that request
- `modified_date_offset` is sanitized with `absint()`, so negative offsets are not actually supported by the current implementation
- There are no automated tests, no CI config, and no dedicated WordPress test harness
- Most server-side logic is concentrated in `BDUK_Plugin`, which keeps the plugin simple now but will make further growth harder to manage

## Decisions Pending

- Should bulk update history remain request-local UI output, or should the plugin add a persistent audit log table?
- Should future bulk update methods continue to use direct SQL writes for speed, or should some modes opt back into standard WordPress save flows for compatibility?
- Should the plugin stay synchronous in one admin request, or should larger operations eventually move to a queued/background model?
