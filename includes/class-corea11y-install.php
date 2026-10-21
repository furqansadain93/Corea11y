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
	 * Remove CoreA11y's data when the plugin is deleted (runs on Freemius's
	 * after_uninstall action). On multisite, every site is cleaned and each site's own
	 * "delete data" setting is respected. The statement page is the owner's content and stays.
	 */
	public static function uninstall() {
		if ( is_multisite() ) {
			foreach ( get_sites(
				array(
					'fields' => 'ids',
					'number' => 0,
				)
			) as $site_id ) {
				switch_to_blog( $site_id );
				self::uninstall_site();
				restore_current_blog();
			}
			delete_site_option( 'corea11y_network' );
		} else {
			self::uninstall_site();
		}
	}

	/**
	 * Remove CoreA11y's tables and options from the current site, if the owner allowed it.
	 */
	private static function uninstall_site() {
		global $wpdb;

		$settings = get_option( 'corea11y_settings', array() );
		$delete   = ! is_array( $settings ) || ! isset( $settings['delete_on_uninstall'] ) || ! empty( $settings['delete_on_uninstall'] );
		if ( ! $delete ) {
			return;
		}

		foreach ( array( 'corea11y_audits', 'corea11y_issues', 'corea11y_dismissals' ) as $table ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange -- Removing the plugin's own tables on uninstall.
			$wpdb->query( $wpdb->prepare( 'DROP TABLE IF EXISTS %i', $wpdb->prefix . $table ) );
		}

		foreach ( array( 'corea11y_settings', 'corea11y_checklist', 'corea11y_db_version', 'corea11y_fixes', 'corea11y_fix_log', 'corea11y_fix_verified', 'corea11y_alt_log', 'corea11y_statement', 'corea11y_pro', 'corea11y_pro_due', 'corea11y_pro_last_check', 'corea11y_contrast', 'corea11y_contrast_log' ) as $option ) {
			delete_option( $option );
		}
		wp_clear_scheduled_hook( 'corea11y_pro_weekly' );
		// The alt text itself is the owner's content and stays; only CoreA11y's marker goes.
		delete_post_meta_by_key( '_corea11y_decorative' );
	}

	/**
	 * Add this site's CoreA11y tables to the list WordPress drops when a site is deleted.
	 *
	 * @param string[] $tables  Tables to drop.
	 * @param int      $site_id Site being deleted.
	 * @return string[]
	 */
	public static function drop_with_site( $tables, $site_id ) {
		global $wpdb;
		$prefix = $wpdb->get_blog_prefix( $site_id );
		foreach ( array( 'corea11y_audits', 'corea11y_issues', 'corea11y_dismissals' ) as $t ) {
			$tables[] = $prefix . $t;
		}
		return array_values( array_unique( $tables ) );
	}

	/**
	 * Table names, keyed by short name.
	 *
	 * @return array<string,string>
	 */
	public static function tables() {
		global $wpdb;
		return array(
			'audits'     => $wpdb->prefix . 'corea11y_audits',
			'issues'     => $wpdb->prefix . 'corea11y_issues',
			'dismissals' => $wpdb->prefix . 'corea11y_dismissals',
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
			fingerprint char(32) NOT NULL DEFAULT '',
			status varchar(20) NOT NULL DEFAULT 'open',
			PRIMARY KEY  (id),
			KEY audit_id (audit_id),
			KEY rule_id (rule_id),
			KEY fingerprint (fingerprint)
		) $charset;";

		$dismissals = "CREATE TABLE {$t['dismissals']} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			fingerprint char(32) NOT NULL,
			rule_id varchar(100) NOT NULL DEFAULT '',
			page_url varchar(2048) NOT NULL DEFAULT '',
			reason varchar(30) NOT NULL DEFAULT '',
			note text,
			user_id bigint(20) unsigned NOT NULL DEFAULT 0,
			created_at datetime DEFAULT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY fingerprint (fingerprint)
		) $charset;";

		dbDelta( $audits );
		dbDelta( $issues );
		dbDelta( $dismissals );
		self::backfill_fingerprints();
	}

	/**
	 * Give issues stored before fingerprints existed one (schema version 3).
	 */
	private static function backfill_fingerprints() {
		global $wpdb;
		$table = self::tables()['issues'];
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- One-time migration of the plugin's own table.
		$rows = $wpdb->get_results( $wpdb->prepare( "SELECT id, rule_id, page_url, selector, snippet, object_id, source FROM %i WHERE fingerprint = '' LIMIT 5000", $table ) );
		foreach ( (array) $rows as $r ) {
			$where = CoreA11y_Audit::is_page_source( $r->source ) ? $r->selector : (int) $r->object_id . '|' . $r->snippet;
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- One-time migration.
			$wpdb->update( $table, array( 'fingerprint' => CoreA11y_Audit::fingerprint( $r->rule_id, $r->page_url, $where ) ), array( 'id' => $r->id ) );
		}
	}
}
