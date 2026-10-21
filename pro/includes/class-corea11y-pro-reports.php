<?php
/**
 * CoreA11y Pro reports: CSV export (Business and Agency) and white-label PDFs (Agency).
 *
 * @package CoreA11y
 */

defined( 'ABSPATH' ) || exit;

/**
 * Report extras.
 */
class CoreA11y_Pro_Reports {

	/**
	 * Hook into Lite.
	 */
	public static function register() {
		add_action( 'corea11y_report_actions', array( __CLASS__, 'csv_button' ) );
		add_action( 'admin_post_corea11y_pro_csv', array( __CLASS__, 'csv' ) );
		add_filter( 'corea11y_report_brand', array( __CLASS__, 'brand' ) );
		add_filter( 'corea11y_report_footer', array( __CLASS__, 'footer' ) );
	}

	/**
	 * Download link for an audit's CSV.
	 *
	 * @param int $audit_id Audit id.
	 * @return string
	 */
	public static function csv_url( $audit_id ) {
		return wp_nonce_url( add_query_arg( array( 'action' => 'corea11y_pro_csv', 'audit' => absint( $audit_id ) ), admin_url( 'admin-post.php' ) ), 'corea11y_pro_csv' );
	}

	/**
	 * "Download CSV" next to "Download PDF".
	 *
	 * @param object $audit Audit row.
	 */
	public static function csv_button( $audit ) {
		if ( ! CoreA11y_Pro_License::has( CoreA11y_Pro_License::BUSINESS ) || empty( $audit->id ) ) {
			return;
		}
		printf(
			'<a class="button" href="%1$s">%2$s</a>',
			esc_url( self::csv_url( $audit->id ) ),
			esc_html__( 'Download CSV', 'corea11y' )
		);
	}

