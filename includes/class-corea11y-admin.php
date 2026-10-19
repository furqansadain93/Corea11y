<?php
/**
 * Admin menu and screens.
 *
 * @package CoreA11y
 */

defined( 'ABSPATH' ) || exit;

/**
 * Registers the CoreA11y admin menu and renders its screens.
 */
class CoreA11y_Admin {

	const CAP  = 'manage_options';
	const SLUG = 'corea11y';

	/**
	 * Hook into WordPress.
	 */
	public function register() {
		add_action( 'admin_menu', array( $this, 'menu' ) );
		add_action( 'admin_init', array( 'CoreA11y_Settings', 'register' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'assets' ) );
		add_action( 'admin_post_corea11y_save_checklist', array( $this, 'save_checklist' ) );
		add_filter( 'plugin_action_links_' . plugin_basename( COREA11Y_FILE ), array( $this, 'action_links' ) );
	}

	/**
	 * Admin menu and submenus.
	 */
	public function menu() {
		add_menu_page(
			__( 'CoreA11y', 'corea11y' ),
			__( 'CoreA11y', 'corea11y' ),
			self::CAP,
			self::SLUG,
			array( $this, 'render_dashboard' ),
			'dashicons-universal-access-alt',
			80
		);
		add_submenu_page( self::SLUG, __( 'Dashboard', 'corea11y' ), __( 'Dashboard', 'corea11y' ), self::CAP, self::SLUG, array( $this, 'render_dashboard' ) );
		add_submenu_page( self::SLUG, __( 'Audits', 'corea11y' ), __( 'Audits', 'corea11y' ), self::CAP, 'corea11y-audits', array( $this, 'render_audits' ) );
		add_submenu_page( self::SLUG, __( 'Manual checklist', 'corea11y' ), __( 'Manual checklist', 'corea11y' ), self::CAP, 'corea11y-checklist', array( $this, 'render_checklist' ) );
		add_submenu_page( self::SLUG, __( 'Settings', 'corea11y' ), __( 'Settings', 'corea11y' ), self::CAP, CoreA11y_Settings::PAGE, array( $this, 'render_settings' ) );
	}

	/**
	 * Enqueue admin styles on CoreA11y screens only.
	 *
	 * @param string $hook Current admin page hook.
	 */
	public function assets( $hook ) {
		if ( false === strpos( $hook, 'corea11y' ) ) {
			return;
		}
		wp_enqueue_style( 'corea11y-admin', COREA11Y_URL . 'assets/css/admin.css', array(), COREA11Y_VERSION );
		if ( 'toplevel_page_' . self::SLUG === $hook ) {
			CoreA11y_Scanner::enqueue();
		}
	}

	/**
	 * "Settings" link on the Plugins screen.
	 *
	 * @param array $links Existing links.
	 * @return array
	 */
	public function action_links( $links ) {
		$url = admin_url( 'admin.php?page=' . CoreA11y_Settings::PAGE );
		array_unshift( $links, '<a href="' . esc_url( $url ) . '">' . esc_html__( 'Settings', 'corea11y' ) . '</a>' );
		return $links;
	}

	/**
	 * Most recent audits.
	 *
	 * @param int $limit Max rows.
	 * @return array
	 */
	public static function recent_audits( $limit = 20, $status = '' ) {
		global $wpdb;
		$table = CoreA11y_Install::tables()['audits'];
		if ( $status ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom table, read on admin screens only.
			return $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM %i WHERE status = %s ORDER BY id DESC LIMIT %d', $table, $status, $limit ) );
		}
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom table, read on admin screens only.
		return $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM %i ORDER BY id DESC LIMIT %d', $table, $limit ) );
	}

	/**
	 * Load a view file.
	 *
	 * @param string $view View name.
	 * @param array  $vars Variables for the view.
	 */
	private function view( $view, $vars = array() ) {
		// phpcs:ignore WordPress.PHP.DontExtract.extract_extract -- Controlled, internal variables only.
		extract( $vars, EXTR_SKIP );
		include COREA11Y_DIR . 'admin/views/' . $view . '.php';
	}

	/**
	 * Dashboard screen.
	 */
	public function render_dashboard() {
		$audits = self::recent_audits( 2, 'complete' );
		$this->view(
			'dashboard',
			array(
				'latest'   => isset( $audits[0] ) ? $audits[0] : null,
				'previous' => isset( $audits[1] ) ? $audits[1] : null,
			)
		);
	}

	/**
	 * Audits screen.
	 */
	public function render_audits() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only view selection.
		$id = isset( $_GET['audit'] ) ? absint( $_GET['audit'] ) : 0;
		if ( $id ) {
			$audit = CoreA11y_Audit::get( $id );
			if ( $audit ) {
				$this->view(
					'report',
					array(
						'audit'  => $audit,
						'counts' => CoreA11y_Audit::counts( $id ),
						'groups' => CoreA11y_Audit::grouped_issues( $id ),
					)
				);
				return;
			}
		}
		$this->view( 'audits', array( 'audits' => self::recent_audits() ) );
	}

	/**
	 * Link to an audit report.
	 *
	 * @param int $id Audit id.
	 * @return string
	 */
	public static function report_url( $id ) {
		return admin_url( 'admin.php?page=corea11y-audits&audit=' . absint( $id ) );
	}

	/**
	 * Settings screen.
	 */
	public function render_settings() {
		$this->view( 'settings' );
	}

	/**
	 * Manual checklist screen.
	 */
	public function render_checklist() {
		$this->view(
			'checklist',
			array(
				'items'   => CoreA11y_Checklist::items(),
				'results' => CoreA11y_Checklist::results(),
			)
		);
	}

	/**
	 * Save checklist results (admin-post handler).
	 */
	public function save_checklist() {
		if ( ! current_user_can( self::CAP ) ) {
			wp_die( esc_html__( 'You do not have permission to do this.', 'corea11y' ), 403 );
		}
		check_admin_referer( 'corea11y_save_checklist' );

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Sanitized in CoreA11y_Checklist::save().
		$raw = isset( $_POST['corea11y_check'] ) ? wp_unslash( $_POST['corea11y_check'] ) : array();
		CoreA11y_Checklist::save( is_array( $raw ) ? $raw : array() );

		wp_safe_redirect( add_query_arg( 'updated', '1', admin_url( 'admin.php?page=corea11y-checklist' ) ) );
		exit;
	}
}
