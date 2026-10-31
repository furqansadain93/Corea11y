<?php
/**
 * Removes the plugin's data when the free version is deleted (if the owner allowed it in Settings).
 * Pro cleans up through Freemius instead, so the Pro package leaves this file out.
 *
 * @package CoreA11y
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

require_once __DIR__ . '/includes/class-corea11y-install.php';
CoreA11y_Install::uninstall();
