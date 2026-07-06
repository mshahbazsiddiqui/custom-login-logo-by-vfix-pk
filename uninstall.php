<?php
/**
 * Uninstall handler for Custom Login Logo by VFIX.PK.
 *
 * WordPress executes this file automatically (and only) when the plugin is
 * deleted via the Plugins screen -- never on simple deactivation. It removes
 * the single option this plugin ever creates, leaving no orphaned data in
 * wp_options. No other cleanup is required since this plugin creates no
 * custom tables, files, or transients.
 */

// Guard against direct access / execution outside of a genuine uninstall.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

delete_option( 'cll_logo_attachment_id' );

// Clean up the same option in all sites if this is a multisite network.
if ( is_multisite() ) {
	global $wpdb;

	// 'number' => 0 removes the default 100-site cap so every site in the
	// network is cleaned up, even on large multisite installs.
	$cll_site_ids = get_sites( array( 'fields' => 'ids', 'number' => 0 ) );

	if ( ! empty( $cll_site_ids ) ) {
		foreach ( $cll_site_ids as $cll_site_id ) {
			switch_to_blog( $cll_site_id );
			delete_option( 'cll_logo_attachment_id' );
			restore_current_blog();
		}
	}
}
