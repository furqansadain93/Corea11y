<?php
/**
 * Removes CoreA11y data when the plugin is deleted, if the owner allowed it.
 *
 * @package CoreA11y
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

$corea11y_settings = get_option( 'corea11y_settings', array() );
$corea11y_delete   = ! is_array( $corea11y_settings ) || ! isset( $corea11y_settings['delete_on_uninstall'] ) || ! empty( $corea11y_settings['delete_on_uninstall'] );

if ( ! $corea11y_delete ) {
	return;
}

global $wpdb;

foreach ( array( 'corea11y_audits', 'corea11y_issues', 'corea11y_dismissals' ) as $corea11y_table ) {
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange -- Removing the plugin's own tables on uninstall.
	$wpdb->query( $wpdb->prepare( 'DROP TABLE IF EXISTS %i', $wpdb->prefix . $corea11y_table ) );
}

delete_option( 'corea11y_settings' );
delete_option( 'corea11y_checklist' );
delete_option( 'corea11y_db_version' );
delete_option( 'corea11y_fixes' );
delete_option( 'corea11y_fix_log' );
delete_option( 'corea11y_statement' ); // The statement page itself is your content and is kept.
