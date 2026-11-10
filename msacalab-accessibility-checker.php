<?php
/**
 * Plugin Name:       MSACAlab Accessibility Checker
 * Description:       Audit your site against WCAG 2.2 AA with axe-core, see exactly what needs fixing, and track progress with before/after reports.
 * Version:           1.0.2
 * Requires at least: 6.5
 * Requires PHP:      7.4
 * Plugin URI:        https://corea11y.com/features/
 * Author:            MSACAlab
 * Author URI:        https://corea11y.com/about/
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       msacalab-accessibility-checker
 * Domain Path:       /languages
 *
 * @package CoreA11y
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'corea11y_other_copy_active' ) ) {
	/**
	 * Whether Pro (which includes everything in the free version) is active next to this free copy.
	 * The free version has no Freemius, so it steps aside itself instead of loading twice.
	 *
	 * @param string $file This copy's main file.
	 * @return bool
	 */
	function corea11y_other_copy_active( $file ) {
		if ( file_exists( dirname( $file ) . '/pro/corea11y-pro.php' ) ) {
			return false; // This copy is Pro.
		}
		$active = (array) get_option( 'active_plugins', array() );
		if ( is_multisite() ) {
			$active = array_merge( $active, array_keys( (array) get_site_option( 'active_sitewide_plugins', array() ) ) );
		}
		foreach ( $active as $plugin ) {
			if ( plugin_basename( $file ) !== $plugin && file_exists( WP_PLUGIN_DIR . '/' . dirname( (string) $plugin ) . '/pro/corea11y-pro.php' ) ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Admin notice shown by a copy that stepped aside.
	 */
	function corea11y_other_copy_notice() {
		if ( current_user_can( 'activate_plugins' ) ) {
			echo '<div class="notice notice-info"><p>' . esc_html__( 'MSACAlab Accessibility Checker Pro is active and includes everything in the free version, so the free version is paused. You can deactivate and delete the free version.', 'msacalab-accessibility-checker' ) . '</p></div>';
		}
	}
}

if ( corea11y_other_copy_active( __FILE__ ) || ( defined( 'COREA11Y_VERSION' ) && ! function_exists( 'corea11y_fs' ) ) ) {
	// Pro, or another copy of the plugin, is already running: this copy stays off.
	add_action( 'admin_notices', 'corea11y_other_copy_notice' );
	add_action( 'network_admin_notices', 'corea11y_other_copy_notice' );
} elseif ( function_exists( 'corea11y_fs' ) ) {
	// Lite and Pro are both installed: let Freemius switch off the other copy
	// instead of loading MSACAlab Accessibility Checker twice.
	corea11y_fs()->set_basename( file_exists( __DIR__ . '/pro/corea11y-pro.php' ), __FILE__ );
} else {
	define( 'COREA11Y_VERSION', '1.0.2' );
	define( 'COREA11Y_DB_VERSION', '3' );
	define( 'COREA11Y_FILE', __FILE__ );
	define( 'COREA11Y_DIR', plugin_dir_path( __FILE__ ) );
	define( 'COREA11Y_URL', plugin_dir_url( __FILE__ ) );

	/*
	 * DO NOT REMOVE THIS IF: it is essential for the `function_exists` call above.
	 */
	if ( ! function_exists( 'corea11y_fs' ) && file_exists( COREA11Y_DIR . 'freemius/start.php' ) ) {
		/**
		 * Freemius instance (licensing and updates for MSACAlab Accessibility Checker Pro).
		 *
		 * The free version runs in anonymous mode: no opt-in screen and nothing is sent
		 * about the site. Pro only talks to Freemius to activate and check its license.
		 *
		 * @return Freemius
		 */
		function corea11y_fs() {
			global $corea11y_fs;

			if ( ! isset( $corea11y_fs ) ) {
				require_once COREA11Y_DIR . 'freemius/start.php';

				$corea11y_fs = fs_dynamic_init(
					array(
						'id'                  => '42231',
						'slug'                => 'msacalab-accessibility-checker',
						'premium_slug'        => 'msacalab-accessibility-checker-pro',
						'type'                => 'plugin',
						'public_key'          => 'pk_873c05c9e529acc396b699bda1c93',
						'is_premium'          => file_exists( COREA11Y_DIR . 'pro/corea11y-pro.php' ),
						'premium_suffix'      => 'Pro',
						'has_premium_version' => true,
						'has_addons'          => false,
						'has_paid_plans'      => true,
						'is_org_compliant'    => true,
						'anonymous_mode'      => ! file_exists( COREA11Y_DIR . 'pro/corea11y-pro.php' ),
						'menu'                => array(
							'slug'       => 'corea11y',
							'first-path' => 'admin.php?page=corea11y',
							'support'    => true,
						),
					)
				);
			}

			return $corea11y_fs;
		}

		corea11y_fs();
		// Clean up on delete through Freemius, so its uninstall feedback still works.
		corea11y_fs()->add_action( 'after_uninstall', array( 'CoreA11y_Install', 'uninstall' ) );
		// MSACAlab Accessibility Checker branding on the Upgrade (pricing) page.
		corea11y_fs()->add_filter(
			'pricing/css_path',
			static function () {
				return COREA11Y_DIR . 'assets/css/pricing.css';
			}
		);
		do_action( 'corea11y_fs_loaded' );
	}

	// Where the free version's "See MSACAlab Accessibility Checker Pro" link goes.
	add_filter(
		'corea11y_upgrade_url',
		static function () {
			return function_exists( 'corea11y_fs' ) ? corea11y_fs()->get_upgrade_url() : 'https://corea11y.com/pricing/';
		}
	);

	/**
	 * Version string for a MSACAlab Accessibility Checker asset: the plugin version plus the file's last change,
	 * so browsers never keep an outdated copy after an update.
	 *
	 * @param string $rel Path relative to the plugin folder.
	 * @return string
	 */
	function corea11y_asset_ver( $rel ) {
		$t = @filemtime( COREA11Y_DIR . $rel ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged -- Missing file falls back to the version.
		return $t ? COREA11Y_VERSION . '.' . $t : COREA11Y_VERSION;
	}

	require_once COREA11Y_DIR . 'includes/class-corea11y-settings.php';
	require_once COREA11Y_DIR . 'includes/class-corea11y-install.php';
	require_once COREA11Y_DIR . 'includes/class-corea11y-checklist.php';
	require_once COREA11Y_DIR . 'includes/class-corea11y-rules.php';
	require_once COREA11Y_DIR . 'includes/class-corea11y-pages.php';
	require_once COREA11Y_DIR . 'includes/class-corea11y-audit.php';
	require_once COREA11Y_DIR . 'includes/class-corea11y-server-checks.php';
	require_once COREA11Y_DIR . 'includes/class-corea11y-scanner.php';
	require_once COREA11Y_DIR . 'includes/class-corea11y-explain.php';
	require_once COREA11Y_DIR . 'includes/class-corea11y-ui.php';
	require_once COREA11Y_DIR . 'includes/class-corea11y-compare.php';
	require_once COREA11Y_DIR . 'includes/class-corea11y-fixes.php';
	require_once COREA11Y_DIR . 'includes/class-corea11y-pro-catalog.php';
	require_once COREA11Y_DIR . 'includes/class-corea11y-alt.php';
	require_once COREA11Y_DIR . 'includes/class-corea11y-compat.php';
	require_once COREA11Y_DIR . 'includes/class-corea11y-dismiss.php';
	require_once COREA11Y_DIR . 'includes/class-corea11y-statement.php';
	require_once COREA11Y_DIR . 'includes/class-corea11y-editor.php';
	require_once COREA11Y_DIR . 'includes/class-corea11y-highlighter.php';
	require_once COREA11Y_DIR . 'includes/class-corea11y-admin.php';
	require_once COREA11Y_DIR . 'includes/class-corea11y-rest.php';
	require_once COREA11Y_DIR . 'includes/class-corea11y.php';

	// MSACAlab Accessibility Checker Pro ships in the same plugin; the free version leaves this folder out.
	// Pro features run only with an active license. Fixes already applied keep working.
	if ( file_exists( COREA11Y_DIR . 'pro/corea11y-pro.php' ) ) {
		require_once COREA11Y_DIR . 'pro/corea11y-pro.php';
	}

	register_activation_hook( __FILE__, array( 'CoreA11y_Install', 'activate' ) );
	// Fixes are applied as pages are shown, so they stop the moment MSACAlab Accessibility Checker is deactivated.
	// Clear page caches too, so visitors don't keep seeing cached copies with the fixes.
	register_deactivation_hook( __FILE__, array( 'CoreA11y_Compat', 'purge_caches' ) );

	add_action( 'plugins_loaded', array( 'CoreA11y', 'instance' ) );

	// Bundled German, French and Spanish translations. Language packs from translate.wordpress.org,
	// when present, take priority. Sites in English are not affected.
	add_action(
		'init',
		static function () {
			load_plugin_textdomain( 'msacalab-accessibility-checker', false, dirname( plugin_basename( COREA11Y_FILE ) ) . '/languages' );
		},
		1
	);
}
