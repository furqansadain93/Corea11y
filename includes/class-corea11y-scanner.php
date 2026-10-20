<?php
/**
 * Browser-side scanner wiring: assets for the admin and front-end tweaks during a scan.
 *
 * @package CoreA11y
 */

defined( 'ABSPATH' ) || exit;

/**
 * Loads the scanner and keeps the admin bar out of scanned pages.
 */
class CoreA11y_Scanner {

	/**
	 * Query arg added to URLs loaded by the scanner.
	 */
	const QUERY_ARG = 'corea11y_scan';

	/**
	 * Hook into WordPress.
	 */
	public static function register() {
		add_filter( 'show_admin_bar', array( __CLASS__, 'hide_admin_bar' ) );
		add_action( 'send_headers', array( __CLASS__, 'isolation_header' ) );
		add_action( 'send_headers', array( __CLASS__, 'allow_same_origin_frame' ), PHP_INT_MAX );
	}

	/**
	 * Some security plugins send "X-Frame-Options: DENY", which stops the scanner from
	 * opening the page in its same-origin frame. For pages loaded by the scanner, and only
	 * for administrators, relax that to SAMEORIGIN: the page can still never be framed by
	 * another site.
	 */
	public static function allow_same_origin_frame() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Header only; no data is changed.
		if ( ! isset( $_GET[ self::QUERY_ARG ] ) || headers_sent() || ! is_user_logged_in() || ! current_user_can( 'manage_options' ) ) {
			return;
		}
		foreach ( headers_list() as $header ) {
			if ( 0 === stripos( $header, 'X-Frame-Options:' ) && false === stripos( $header, 'sameorigin' ) ) {
				header_remove( 'X-Frame-Options' );
				header( 'X-Frame-Options: SAMEORIGIN' );
				break;
			}
		}
	}

	/**
	 * When the block editor checks a page, it is cross-origin isolated (WordPress 7.1+
	 * sends Document-Isolation-Policy for client-side media processing). The page being
	 * checked must use the same policy, or the editor can't read it.
	 */
	public static function isolation_header() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Header only; no data is changed.
		if ( isset( $_GET[ self::QUERY_ARG ] ) && 'isolated' === $_GET[ self::QUERY_ARG ] && is_user_logged_in() && current_user_can( 'edit_posts' ) && ! headers_sent() ) {
			header( 'Document-Isolation-Policy: isolate-and-credentialless' );
		}
	}

	/**
	 * Hide the admin bar on pages loaded by the scanner, so it isn't audited as site content.
	 *
	 * @param bool $show Whether to show the admin bar.
	 * @return bool
	 */
	public static function hide_admin_bar( $show ) {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only display change for administrators.
		if ( isset( $_GET[ self::QUERY_ARG ] ) && current_user_can( 'manage_options' ) ) {
			return false;
		}
		return $show;
	}

	/**
	 * Enqueue the scanner script on the dashboard.
	 */
	public static function enqueue() {
		wp_enqueue_script(
			'corea11y-scanner',
			COREA11Y_URL . 'assets/js/scanner.js',
			array( 'wp-api-fetch', 'wp-i18n' ),
			COREA11Y_VERSION,
			true
		);
		wp_set_script_translations( 'corea11y-scanner', 'corea11y', COREA11Y_DIR . 'languages' );
		wp_localize_script(
			'corea11y-scanner',
			'corea11yScanner',
			array(
				'axeUrl'    => COREA11Y_URL . 'assets/vendor/axe-core/axe.min.js?ver=' . rawurlencode( CoreA11y_Audit::axe_version() ),
				'queryArg'  => self::QUERY_ARG,
				'reportUrl' => admin_url( 'admin.php?page=corea11y-audits&audit=' ),
				'timeout'   => 30000,
				'mobile'    => (bool) CoreA11y_Settings::get( 'scan_mobile' ),
				'checksUrl' => COREA11Y_URL . 'assets/js/checks.js?ver=' . rawurlencode( COREA11Y_VERSION ),
				'extended'  => (bool) CoreA11y_Settings::get( 'extended_checks' ),
				'aceUrl'    => COREA11Y_URL . 'assets/vendor/ibm-equal-access/ace.js?ver=4.0.34',
				'widths'    => array(
					'desktop' => 1280,
					'mobile'  => 390,
				),
			)
		);
	}
}
