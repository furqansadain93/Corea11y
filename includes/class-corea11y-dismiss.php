<?php
/**
 * Dismissed issues: false positives, issues fixed in ways a scan can't see, or accepted exceptions.
 *
 * A dismissal is keyed by the issue's fingerprint (same problem, same page, same element),
 * so it carries over to future audits. Every dismissal can be restored.
 *
 * @package CoreA11y
 */

defined( 'ABSPATH' ) || exit;

/**
 * Dismiss and restore issues.
 */
class CoreA11y_Dismiss {

	/**
	 * Cached set of dismissed fingerprints for this request.
	 *
	 * @var array<string,bool>|null
	 */
	private static $cache = null;

	/**
	 * Reasons a user can pick.
	 *
	 * @return array<string,string>
	 */
	public static function reasons() {
		return array(
			'false_positive'  => __( 'Not a real problem (false positive)', 'corea11y' ),
			'fixed_elsewhere' => __( 'Already accessible in a way the scan can\'t see', 'corea11y' ),
			'accepted'        => __( 'Known issue, accepted for now', 'corea11y' ),
		);
	}

	/**
	 * Whether a fingerprint is dismissed.
	 *
	 * @param string $fp Fingerprint.
	 * @return bool
	 */
	public static function is_dismissed( $fp ) {
		if ( null === self::$cache ) {
			global $wpdb;
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom table; cached per request.
			$fps         = $wpdb->get_col( $wpdb->prepare( 'SELECT fingerprint FROM %i', CoreA11y_Install::tables()['dismissals'] ) );
			self::$cache = array_fill_keys( (array) $fps, true );
		}
		return isset( self::$cache[ $fp ] );
	}

	/**
	 * Dismiss the issue with this id (and the same issue in every audit).
	 *
	 * @param int    $issue_id Issue row id.
	 * @param string $reason   Reason key.
	 * @param string $note     Optional note.
	 * @return string|WP_Error Fingerprint dismissed.
	 */
	public static function dismiss( $issue_id, $reason, $note = '' ) {
		global $wpdb;
		$t     = CoreA11y_Install::tables();
		$issue = $wpdb->get_row( $wpdb->prepare( 'SELECT id, fingerprint, rule_id, page_url FROM %i WHERE id = %d', $t['issues'], $issue_id ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom table.
		if ( ! $issue || '' === $issue->fingerprint ) {
			return new WP_Error( 'corea11y_no_issue', __( 'That issue could not be found.', 'corea11y' ) );
		}
		if ( ! array_key_exists( $reason, self::reasons() ) ) {
			$reason = 'accepted';
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Custom table.
		$wpdb->replace(
			$t['dismissals'],
			array(
				'fingerprint' => $issue->fingerprint,
				'rule_id'     => $issue->rule_id,
				'page_url'    => $issue->page_url,
				'reason'      => $reason,
				'note'        => CoreA11y_Audit::plain_text( $note, 1000 ),
				'user_id'     => get_current_user_id(),
				'created_at'  => current_time( 'mysql', true ),
			)
		);
		self::set_status( $issue->fingerprint, 'dismissed' );
		return $issue->fingerprint;
	}

	/**
	 * Restore a dismissed issue (undo).
	 *
	 * @param string $fp Fingerprint.
	 * @return bool
	 */
	public static function restore( $fp ) {
		global $wpdb;
		$fp = preg_replace( '/[^a-f0-9]/', '', strtolower( (string) $fp ) );
		if ( 32 !== strlen( $fp ) ) {
			return false;
		}
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom table.
		$deleted = $wpdb->delete( CoreA11y_Install::tables()['dismissals'], array( 'fingerprint' => $fp ) );
		self::set_status( $fp, 'open' );
		return (bool) $deleted;
	}

	/**
	 * Dismissal record for a fingerprint.
	 *
	 * @param string $fp Fingerprint.
	 * @return object|null
	 */
	public static function get( $fp ) {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom table.
		return $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM %i WHERE fingerprint = %s', CoreA11y_Install::tables()['dismissals'], $fp ) );
	}

	/**
	 * Dismissed issues within one audit, with their dismissal details.
	 *
	 * @param int $audit_id Audit id.
	 * @return array
	 */
	public static function for_audit( $audit_id ) {
		global $wpdb;
		$t = CoreA11y_Install::tables();
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom tables.
		return $wpdb->get_results( $wpdb->prepare( "SELECT i.*, d.reason, d.note, d.user_id AS dismissed_by, d.created_at AS dismissed_at FROM %i i JOIN %i d ON d.fingerprint = i.fingerprint WHERE i.audit_id = %d AND i.status = 'dismissed' ORDER BY d.created_at DESC", $t['issues'], $t['dismissals'], $audit_id ) );
	}

	/**
	 * Update the status of every issue with a fingerprint, then recalculate the affected audits.
	 *
	 * @param string $fp     Fingerprint.
	 * @param string $status open|dismissed.
	 */
	private static function set_status( $fp, $status ) {
		global $wpdb;
		$table = CoreA11y_Install::tables()['issues'];
		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom table.
		$audits = $wpdb->get_col( $wpdb->prepare( 'SELECT DISTINCT audit_id FROM %i WHERE fingerprint = %s', $table, $fp ) );
		$wpdb->update( $table, array( 'status' => $status ), array( 'fingerprint' => $fp ) );
		// phpcs:enable
		self::$cache = null;
		foreach ( $audits as $audit_id ) {
			CoreA11y_Audit::recalculate( (int) $audit_id );
		}
	}
}
