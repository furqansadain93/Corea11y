<?php
/**
 * Audit storage: create audits, record page results, run server-side checks, finish.
 *
 * @package CoreA11y
 */

defined( 'ABSPATH' ) || exit;

/**
 * Audit model.
 */
class CoreA11y_Audit {

	/**
	 * Max elements stored per rule per page (the rest are counted, not stored).
	 */
	const MAX_NODES_PER_RULE = 25;

	/**
	 * Audits left "running" longer than this are marked abandoned.
	 */
	const STALE_AFTER = HOUR_IN_SECONDS;

	/**
	 * Create an audit for the given pages.
	 *
	 * @param string $scope Scan scope.
	 * @param array  $pages Pages from CoreA11y_Pages.
	 * @return int Audit id, 0 on failure.
	 */
	public static function create( $scope, array $pages ) {
		global $wpdb;
		self::abandon_stale();

		$summary = array(
			'pages'     => array_map(
				static function ( $p ) {
					return array(
						'url'    => $p['url'],
						'label'  => $p['label'],
						'status' => 'pending',
					);
				},
				$pages
			),
			'truncated' => 0,
			'engine'    => 'axe-core ' . self::axe_version(),
		);

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom table; written once per request.
		$ok = $wpdb->insert(
			CoreA11y_Install::tables()['audits'],
			array(
				'status'      => 'running',
				'scope'       => $scope,
				'pages_total' => count( $pages ),
				'created_by'  => get_current_user_id(),
				'started_at'  => current_time( 'mysql', true ),
				'summary'     => wp_json_encode( $summary ),
			),
			array( '%s', '%s', '%d', '%d', '%s', '%s' )
		);

		return $ok ? (int) $wpdb->insert_id : 0;
	}

	/**
	 * Whether issues from this source were found by scanning a page (and so have a
	 * CSS selector that "Show on page" can use), as opposed to server-side checks.
	 *
	 * @param string $source Issue source.
	 * @return bool
	 */
	public static function is_page_source( $source ) {
		return in_array( $source, array( 'axe', 'best-practice' ), true );
	}

	/**
	 * Whether a rule comes from axe-core itself (so Deque's rule guide applies).
	 *
	 * @param string $rule_id Rule id.
	 * @return bool
	 */
	public static function is_axe_rule( $rule_id ) {
		return ! preg_match( '/^(corea11y|ibm)-/', (string) $rule_id );
	}

	/**
	 * The most recent audit of any status, with its summary decoded.
	 *
	 * @return object|null
	 */
	public static function latest() {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom table.
		$id = (int) $wpdb->get_var( $wpdb->prepare( 'SELECT id FROM %i ORDER BY id DESC LIMIT 1', CoreA11y_Install::tables()['audits'] ) );
		return $id ? self::get( $id ) : null;
	}

	/**
	 * Get an audit row with its summary decoded.
	 *
	 * @param int $id Audit id.
	 * @return object|null
	 */
	public static function get( $id ) {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom table.
		$row = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM %i WHERE id = %d', CoreA11y_Install::tables()['audits'], $id ) );
		if ( $row ) {
			$row->summary = json_decode( (string) $row->summary, true );
			if ( ! is_array( $row->summary ) ) {
				$row->summary = array();
			}
		}
		return $row;
	}

