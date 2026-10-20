<?php
/**
 * Main plugin bootstrap.
 *
 * @package CoreA11y
 */

defined( 'ABSPATH' ) || exit;

/**
 * Wires up the plugin's components. Pro extends Lite through the
 * `corea11y_loaded` action rather than by replacing classes.
 */
final class CoreA11y {

	/**
	 * Single instance.
	 *
	 * @var CoreA11y|null
	 */
	private static $instance = null;

	/**
	 * Get (and boot on first call) the plugin instance.
	 *
	 * @return CoreA11y
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
			self::$instance->boot();
		}
		return self::$instance;
	}

	/**
	 * Hook components.
	 */
	private function boot() {
		CoreA11y_Install::maybe_upgrade();

		( new CoreA11y_Rest() )->register();
		CoreA11y_Scanner::register();
		CoreA11y_Fixes::register();
		CoreA11y_Compat::register();
		CoreA11y_Statement::register();
		CoreA11y_Editor::register();
		CoreA11y_Highlighter::register();

		if ( is_admin() ) {
			( new CoreA11y_Admin() )->register();
		}

		/**
		 * Fires after CoreA11y Lite has loaded. CoreA11y Pro hooks in here.
		 *
		 * @param CoreA11y $plugin The plugin instance.
		 */
		do_action( 'corea11y_loaded', $this );
	}
}
