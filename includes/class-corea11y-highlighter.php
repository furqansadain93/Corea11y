<?php
/**
 * Front-end highlighter: shows administrators where each issue is on the live page.
 *
 * @package CoreA11y
 */

defined( 'ABSPATH' ) || exit;

/**
 * "Show on page" links, the admin-bar shortcut and the highlighter assets.
 */
class CoreA11y_Highlighter {

	const ARG_AUDIT = 'corea11y_highlight';
	const ARG_ISSUE = 'corea11y_issue';
	const ARG_NONCE = 'corea11y_hl';

	/**
	 * Hook into WordPress.
	 */
	public static function register() {
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'maybe_enqueue' ) );
		add_action( 'admin_bar_menu', array( __CLASS__, 'admin_bar' ), 90 );
	}

	/**
	 * Link that opens a page with its issues highlighted.
	 *
	 * @param int    $audit_id Audit id.
	 * @param string $page_url Page URL.
	 * @param int    $issue_id Issue to show first (optional).
	 * @return string
	 */
	public static function url( $audit_id, $page_url, $issue_id = 0 ) {
		$args = array(
			self::ARG_AUDIT => absint( $audit_id ),
			self::ARG_NONCE => wp_create_nonce( 'corea11y_highlight_' . absint( $audit_id ) ),
		);
		if ( $issue_id ) {
			$args[ self::ARG_ISSUE ] = absint( $issue_id );
		}
		return add_query_arg( $args, $page_url );
	}

	/**
	 * The URL of the current front-end request, without highlighter arguments.
	 *
	 * @return string
	 */
	private static function current_url() {
		$host = isset( $_SERVER['HTTP_HOST'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_HOST'] ) ) : '';
		$uri  = isset( $_SERVER['REQUEST_URI'] ) ? esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '/';
		$url  = ( is_ssl() ? 'https://' : 'http://' ) . $host . $uri;
		return remove_query_arg( array( self::ARG_AUDIT, self::ARG_ISSUE, self::ARG_NONCE, CoreA11y_Scanner::QUERY_ARG ), $url );
	}

	/**
	 * Open axe issues for a page in an audit.
	 *
	 * @param int    $audit_id Audit id.
	 * @param string $page_url Page URL (any form; compared normalized).
	 * @return array
	 */
	public static function issues_for( $audit_id, $page_url ) {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom table.
		$rows   = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM %i WHERE audit_id = %d AND source IN ('axe', 'best-practice') AND status <> 'dismissed' ORDER BY id ASC", CoreA11y_Install::tables()['issues'], $audit_id ) );
		$target = CoreA11y_Compare::normalize( $page_url );
		$rank   = array_flip( array_keys( CoreA11y_Rules::impacts() ) );
		$out    = array();
		foreach ( $rows as $r ) {
			if ( CoreA11y_Compare::normalize( $r->page_url ) === $target ) {
				$out[] = $r;
			}
		}
		usort(
			$out,
			static function ( $a, $b ) use ( $rank ) {
				return array( $rank[ $a->impact ], (int) $a->id ) <=> array( $rank[ $b->impact ], (int) $b->id );
			}
		);
		return $out;
	}

	/**
	 * Enqueue the highlighter when a valid, nonce-protected request asks for it.
	 */
	public static function maybe_enqueue() {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- Verified just below.
		if ( ! isset( $_GET[ self::ARG_AUDIT ], $_GET[ self::ARG_NONCE ] ) || ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$audit_id = absint( $_GET[ self::ARG_AUDIT ] );
		$issue_id = isset( $_GET[ self::ARG_ISSUE ] ) ? absint( $_GET[ self::ARG_ISSUE ] ) : 0;
		$nonce    = sanitize_key( wp_unslash( $_GET[ self::ARG_NONCE ] ) );
		// phpcs:enable
		if ( ! wp_verify_nonce( $nonce, 'corea11y_highlight_' . $audit_id ) ) {
			return;
		}

		$impacts = CoreA11y_Rules::impacts();
		$data    = array();
		foreach ( self::issues_for( $audit_id, self::current_url() ) as $r ) {
			$ex     = CoreA11y_Explain::get( $r->rule_id, $r->help );
			$data[] = array(
				'id'          => (int) $r->id,
				'selector'    => $r->selector,
				'title'       => $ex['title'],
				'why'         => $ex['why'],
				'fix'         => $ex['fix'],
				'impact'      => $r->impact,
				'impactLabel' => isset( $impacts[ $r->impact ] ) ? $impacts[ $r->impact ] : $r->impact,
				'wcag'        => $r->wcag,
				'snippet'     => CoreA11y_Audit::clip( $r->snippet, 300 ),
			);
		}

		wp_enqueue_script( 'corea11y-highlighter', COREA11Y_URL . 'assets/js/highlighter.js', array(), corea11y_asset_ver( 'assets/js/highlighter.js' ), array( 'in_footer' => true ) );
		wp_localize_script(
			'corea11y-highlighter',
			'corea11yHighlight',
			array(
				'issues'    => $data,
				'current'   => $issue_id,
				'reportUrl' => CoreA11y_Admin::report_url( $audit_id ),
				'i18n'      => array(
					'title'   => __( 'Accessibility issues on this page', 'corea11y' ),
					/* translators: 1: current issue number, 2: total issues. Keep the placeholders as they are. */
					'count'   => __( 'Issue %1$d of %2$d', 'corea11y' ),
					'prev'    => __( 'Previous', 'corea11y' ),
					'next'    => __( 'Next', 'corea11y' ),
					'close'   => __( 'Close', 'corea11y' ),
					'back'    => __( 'Back to report', 'corea11y' ),
					'why'     => __( 'Why it matters:', 'corea11y' ),
					'fix'     => __( 'How to fix:', 'corea11y' ),
					'missing' => __( 'This element is no longer on the page in the same place. It may already be fixed, or the page has changed since the audit.', 'corea11y' ),
				),
			)
		);
	}

	/**
	 * Admin bar shortcut: "Accessibility: N issues" on pages with issues in the latest audit.
	 *
	 * @param WP_Admin_Bar $bar Admin bar.
	 */
	public static function admin_bar( $bar ) {
		if ( is_admin() || ! current_user_can( 'manage_options' ) ) {
			return;
		}
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Display only.
		if ( isset( $_GET[ CoreA11y_Scanner::QUERY_ARG ] ) ) {
			return;
		}
		$latest = CoreA11y_Admin::recent_audits( 1, 'complete' );
		if ( ! $latest ) {
			return;
		}
		$url    = self::current_url();
		$issues = self::issues_for( (int) $latest[0]->id, $url );
		$n      = count( $issues );
		$bar->add_node(
			array(
				'id'    => 'corea11y',
				/* translators: %s: number of issues. */
				'title' => esc_html( $n ? sprintf( _n( 'Accessibility: %s issue', 'Accessibility: %s issues', $n, 'corea11y' ), number_format_i18n( $n ) ) : __( 'Accessibility: no issues', 'corea11y' ) ),
				'href'  => $n ? self::url( (int) $latest[0]->id, $url ) : CoreA11y_Admin::report_url( (int) $latest[0]->id ),
			)
		);
	}
}