	/**
	 * Store axe-core results for one page.
	 *
	 * @param int    $id         Audit id.
	 * @param string $url        Page URL.
	 * @param int    $object_id  Post id or 0.
	 * @param array  $violations axe-core violations.
	 * @param string $error      Error message if the page could not be scanned.
	 * @param array  $review     axe-core "incomplete" results: elements a person needs to check.
	 * @return int|WP_Error Number of issues stored.
	 */
	public static function add_page_results( $id, $url, $object_id, array $violations, $error = '', array $review = array() ) {
		global $wpdb;
		$audit = self::get( $id );
		if ( ! $audit ) {
			return new WP_Error( 'corea11y_no_audit', __( 'Audit not found.', 'corea11y' ), array( 'status' => 404 ) );
		}
		if ( 'running' !== $audit->status ) {
			return new WP_Error( 'corea11y_audit_closed', __( 'This audit has already finished.', 'corea11y' ), array( 'status' => 409 ) );
		}

		// Only accept URLs that are part of this audit.
		$label = '';
		$index = null;
		foreach ( (array) $audit->summary['pages'] as $i => $page ) {
			if ( $page['url'] === $url ) {
				$index = $i;
				$label = $page['label'];
				break;
			}
		}
		if ( null === $index ) {
			return new WP_Error( 'corea11y_bad_page', __( 'That page is not part of this audit.', 'corea11y' ), array( 'status' => 400 ) );
		}
		if ( 'pending' !== $audit->summary['pages'][ $index ]['status'] ) {
			return new WP_Error( 'corea11y_page_done', __( 'Results for this page were already saved.', 'corea11y' ), array( 'status' => 409 ) );
		}

		$stored    = 0;
		$truncated = 0;
		$table     = CoreA11y_Install::tables()['issues'];

		foreach ( $violations as $v ) {
			if ( ! is_array( $v ) || empty( $v['id'] ) ) {
				continue;
			}
			$rule   = CoreA11y_Rules::sanitize_rule_id( $v['id'] );
			$impact = CoreA11y_Rules::sanitize_impact( isset( $v['impact'] ) ? $v['impact'] : '' );
			$wcag   = CoreA11y_Rules::wcag_from_tags( isset( $v['tags'] ) ? $v['tags'] : array() );
			$help   = isset( $v['help'] ) ? self::plain_text( $v['help'], 500 ) : '';
			$nodes  = isset( $v['nodes'] ) && is_array( $v['nodes'] ) ? $v['nodes'] : array();
			// Best-practice suggestions: shown as minor, labelled, and left out of the score.
			$is_bp = '' === $wcag && isset( $v['tags'] ) && in_array( 'best-practice', (array) $v['tags'], true );
			if ( $is_bp ) {
				$impact = 'minor';
			}

			$total_nodes = isset( $v['nodeCount'] ) ? absint( $v['nodeCount'] ) : count( $nodes );
			$truncated  += max( 0, $total_nodes - min( count( $nodes ), self::MAX_NODES_PER_RULE ) );

			foreach ( array_slice( $nodes, 0, self::MAX_NODES_PER_RULE ) as $node ) {
				$node_impact = ( ! $is_bp && isset( $node['impact'] ) ) ? CoreA11y_Rules::sanitize_impact( $node['impact'] ) : $impact;
				$selector    = self::selector( isset( $node['target'] ) ? $node['target'] : array() );
				$fp          = self::fingerprint( $rule, $url, $selector );
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom table; written once per request.
				$wpdb->insert(
					$table,
					array(
						'audit_id'   => $id,
						'page_url'   => $url,
						'page_label' => $label,
						'object_id'  => $object_id,
						'rule_id'    => $rule,
						'wcag'       => $wcag,
						'impact'     => $node_impact,
						'tier'       => CoreA11y_Rules::tier( $rule ),
						'source'     => $is_bp ? 'best-practice' : 'axe',
						'selector'    => $selector,
						'snippet'     => self::clip( isset( $node['html'] ) ? $node['html'] : '', 1000 ),
						'help'        => $help,
						'detail'      => isset( $node['failureSummary'] ) ? self::plain_text( $node['failureSummary'], 2000 ) : '',
						'fingerprint' => $fp,
						'status'      => CoreA11y_Dismiss::is_dismissed( $fp ) ? 'dismissed' : 'open',
					),
					array( '%d', '%s', '%s', '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s' )
				);
				++$stored;
			}
		}

		$summary                               = $audit->summary;
		$summary['pages'][ $index ]['status']  = $error ? 'error' : 'done';
		$summary['pages'][ $index ]['issues']  = $stored;
		$summary['truncated']                 += $truncated;
		if ( $error ) {
			$summary['pages'][ $index ]['error'] = sanitize_text_field( $error );
		}
		if ( ! $error && $review ) {
			$summary['review'] = self::merge_review( isset( $summary['review'] ) ? (array) $summary['review'] : array(), $review, $label );
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom table; written once per request.
		$wpdb->update(
			CoreA11y_Install::tables()['audits'],
			array(
				'pages_scanned' => (int) $audit->pages_scanned + ( $error ? 0 : 1 ),
				'summary'       => wp_json_encode( $summary ),
			),
			array( 'id' => $id ),
			array( '%d', '%s' ),
			array( '%d' )
		);

		return $stored;
	}

	/**
	 * Add one page's "needs review" results to the audit summary. These are elements
	 * axe-core could not decide on automatically (for example text over a background
	 * image). They are listed for a person to check and never count toward the score.
	 *
	 * @param array  $current Existing summary['review'], keyed by rule id.
	 * @param array  $review  axe-core incomplete results for the page.
	 * @param string $label   Page label.
	 * @return array
	 */
	private static function merge_review( array $current, array $review, $label ) {
		foreach ( $review as $r ) {
			if ( ! is_array( $r ) || empty( $r['id'] ) ) {
				continue;
			}
			$rule  = CoreA11y_Rules::sanitize_rule_id( $r['id'] );
			$nodes = isset( $r['nodes'] ) && is_array( $r['nodes'] ) ? $r['nodes'] : array();
			$count = isset( $r['nodeCount'] ) ? absint( $r['nodeCount'] ) : count( $nodes );
			if ( ! isset( $current[ $rule ] ) ) {
				$current[ $rule ] = array(
					'help'     => isset( $r['help'] ) ? self::plain_text( $r['help'], 500 ) : '',
					'wcag'     => CoreA11y_Rules::wcag_from_tags( isset( $r['tags'] ) ? $r['tags'] : array() ),
					'elements' => 0,
					'pages'    => array(),
					'examples' => array(),
				);
			}
			$current[ $rule ]['elements'] += $count;
			if ( ! in_array( $label, $current[ $rule ]['pages'], true ) ) {
				$current[ $rule ]['page_count'] = ( isset( $current[ $rule ]['page_count'] ) ? (int) $current[ $rule ]['page_count'] : 0 ) + 1;
				if ( count( $current[ $rule ]['pages'] ) < 10 ) {
					$current[ $rule ]['pages'][] = $label;
				}
			}
			foreach ( $nodes as $node ) {
				if ( count( $current[ $rule ]['examples'] ) >= 3 ) {
					break;
				}
				$current[ $rule ]['examples'][] = array(
					'page'    => $label,
					'snippet' => self::clip( isset( $node['html'] ) ? $node['html'] : '', 300 ),
					'why'     => isset( $node['failureSummary'] ) ? self::plain_text( $node['failureSummary'], 400 ) : '',
				);
			}
		}
		return $current;
	}

	/**
	 * Finish an audit: run server-side checks, total up, score.
	 *
	 * @param int $id Audit id.
	 * @return object|WP_Error Updated audit.
	 */
	public static function complete( $id ) {
		global $wpdb;
		$audit = self::get( $id );
		if ( ! $audit ) {
			return new WP_Error( 'corea11y_no_audit', __( 'Audit not found.', 'corea11y' ), array( 'status' => 404 ) );
		}
		if ( 'running' !== $audit->status ) {
			return $audit;
		}

		$scanned_ids = array();
		foreach ( (array) $audit->summary['pages'] as $page ) {
			if ( 'done' === $page['status'] ) {
				$pid = url_to_postid( $page['url'] );
				if ( $pid ) {
					$scanned_ids[ $pid ] = true;
				}
			}
		}
		CoreA11y_Server_Checks::run( $id, $scanned_ids );
		self::recalculate( $id, true );

		return self::get( $id );
	}

	/**
	 * Recompute totals and score (after finishing, or after issues are dismissed or restored).
	 *
	 * @param int  $id     Audit id.
	 * @param bool $finish Also mark the audit complete.
	 */
	public static function recalculate( $id, $finish = false ) {
		global $wpdb;
		$audit = self::get( $id );
		if ( ! $audit ) {
			return;
		}
		$counts = self::counts( $id );
		$points = 0;
		// Best-practice suggestions don't count toward the score.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom table.
		$rows = $wpdb->get_results( $wpdb->prepare( "SELECT impact, COUNT(*) AS n FROM %i WHERE audit_id = %d AND status <> 'dismissed' AND source <> 'best-practice' GROUP BY impact", CoreA11y_Install::tables()['issues'], $id ) );
		foreach ( $rows as $r ) {
			$points += CoreA11y_Rules::weight( $r->impact ) * (int) $r->n;
		}
		$pages = max( 1, (int) $audit->pages_scanned );
		$score = (int) max( 0, 100 - round( 2 * $points / $pages ) );

		$summary           = $audit->summary;
		$summary['counts'] = $counts;

		$data = array(
			'issues_total' => $counts['total'],
			'score'        => $score,
			'summary'      => wp_json_encode( $summary ),
		);
		if ( $finish ) {
			$data['status']      = 'complete';
			$data['finished_at'] = current_time( 'mysql', true );
		}
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom table; written once per request.
		$wpdb->update( CoreA11y_Install::tables()['audits'], $data, array( 'id' => $id ) );
	}

	/**
	 * Stable identity of an issue across audits: same problem, same page, same element.
	 *
	 * @param string $rule  Rule id.
	 * @param string $url   Page URL.
	 * @param string $where Selector (axe) or "object_id|snippet" (server checks).
	 * @return string 32-char hash.
	 */
	public static function fingerprint( $rule, $url, $where ) {
		return md5( $rule . '|' . CoreA11y_Compare::normalize( $url ) . '|' . $where );
	}

	/**
	 * Counts by impact, tier and source.
	 *
	 * @param int $id Audit id.
	 * @return array
	 */
	public static function counts( $id ) {
		global $wpdb;
		$table = CoreA11y_Install::tables()['issues'];
		$out   = array(
			'total'  => 0,
			'impact' => array_fill_keys( array_keys( CoreA11y_Rules::impacts() ), 0 ),
			'tier'   => array_fill_keys( array_keys( CoreA11y_Rules::tiers() ), 0 ),
			'rules'  => 0,
		);
		foreach ( array( 'impact', 'tier' ) as $col ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom table.
			$rows = $wpdb->get_results( $wpdb->prepare( "SELECT %i AS k, COUNT(*) AS n FROM %i WHERE audit_id = %d AND status <> 'dismissed' GROUP BY %i", $col, $table, $id, $col ) );
			foreach ( $rows as $r ) {
				if ( isset( $out[ $col ][ $r->k ] ) ) {
					$out[ $col ][ $r->k ] = (int) $r->n;
				}
			}
		}
		$out['total'] = array_sum( $out['impact'] );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom table.
		$out['rules'] = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(DISTINCT rule_id) FROM %i WHERE audit_id = %d AND status <> 'dismissed'", $table, $id ) );
		return $out;
	}

	/**
	 * Issue counts per WCAG principle (1-4).
	 *
	 * @param int $id Audit id.
	 * @return array<int,int>
	 */
	public static function principle_counts( $id ) {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom table.
		$rows = $wpdb->get_results( $wpdb->prepare( "SELECT wcag, COUNT(*) AS n FROM %i WHERE audit_id = %d AND status <> 'dismissed' GROUP BY wcag", CoreA11y_Install::tables()['issues'], $id ) );
		$out  = array_fill_keys( array_keys( CoreA11y_Rules::principles() ), 0 );
		foreach ( $rows as $r ) {
			$p = CoreA11y_Rules::principle_of( $r->wcag );
			if ( $p ) {
				$out[ $p ] += (int) $r->n;
			}
		}
		return $out;
	}

	/**
	 * Pages with the most issues.
	 *
	 * @param int $id    Audit id.
	 * @param int $limit Max rows.
	 * @return array
	 */
	public static function page_counts( $id, $limit = 10 ) {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom table.
		return $wpdb->get_results( $wpdb->prepare( "SELECT page_url, MAX(page_label) AS page_label, COUNT(*) AS n FROM %i WHERE audit_id = %d AND source = 'axe' AND status <> 'dismissed' GROUP BY page_url ORDER BY n DESC LIMIT %d", CoreA11y_Install::tables()['issues'], $id, $limit ) );
	}

	/**
	 * Issues grouped by rule, most severe first.
	 *
	 * @param int $id Audit id.
	 * @return array<string,array{rule:string,help:string,wcag:string,impact:string,tier:string,source:string,items:array}>
	 */
	public static function grouped_issues( $id ) {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom table.
		$rows   = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM %i WHERE audit_id = %d AND status <> 'dismissed' ORDER BY id ASC", CoreA11y_Install::tables()['issues'], $id ) );
		$groups = array();
		$rank   = array_flip( array_keys( CoreA11y_Rules::impacts() ) );
		foreach ( $rows as $r ) {
			if ( ! isset( $groups[ $r->rule_id ] ) ) {
				$groups[ $r->rule_id ] = array(
					'rule'   => $r->rule_id,
					'help'   => $r->help,
					'wcag'   => $r->wcag,
					'impact' => $r->impact,
					'tier'   => $r->tier,
					'source' => $r->source,
					'pages'  => array(),
					'items'  => array(),
				);
			}
			if ( $rank[ $r->impact ] < $rank[ $groups[ $r->rule_id ]['impact'] ] ) {
				$groups[ $r->rule_id ]['impact'] = $r->impact;
			}
			$groups[ $r->rule_id ]['pages'][ $r->page_url ] = true;
			$groups[ $r->rule_id ]['items'][]               = $r;
		}
		uasort(
			$groups,
			static function ( $a, $b ) use ( $rank ) {
				return array( $rank[ $a['impact'] ], -count( $a['items'] ) ) <=> array( $rank[ $b['impact'] ], -count( $b['items'] ) );
			}
		);
		return $groups;
	}

	/**
	 * Mark audits stuck in "running" as abandoned.
	 */
	private static function abandon_stale() {
		global $wpdb;
		$cutoff = gmdate( 'Y-m-d H:i:s', time() - self::STALE_AFTER );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom table.
		$wpdb->query( $wpdb->prepare( "UPDATE %i SET status = 'abandoned' WHERE status = 'running' AND started_at < %s", CoreA11y_Install::tables()['audits'], $cutoff ) );
	}

	/**
	 * Flatten an axe target (array of selectors, possibly nested for iframes/shadow DOM).
	 *
	 * @param mixed $target Target.
	 * @return string
	 */
	private static function selector( $target ) {
		$parts = array();
		array_walk_recursive(
			$target,
			static function ( $s ) use ( &$parts ) {
				$parts[] = (string) $s;
			}
		);
		return self::clip( sanitize_text_field( implode( ' >>> ', $parts ) ), 1000 );
	}

	/**
	 * Trim a string to a max length.
	 *
	 * @param mixed $text Text.
	 * @param int   $max  Max characters.
	 * @return string
	 */
	public static function clip( $text, $max ) {
		$text = (string) $text;
		return mb_strlen( $text ) > $max ? mb_substr( $text, 0, $max - 1 ) . '…' : $text;
	}

	/**
	 * Clean plain text that may legitimately mention element names like "<li>".
	 * Stored as-is (minus control characters) and always escaped on output.
	 *
	 * @param mixed $text Text.
	 * @param int   $max  Max characters.
	 * @return string
	 */
	public static function plain_text( $text, $max ) {
		$text = wp_check_invalid_utf8( is_scalar( $text ) ? (string) $text : '', true );
		$text = preg_replace( '/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $text );
		return self::clip( trim( (string) $text ), $max );
	}

	/**
	 * Bundled axe-core version.
	 *
	 * @return string
	 */
	public static function axe_version() {
		return '4.14.0';
	}
}
