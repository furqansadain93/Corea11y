<?php
/**
 * Plugin Name:       CoreA11y
 * Description:       Audit your site against WCAG 2.2 AA with axe-core, see exactly what needs fixing, and track progress with before/after reports.
 * Version:           1.0.0
 * Requires at least: 6.5
 * Requires PHP:      7.4
 * Plugin URI:        https://corea11y.com
 * Author:            MSACAlab
 * Author URI:        https://corea11y.com
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       corea11y
 * Domain Path:       /languages
 *
 * @package CoreA11y
 */

defined( 'ABSPATH' ) || exit;

if ( function_exists( 'corea11y_fs' ) ) {
	// Lite and Pro are both installed: let Freemius switch off the other copy
	// instead of loading CoreA11y twice.
	corea11y_fs()->set_basename( file_exists( __DIR__ . '/pro/corea11y-pro.php' ), __FILE__ );
} else {
	define( 'COREA11Y_VERSION', '1.0.0' );
	define( 'COREA11Y_DB_VERSION', '3' );
	define( 'COREA11Y_FILE', __FILE__ );
	define( 'COREA11Y_DIR', plugin_dir_path( __FILE__ ) );
	define( 'COREA11Y_URL', plugin_dir_url( __FILE__ ) );

	/*
	 * DO NOT REMOVE THIS IF: it is essential for the `function_exists` call above.
	 */
	if ( ! function_exists( 'corea11y_fs' ) && file_exists( COREA11Y_DIR . 'freemius/start.php' ) ) {
		/**
		 * Freemius instance (licensing and updates for CoreA11y Pro).
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
						'slug'                => 'corea11y',
						'premium_slug'        => 'corea11y-pro',
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
		// CoreA11y branding on the Upgrade (pricing) page.
		corea11y_fs()->add_filter(
			'pricing/css_path',
			static function () {
				return COREA11Y_DIR . 'assets/css/pricing.css';
			}
		);
		do_action( 'corea11y_fs_loaded' );
	}

	// Where the free version's "See CoreA11y Pro" link goes.
	add_filter(
		'corea11y_upgrade_url',
		static function () {
			return function_exists( 'corea11y_fs' ) ? corea11y_fs()->get_upgrade_url() : 'https://corea11y.com/pricing/';
		}
	);

	/**
	 * Version string for a CoreA11y asset: the plugin version plus the file's last change,
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
	require_once COREA11Y_DIR . 'includes/class-corea11y-alt.php';
	require_once COREA11Y_DIR . 'includes/class-corea11y-compat.php';
	require_once COREA11Y_DIR . 'includes/class-corea11y-dismiss.php';
	require_once COREA11Y_DIR . 'includes/class-corea11y-statement.php';
	require_once COREA11Y_DIR . 'includes/class-corea11y-editor.php';
	require_once COREA11Y_DIR . 'includes/class-corea11y-highlighter.php';
	require_once COREA11Y_DIR . 'includes/class-corea11y-admin.php';
	require_once COREA11Y_DIR . 'includes/class-corea11y-rest.php';
	require_once COREA11Y_DIR . 'includes/class-corea11y.php';

	// CoreA11y Pro ships in the same plugin; the free version leaves this folder out.
	// Pro features run only with an active license. Fixes already applied keep working.
	if ( file_exists( COREA11Y_DIR . 'pro/corea11y-pro.php' ) ) {
		require_once COREA11Y_DIR . 'pro/corea11y-pro.php';
	}

	register_activation_hook( __FILE__, array( 'CoreA11y_Install', 'activate' ) );
	// Fixes are applied as pages are shown, so they stop the moment CoreA11y is deactivated.
	// Clear page caches too, so visitors don't keep seeing cached copies with the fixes.
	register_deactivation_hook( __FILE__, array( 'CoreA11y_Compat', 'purge_caches' ) );

	add_action( 'plugins_loaded', array( 'CoreA11y', 'instance' ) );
}
