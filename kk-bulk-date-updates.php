<?php
/**
 * Plugin Name: Bulk Date Updates by KK
 * Description: A WordPress plugin for bulk updating dates across posts and pages.
 * Version: 0.22
 * Author: Karol K
 * Author URI: https://wpwork.shop/
 * License: GPL v2 or later
 * Text Domain: kk-bulk-date-updates
 * Domain Path: /languages
 */

// Prevent direct access.
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants.
define('BDUK_VERSION', '0.22');
define('BDUK_PLUGIN_FILE', __FILE__);
define('BDUK_PLUGIN_URL', plugin_dir_url(__FILE__));
define('BDUK_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('BDUK_PLUGIN_BASENAME', plugin_basename(__FILE__));

require_once BDUK_PLUGIN_PATH . 'includes/class-bduk-date-resolver.php';
require_once BDUK_PLUGIN_PATH . 'includes/class-bduk-plugin.php';

// Initialize the plugin.
function bduk_init() {
    return BDUK_Plugin::get_instance();
}

// Start the plugin.
add_action('plugins_loaded', 'bduk_init');