<?php
/**
 * Block editor integration: checks post content on save.
 *
 * @package CoreA11y
 */

defined( 'ABSPATH' ) || exit;

/**
 * Loads the CoreA11y panel in the block editor.
 */
class CoreA11y_Editor {

	/**
	 * Hook into WordPress.
	 */
	public static function register() {
		add_action( 'enqueue_block_editor_assets', array( __CLASS__, 'enqueue' ) );
	}

	/**
	 * Enqueue the editor panel for post and page editing screens.
	 */
	public static function enqueue() {
		if ( ! current_user_can( 'edit_posts' ) ) {
			return;
		}
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( ! $screen || 'post' !== $screen->base ) {
			return;
		}

		wp_enqueue_script(
			'corea11y-editor',
			COREA11Y_URL . 'assets/js/editor.js',
			array( 'wp-plugins', 'wp-element', 'wp-data', 'wp-components', 'wp-i18n', 'wp-editor' ),
			COREA11Y_VERSION,
			true
		);
		wp_set_script_translations( 'corea11y-editor', 'corea11y', COREA11Y_DIR . 'languages' );

		$explain = array();
		foreach ( CoreA11y_Explain::all() as $rule => $e ) {
			$explain[ $rule ] = array(
				't' => $e['title'],
				'w' => $e['why'],
				'o' => $e['who'],
				'f' => $e['fix'],
			);
		}
		wp_localize_script(
			'corea11y-editor',
			'corea11yEditor',
			array(
				'axeUrl'     => COREA11Y_URL . 'assets/vendor/axe-core/axe.min.js?ver=' . rawurlencode( CoreA11y_Audit::axe_version() ),
				'queryArg'   => CoreA11y_Scanner::QUERY_ARG,
				'impacts'    => CoreA11y_Rules::impacts(),
				'explain'    => $explain,
				'genericFix' => __( 'Check this element against the WCAG requirement shown, then update it in the block settings or ask your theme or plugin developer.', 'corea11y' ),
			)
		);

		wp_register_style( 'corea11y-editor', false, array(), COREA11Y_VERSION );
		wp_enqueue_style( 'corea11y-editor' );
		wp_add_inline_style(
			'corea11y-editor',
			'.corea11y-ed-list{margin:8px 0 12px;padding:0;list-style:none}'
			. '.corea11y-ed-issue{border-left:4px solid #646970;padding:6px 0 6px 10px;margin:0 0 10px}'
			. '.corea11y-ed-critical{border-left-color:#b32d2e}.corea11y-ed-serious{border-left-color:#b45309}.corea11y-ed-moderate{border-left-color:#8a6d00}'
			. '.corea11y-ed-title{display:block}.corea11y-ed-meta{display:block;color:#50575e;font-size:12px;margin:2px 0}'
			. '.corea11y-ed-issue summary{cursor:pointer;color:#2271b1}.corea11y-ed-issue p{margin:6px 0}'
			. '.corea11y-ed-code{display:block;white-space:pre-wrap;word-break:break-all;font-size:11px;background:#f6f7f7;padding:4px 6px}'
			. '.corea11y-ed-ok{color:#00703c;font-weight:600}.corea11y-ed-running{display:flex;align-items:center;gap:6px}'
		);
	}
}
