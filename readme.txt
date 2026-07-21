=== Chronocrow Bulk Date Updates ===
Contributors: karol-k
Tags: dates, bulk edit, posts, admin, maintenance
Requires at least: 6.0
Tested up to: 7.0.2
Requires PHP: 7.4
Stable tag: 0.31
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Bulk update published and modified dates across many WordPress posts from a single admin screen, with preview mode before applying changes.

== Description ==

Chronocrow Bulk Date Updates adds an admin-only screen under Tools for changing dates across many posts in one operation.

The plugin is built for timeline corrections and other date maintenance tasks where editing posts one by one would be too slow. It stays inside wp-admin, targets published content only, and defaults to preview mode so you can inspect the first affected rows before writing changes.

Features:

* Select one or more public post types, excluding attachments.
* Target published posts only.
* Filter by published date range, latest or oldest posts, or categories and tags.
* Update `post_date`, `post_modified`, or both together.
* Use the implemented date methods:
* Add days.
* Subtract days.
* Match modified date to published date with an optional minute offset.
* Preview the first matching rows before applying changes.
* Execute updates through the WordPress admin with nonce and capability checks.

Important notes:

* The plugin writes directly to the `wp_posts` date columns for performance.
* Standard post-save hooks do not run for these direct SQL updates.

== Installation ==

1. Upload the plugin folder to `/wp-content/plugins/`, or install it through WordPress if a package is available.
2. Activate `Chronocrow Bulk Date Updates` in the Plugins screen.
3. Open `Tools > Bulk Date Updates`.
4. Leave `Test Mode` enabled for the first run, review the preview, then apply changes when ready.

== Frequently Asked Questions ==

= Which content can the plugin update? =

The plugin only targets published posts from public post types selected in the admin form. Attachments are excluded, and drafts, pending posts, and private posts are not updated.

= What does preview mode show? =

Preview mode returns the first matching posts that would actually change, including their current and new dates. It is intended as a quick safety check before running a live update.

= Does the plugin use wp_update_post()? =

No. For performance, it writes the date columns directly in `wp_posts`. That is faster for large operations, but code attached to normal post-save hooks will not run for these updates.

== Changelog ==

= 0.30 =

* Rebrand the plugin as Chronocrow Bulk Date Updates.

= 0.20 =

* Extract shared date resolution into a dedicated resolver class.
* Unify preview and live update date calculations.
* Improve preview batching and AJAX response handling.
* Fix disabled form fields in AJAX submissions.
* Tighten plugin-owned identifiers for WordPress.org readiness.
