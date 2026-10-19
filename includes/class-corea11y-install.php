<?php
/**
 * Activation, database schema and upgrades.
 *
 * @package CoreA11y
 */

defined( 'ABSPATH' ) || exit;

/**
 * Creates and upgrades the plugin's tables.
 */
class CoreA11y_Install {

	/**
	 * Option that stores the installed schema version.
	 */
	const DB_VERSION_OPTION = 'corea11y_db_version';

	/**
	 * Activation hook.
	 */
	public static function activate() {
		self::create_tables();
		update_option( self::DB_VERSION_OPTION, COREA11Y_DB_VERSION, false );
		if ( false === get_option( CoreA11y_Settings::OPTION ) ) {
			add_option( CoreA11y_Settings::OPTION, CoreA11y_Settings::defaults(), '', false );
		}
	}

	/**
	 * Run schema upgrades when the stored version is behind.
	 */
	public static function maybe_upgrade() {
		if ( get_option( self::DB_VERSION_OPTION ) !== COREA11Y_DB_VERSION ) {
			self::activate();
		}
	}

	/**
	 * Table names, keyed by short name.
	 *
	 * @return array<string,string>
	 */
	public static function tables() {
		global $wpdb;
		return array(
			'audits' => $wpdb->prefix . 'corea11y_audits',
			'issues' => $wpdb->prefix . 'corea11y_issues',
		);
	}

	/**
	 * Create or update tables with dbDelta.
	 */
	public static function create_tables() {
		global $wpdb;
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$charset = $wpdb->get_charset_collate();
		$t       = self::tables();

		// dbDelta is strict about formatting: two spaces after PRIMARY KEY, one field per line.
		$audits = "CREATE TABLE {$t['audits']} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			status varchar(20) NOT NULL DEFAULT 'pending',
			scope varchar(20) NOT NULL DEFAULT 'sample',
			pages_total int(10) unsigned NOT NULL DEFAULT 0,
			pages_scanned int(10) unsigned NOT NULL DEFAULT 0,
			issues_total int(10) unsigned NOT NULL DEFAULT 0,
			score tinyint(3) unsigned DEFAULT NULL,
			created_by bigint(20) unsigned NOT NULL DEFAULT 0,
			started_at datetime DEFAULT NULL,
			finished_at datetime DEFAULT NULL,
			summary longtext,
			PRIMARY KEY  (id),
			KEY status (status)
		) $charset;";

		$issues = "CREATE TABLE {$t['issues']} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			audit_id bigint(20) unsigned NOT NULL,
			page_url varchar(2048) NOT NULL DEFAULT '',
			page_label varchar(255) NOT NULL DEFAULT '',
			object_id bigint(20) unsigned NOT NULL DEFAULT 0,
			rule_id varchar(100) NOT NULL DEFAULT '',
			wcag varchar(100) NOT NULL DEFAULT '',
			impact varchar(20) NOT NULL DEFAULT '',
			tier varchar(20) NOT NULL DEFAULT 'owner',
			source varchar(20) NOT NULL DEFAULT 'axe',
			selector text,
			snippet text,
			help text,
			detail text,
			status varchar(20) NOT NULL DEFAULT 'open',
			PRIMARY KEY  (id),
			KEY audit_id (audit_id),
			KEY rule_id (rule_id)
		) $charset;";

		dbDelta( $audits );
		dbDelta( $issues );
	}
}
