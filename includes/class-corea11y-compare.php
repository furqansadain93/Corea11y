<?php
/**
 * Before/after comparison of two audits.
 *
 * @package CoreA11y
 */

defined( 'ABSPATH' ) || exit;

/**
 * Compares the issues of two audits.
 *
 * Only pages scanned successfully in both audits are compared, so a page that was
 * skipped in one audit never makes its issues look "fixed" or "new". Site-wide
 * server checks are always compared.
 */
class CoreA11y_Compare {

	/**
	 * Build a comparison.
	 *
	 * @param int $before_id Earlier audit id.
	 * @param int $after_id  Later audit id.
	 * @return array|WP_Error
	 */
	public static function build( $before_id, $after_id ) {
		$before = CoreA11y_Audit::get( $before_id );
		$after  = CoreA11y_Audit::get( $after_id );
		if ( ! $before || ! $after ) {
			return new WP_Error( 'corea11y_no_audit', __( 'One of the audits could not be found.', 'corea11y' ) );
		}

		$pages_before = self::done_pages( $before );
		$pages_after  = self::done_pages( $after );
		$shared       = array_intersect_key( $pages_before, $pages_after );

		$a = self::index( $before_id, $shared );
		$b = self::index( $after_id, $shared );

		$rules = array();
		$sum   = array(
			'resolved'  => 0,
			'remaining' => 0,
			'new'       => 0,
		);
		$sev   = array();
		foreach ( array_keys( CoreA11y_Rules::impacts() ) as $impact ) {
			$sev[ $impact ] = array(
				'before' => 0,
				'after'  => 0,
			);
		}

		foreach ( array( 'a', 'b' ) as $side ) {
			$set = 'a' === $side ? $a : $b;
			foreach ( $set as $item ) {
				$rule = $item['rule'];
				if ( ! isset( $rules[ $rule ] ) ) {
					$rules[ $rule ] = array(
						'rule'      => $rule,
						'help'      => $item['help'],
						'wcag'      => $item['wcag'],
						'impact'    => $item['impact'],
						'before'    => 0,
						'after'     => 0,
						'resolved'  => 0,
						'remaining' => 0,
						'new'       => 0,
					);
				}
				++$rules[ $rule ][ 'a' === $side ? 'before' : 'after' ];
				++$sev[ $item['impact'] ][ 'a' === $side ? 'before' : 'after' ];
			}
		}

		foreach ( $a as $key => $item ) {
			$state = isset( $b[ $key ] ) ? 'remaining' : 'resolved';
			++$rules[ $item['rule'] ][ $state ];
			++$sum[ $state ];
		}
		foreach ( $b as $key => $item ) {
			if ( ! isset( $a[ $key ] ) ) {
				++$rules[ $item['rule'] ]['new'];
				++$sum['new'];
			}
		}

		$rank = array_flip( array_keys( CoreA11y_Rules::impacts() ) );
		uasort(
			$rules,
			static function ( $x, $y ) use ( $rank ) {
				return array( $rank[ $x['impact'] ], -max( $x['before'], $x['after'] ) ) <=> array( $rank[ $y['impact'] ], -max( $y['before'], $y['after'] ) );
			}
		);

		return array(
			'before'      => $before,
			'after'       => $after,
			'summary'     => $sum,
			'total'       => array(
				'before' => count( $a ),
				'after'  => count( $b ),
			),
			'severity'    => $sev,
			'rules'       => $rules,
			'shared'      => $shared,
			'only_before' => array_diff_key( $pages_before, $pages_after ),
			'only_after'  => array_diff_key( $pages_after, $pages_before ),
		);
	}

	/**
	 * Successfully scanned pages of an audit, keyed by normalized URL.
	 *
	 * @param object $audit Audit.
	 * @return array<string,string> URL => label.
	 */
	private static function done_pages( $audit ) {
		$out = array();
		foreach ( (array) ( isset( $audit->summary['pages'] ) ? $audit->summary['pages'] : array() ) as $page ) {
			if ( 'done' === $page['status'] ) {
				$out[ self::normalize( $page['url'] ) ] = $page['label'];
			}
		}
		return $out;
	}

	/**
	 * Issues of an audit keyed by a stable fingerprint. Duplicate fingerprints get a counter.
	 *
	 * @param int   $audit_id Audit id.
	 * @param array $shared   Normalized URLs scanned in both audits.
	 * @return array<string,array>
	 */
	private static function index( $audit_id, array $shared ) {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom table.
		$rows = $wpdb->get_results( $wpdb->prepare( "SELECT rule_id, page_url, selector, snippet, impact, wcag, help, source, object_id FROM %i WHERE audit_id = %d AND status <> 'dismissed'", CoreA11y_Install::tables()['issues'], $audit_id ) );
		$out  = array();
		foreach ( $rows as $r ) {
			$url = self::normalize( $r->page_url );
			if ( 'axe' === $r->source && ! isset( $shared[ $url ] ) ) {
				continue;
			}
			$where = 'axe' === $r->source ? $r->selector : $r->object_id . '|' . $r->snippet;
			$key   = md5( $r->rule_id . '|' . $url . '|' . $where );
			$n     = 1;
			while ( isset( $out[ $key . '#' . $n ] ) ) {
				++$n;
			}
			$out[ $key . '#' . $n ] = array(
				'rule'   => $r->rule_id,
				'impact' => $r->impact,
				'wcag'   => $r->wcag,
				'help'   => $r->help,
			);
		}
		return $out;
	}

	/**
	 * Normalize a URL for comparison (no scanner arg, no trailing slash, lowercase host).
	 *
	 * @param string $url URL.
	 * @return string
	 */
	public static function normalize( $url ) {
		$url = remove_query_arg( CoreA11y_Scanner::QUERY_ARG, (string) $url );
		$url = preg_replace( '#^https?://#i', '', $url );
		return strtolower( (string) wp_parse_url( 'http://' . $url, PHP_URL_HOST ) ) . untrailingslashit( substr( $url, strpos( $url . '/', '/' ) ) );
	}
}
