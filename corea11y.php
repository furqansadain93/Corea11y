<?php
/**
 * Plugin Name:       CoreA11y
 * Plugin URI:        https://github.com/furqansadain93/corea11y
 * Description:       Audit your site against WCAG 2.2 AA with axe-core, see exactly what needs fixing, and track progress with before/after reports.
 * Version:           0.2.0
 * Requires at least: 6.5
 * Requires PHP:      7.4
 * Author:            Strugbits
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       corea11y
 * Domain Path:       /languages
 *
 * @package CoreA11y
 */

defined( 'ABSPATH' ) || exit;

define( 'COREA11Y_VERSION', '0.2.0' );
define( 'COREA11Y_DB_VERSION', '2' );
define( 'COREA11Y_FILE', __FILE__ );
define( 'COREA11Y_DIR', plugin_dir_path( __FILE__ ) );
define( 'COREA11Y_URL', plugin_dir_url( __FILE__ ) );

require_once COREA11Y_DIR . 'includes/class-corea11y-settings.php';
require_once COREA11Y_DIR . 'includes/class-corea11y-install.php';
require_once COREA11Y_DIR . 'includes/class-corea11y-checklist.php';
require_once COREA11Y_DIR . 'includes/class-corea11y-rules.php';
require_once COREA11Y_DIR . 'includes/class-corea11y-pages.php';
require_once COREA11Y_DIR . 'includes/class-corea11y-audit.php';
require_once COREA11Y_DIR . 'includes/class-corea11y-server-checks.php';
require_once COREA11Y_DIR . 'includes/class-corea11y-scanner.php';
require_once COREA11Y_DIR . 'includes/class-corea11y-admin.php';
require_once COREA11Y_DIR . 'includes/class-corea11y-rest.php';
require_once COREA11Y_DIR . 'includes/class-corea11y.php';

register_activation_hook( __FILE__, array( 'CoreA11y_Install', 'activate' ) );

add_action( 'plugins_loaded', array( 'CoreA11y', 'instance' ) );