	/**
	 * Stream every issue of an audit as CSV (admin-post handler).
	 */
	public static function csv() {
		if ( ! current_user_can( CoreA11y_Admin::CAP ) ) {
			wp_die( esc_html__( 'You do not have permission to do this.', 'corea11y' ), 403 );
		}
		check_admin_referer( 'corea11y_pro_csv' );
		if ( ! CoreA11y_Pro_License::has( CoreA11y_Pro_License::BUSINESS ) ) {
			wp_die( esc_html__( 'CSV export is part of the CoreA11y Pro Business and Agency plans.', 'corea11y' ), 403 );
		}
		$audit = CoreA11y_Audit::get( isset( $_GET['audit'] ) ? absint( $_GET['audit'] ) : 0 );
		if ( ! $audit ) {
			wp_die( esc_html__( 'Audit not found.', 'corea11y' ), 404 );
		}

		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom table.
		$rows = $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM %i WHERE audit_id = %d ORDER BY page_label, rule_id, id', CoreA11y_Install::tables()['issues'], $audit->id ) );

		$impacts = CoreA11y_Rules::impacts();
		$date    = $audit->started_at ? get_date_from_gmt( $audit->started_at, 'Y-m-d' ) : gmdate( 'Y-m-d' );
		$host    = (string) wp_parse_url( home_url(), PHP_URL_HOST );
		$name    = sanitize_file_name( 'corea11y-' . $host . '-' . $date . '.csv' );

		nocache_headers();
		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="' . $name . '"' );
		header( 'X-Content-Type-Options: nosniff' );

		$out = fopen( 'php://output', 'w' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen -- Streaming to the browser.
		fwrite( $out, "\xEF\xBB\xBF" ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fwrite -- UTF-8 marker so Excel shows accents correctly.
		fputcsv(
			$out,
			array(
				__( 'Page', 'corea11y' ),
				__( 'URL', 'corea11y' ),
				__( 'Problem', 'corea11y' ),
				__( 'Rule', 'corea11y' ),
				__( 'WCAG', 'corea11y' ),
				__( 'Impact', 'corea11y' ),
				__( 'Type', 'corea11y' ),
				__( 'Status', 'corea11y' ),
				__( 'Element', 'corea11y' ),
				__( 'HTML', 'corea11y' ),
				__( 'Who it affects', 'corea11y' ),
				__( 'How to fix', 'corea11y' ),
			)
		);
		$cache = array();
		foreach ( $rows as $r ) {
			if ( ! isset( $cache[ $r->rule_id ] ) ) {
				$cache[ $r->rule_id ] = CoreA11y_Explain::get( $r->rule_id, (string) $r->help );
			}
			$x = $cache[ $r->rule_id ];
			if ( 'best-practice' === $r->source ) {
				$type = __( 'Best practice', 'corea11y' );
			} elseif ( 'review' === $r->source ) {
				$type = __( 'Needs a human check', 'corea11y' );
			} else {
				$type = __( 'Issue', 'corea11y' );
			}
			fputcsv(
				$out,
				array_map(
					array( __CLASS__, 'cell' ),
					array(
						$r->page_label,
						$r->page_url,
						$x['title'],
						$r->rule_id,
						$r->wcag,
						isset( $impacts[ $r->impact ] ) ? $impacts[ $r->impact ] : $r->impact,
						$type,
						'dismissed' === $r->status ? __( 'Dismissed', 'corea11y' ) : __( 'Open', 'corea11y' ),
						$r->selector,
						$r->snippet,
						$x['who'],
						$x['fix'],
					)
				)
			);
		}
		fclose( $out ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose -- Streaming to the browser.
		exit;
	}

	/**
	 * Make a value safe for spreadsheets: never read as a formula. HTML stays as text.
	 *
	 * @param mixed $value Value.
	 * @return string
	 */
	public static function cell( $value ) {
		$value = is_array( $value ) ? implode( ', ', $value ) : (string) $value;
		$value = mb_substr( $value, 0, 2000 );
		if ( '' !== $value && in_array( $value[0], array( '=', '+', '-', '@', "\t", "\r" ), true ) ) {
			$value = "'" . $value;
		}
		return $value;
	}

	/**
	 * White-label brand for PDF reports (Agency).
	 *
	 * @param array $brand Brand.
	 * @return array
	 */
	public static function brand( $brand ) {
		if ( ! CoreA11y_Pro_License::has( CoreA11y_Pro_License::AGENCY ) ) {
			return $brand;
		}
		$s = CoreA11y_Pro_Settings::get();
		if ( '' === $s['brand_name'] && ! $s['brand_logo'] ) {
			return $brand;
		}
		$logo = $s['brand_logo'] ? wp_get_attachment_image_url( (int) $s['brand_logo'], 'medium' ) : '';
		$name = '' !== $s['brand_name'] ? $s['brand_name'] : get_post_meta( (int) $s['brand_logo'], '_wp_attachment_image_alt', true );
		return array(
			'name'  => $name,
			'logo'  => $logo ? $logo : '',
			'color' => $s['brand_color'],
		);
	}

	/**
	 * White-label footer (Agency). The conformance disclaimer always stays.
	 *
	 * @param string $foot Footer text.
	 * @return string
	 */
	public static function footer( $foot ) {
		if ( ! CoreA11y_Pro_License::has( CoreA11y_Pro_License::AGENCY ) ) {
			return $foot;
		}
		$s = CoreA11y_Pro_Settings::get();
		if ( '' === $s['brand_name'] && ! $s['brand_logo'] ) {
			return $foot;
		}
		$who   = '' !== $s['brand_name'] ? $s['brand_name'] : __( 'your accessibility partner', 'corea11y' );
		$parts = array(
			/* translators: %s: agency name. */
			sprintf( __( 'Report prepared by %s', 'corea11y' ), $who ),
		);
		if ( '' !== $s['brand_foot'] ) {
			$parts[] = $s['brand_foot'];
		}
		$parts[] = __( 'Automated results do not certify WCAG conformance.', 'corea11y' );
		return implode( ' · ', $parts );
	}
}
