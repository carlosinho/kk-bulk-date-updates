<?php
/**
 * Plugin uninstall cleanup.
 *
 * @package BDUK
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

delete_option( 'bduk_version' );
