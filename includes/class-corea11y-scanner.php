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
			)
		);
	}
}
