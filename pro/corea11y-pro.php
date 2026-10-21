<?php
/**
 * CoreA11y Pro.
 *
 * Extends CoreA11y Lite through its hooks. Every Pro quick fix follows the same rules
 * as Lite: it is applied as pages are shown, never written into theme or content files,
 * logged in the change history, and can be undone with one click.
 *
 * @package CoreA11y
 */

defined( 'ABSPATH' ) || exit;

// Lite and Pro are one plugin, so they share a version number.
define( 'COREA11Y_PRO_VERSION', COREA11Y_VERSION );
define( 'COREA11Y_PRO_DIR', __DIR__ . '/' );
define( 'COREA11Y_PRO_URL', plugin_dir_url( __FILE__ ) );

require_once COREA11Y_PRO_DIR . 'includes/class-corea11y-pro-license.php';
require_once COREA11Y_PRO_DIR . 'includes/class-corea11y-pro-fixes.php';
require_once COREA11Y_PRO_DIR . 'includes/class-corea11y-pro-settings.php';
require_once COREA11Y_PRO_DIR . 'includes/class-corea11y-pro-reports.php';
require_once COREA11Y_PRO_DIR . 'includes/class-corea11y-pro-monitor.php';
require_once COREA11Y_PRO_DIR . 'includes/class-corea11y-pro-network.php';
require_once COREA11Y_PRO_DIR . 'includes/class-corea11y-pro-contrast.php';

add_action(
	'corea11y_loaded',
	static function () {
		CoreA11y_Pro_License::register();
		CoreA11y_Pro_Fixes::register();
		CoreA11y_Pro_Settings::register();
		CoreA11y_Pro_Reports::register();
		CoreA11y_Pro_Monitor::register();
		CoreA11y_Pro_Network::register();
		CoreA11y_Pro_Contrast::register();
	}
);
