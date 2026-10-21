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
		add_action( 'admin_post_corea11y_export', array( $this, 'export' ) );
		add_action( 'admin_post_corea11y_save_fixes', array( $this, 'save_fixes' ) );
		add_action( 'admin_post_corea11y_fix_now', array( $this, 'fix_now' ) );
		add_action( 'admin_post_corea11y_undo_fix', array( $this, 'undo_fix' ) );
		add_action( 'admin_post_corea11y_dismiss', array( $this, 'dismiss_issue' ) );
		add_action( 'admin_post_corea11y_save_statement', array( $this, 'save_statement' ) );
		add_action( 'admin_post_corea11y_restore', array( $this, 'restore_issue' ) );
		add_action( 'admin_post_corea11y_save_alt', array( $this, 'save_alt' ) );
		add_action( 'admin_post_corea11y_undo_alt', array( $this, 'undo_alt' ) );
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
		add_submenu_page( self::SLUG, __( 'Quick fixes', 'corea11y' ), __( 'Quick fixes', 'corea11y' ), self::CAP, 'corea11y-fixes', array( $this, 'render_fixes' ) );
		add_submenu_page( self::SLUG, __( 'Alt text', 'corea11y' ), __( 'Alt text', 'corea11y' ), self::CAP, 'corea11y-alt', array( $this, 'render_alt' ) );
		add_submenu_page( self::SLUG, __( 'Audits', 'corea11y' ), __( 'Audits', 'corea11y' ), self::CAP, 'corea11y-audits', array( $this, 'render_audits' ) );
		add_submenu_page( self::SLUG, __( 'Before & after', 'corea11y' ), __( 'Before & after', 'corea11y' ), self::CAP, 'corea11y-compare', array( $this, 'render_compare' ) );
		add_submenu_page( self::SLUG, __( 'Accessibility statement', 'corea11y' ), __( 'Statement', 'corea11y' ), self::CAP, 'corea11y-statement', array( $this, 'render_statement' ) );
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
		wp_enqueue_script( 'corea11y-admin', COREA11Y_URL . 'assets/js/admin.js', array(), COREA11Y_VERSION, true );
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
				$this->view( 'report', self::report_data( $audit ) );
				return;
			}
		}
		$this->view( 'audits', array( 'audits' => self::recent_audits() ) );
	}

	/**
	 * Variables for the report body.
	 *
	 * @param object $audit Audit.
	 * @return array
	 */
	public static function report_data( $audit ) {
		$id = (int) $audit->id;
		return array(
			'audit'      => $audit,
			'counts'     => CoreA11y_Audit::counts( $id ),
			'groups'     => CoreA11y_Audit::grouped_issues( $id ),
			'principles' => CoreA11y_Audit::principle_counts( $id ),
			'top_pages'  => CoreA11y_Audit::page_counts( $id, 10 ),
			'dismissed'  => CoreA11y_Dismiss::for_audit( $id ),
			'previous'   => self::previous_audit( $id ),
		);
	}

	/**
	 * The completed audit before a given one.
	 *
	 * @param int $id Audit id.
	 * @return object|null
	 */
	public static function previous_audit( $id ) {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom table.
		return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM %i WHERE status = 'complete' AND id < %d ORDER BY id DESC LIMIT 1", CoreA11y_Install::tables()['audits'], $id ) );
	}

	/**
	 * Quick fixes screen.
	 */
	public function render_fixes() {
		global $wpdb;
		$fixes  = CoreA11y_Fixes::all();
		$latest = self::recent_audits( 1, 'complete' );
		$latest = $latest ? $latest[0] : null;
		$found  = array();
		if ( $latest ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom table.
			$rows = $wpdb->get_results( $wpdb->prepare( "SELECT rule_id, COUNT(*) AS n FROM %i WHERE audit_id = %d AND status <> 'dismissed' GROUP BY rule_id", CoreA11y_Install::tables()['issues'], $latest->id ) );
			foreach ( $rows as $r ) {
				$fix = CoreA11y_Fixes::for_rule( $r->rule_id );
				if ( $fix ) {
					$found[ $fix ] = ( isset( $found[ $fix ] ) ? $found[ $fix ] : 0 ) + (int) $r->n;
				}
			}
		}
		// Pages to re-check: right after a save, the fixes just turned on (checked
		// automatically); otherwise, fixes that are on but haven't been checked yet.
		$state    = CoreA11y_Fixes::state();
		$verified = CoreA11y_Fixes::verifications();
		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- Display only.
		$batch     = isset( $_GET['batch'] ) ? sanitize_key( wp_unslash( $_GET['batch'] ) ) : '';
		$just_on   = array();
		$autostart = false;
		if ( $batch && ( isset( $_GET['saved'] ) || isset( $_GET['redone'] ) ) ) {
			foreach ( CoreA11y_Fixes::batch_entries( $batch ) as $e ) {
				if ( ! empty( $e['on'] ) ) {
					$just_on[] = $e['fix'];
				}
			}
			$autostart = (bool) $just_on;
		}
		// phpcs:enable
		$ids  = $just_on ? $just_on : array_keys( array_diff_key( array_filter( $state ), $verified ) );
		$plan = CoreA11y_Fixes::verify_plan( $ids );
		if ( $plan['fixes'] ) {
			CoreA11y_Scanner::enqueue_verify( $plan );
		}
		$this->view(
			'fixes',
			array(
				'fixes'     => $fixes,
				'state'     => $state,
				'found'     => $found,
				'latest'    => $latest,
				'plan'      => $plan,
				'autostart' => $autostart,
				'verified'  => $verified,
				'log'    => CoreA11y_Fixes::log(),
			)
		);
	}

	/**
	 * Save quick fixes (admin-post handler).
	 */
	public function save_fixes() {
		if ( ! current_user_can( self::CAP ) ) {
			wp_die( esc_html__( 'You do not have permission to do this.', 'corea11y' ), 403 );
		}
		check_admin_referer( 'corea11y_save_fixes' );

		$undo_one = isset( $_POST['corea11y_undo_fix'] ) ? sanitize_key( wp_unslash( $_POST['corea11y_undo_fix'] ) ) : '';
		if ( $undo_one ) {
			$batch = CoreA11y_Fixes::set( $undo_one, false, 'undo' );
			$flag  = 'undone';
		} else {
			$new = array();
			if ( empty( $_POST['corea11y_all_off'] ) && isset( $_POST['corea11y_fix'] ) && is_array( $_POST['corea11y_fix'] ) ) {
				$posted = array_map( 'sanitize_key', array_keys( wp_unslash( $_POST['corea11y_fix'] ) ) ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Keys sanitized with sanitize_key; values unused.
				foreach ( array_keys( CoreA11y_Fixes::all() ) as $id ) {
					$new[ $id ] = in_array( $id, $posted, true );
				}
			}
			$batch = CoreA11y_Fixes::save( $new );
			$flag  = 'saved';
		}

		self::fixes_redirect( $flag, $batch );
	}

	/**
	 * "Fix them now": turn on every quick fix that solves issues in the latest audit,
	 * then open the Quick fixes screen, which checks that they worked.
	 */
	public function fix_now() {
		if ( ! current_user_can( self::CAP ) ) {
			wp_die( esc_html__( 'You do not have permission to do this.', 'corea11y' ), 403 );
		}
		check_admin_referer( 'corea11y_fix_now' );
		$latest = self::recent_audits( 1, 'complete' );
		$batch  = '';
		if ( $latest ) {
			$opp = CoreA11y_Fixes::opportunity( (int) $latest[0]->id );
			if ( $opp['fixes'] ) {
				$state = CoreA11y_Fixes::state();
				foreach ( $opp['fixes'] as $id ) {
					$state[ $id ] = true;
				}
				$batch = CoreA11y_Fixes::save( $state );
			}
		}
		self::fixes_redirect( 'saved', $batch );
	}

	/**
	 * Alt text screen.
	 */
	public function render_alt() {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- Display-only filters and flags.
		$show  = isset( $_GET['show'] ) && 'all' === $_GET['show'] ? 'all' : 'used';
		$paged = isset( $_GET['paged'] ) ? max( 1, absint( $_GET['paged'] ) ) : 1;
		// phpcs:enable
		$rows = CoreA11y_Alt::rows( $show );
		$this->view(
			'alt',
			array(
				'rows'  => array_slice( $rows, ( $paged - 1 ) * CoreA11y_Alt::PER_PAGE, CoreA11y_Alt::PER_PAGE ),
				'total' => count( $rows ),
				'paged' => $paged,
				'show'  => $show,
				'log'   => CoreA11y_Alt::log(),
			)
		);
	}

	/**
	 * Save alt text (admin-post handler).
	 */
	public function save_alt() {
		if ( ! current_user_can( self::CAP ) ) {
			wp_die( esc_html__( 'You do not have permission to do this.', 'corea11y' ), 403 );
		}
		check_admin_referer( 'corea11y_save_alt' );
		$alts = isset( $_POST['alt'] ) && is_array( $_POST['alt'] ) ? wp_unslash( $_POST['alt'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Sanitized per value in CoreA11y_Alt::save().
		$deco = isset( $_POST['decorative'] ) && is_array( $_POST['decorative'] ) ? wp_unslash( $_POST['decorative'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Only keys and truthiness are used.
		$res  = CoreA11y_Alt::save( $alts, $deco );
		$args = array(
			'page'   => 'corea11y-alt',
			'saved'  => $res['images'],
			'posts'  => $res['posts'],
			'batch'  => $res['batch'],
		);
		if ( isset( $_POST['show'] ) && 'all' === $_POST['show'] ) {
			$args['show'] = 'all';
		}
		wp_safe_redirect( add_query_arg( $args, admin_url( 'admin.php' ) ) );
		exit;
	}

	/**
	 * Undo an alt text save (admin-post handler).
	 */
	public function undo_alt() {
		if ( ! current_user_can( self::CAP ) ) {
			wp_die( esc_html__( 'You do not have permission to do this.', 'corea11y' ), 403 );
		}
		check_admin_referer( 'corea11y_undo_alt' );
		$batch = isset( $_POST['batch'] ) ? sanitize_text_field( wp_unslash( $_POST['batch'] ) ) : '';
		$done  = CoreA11y_Alt::undo( $batch );
		wp_safe_redirect( add_query_arg( array( 'page' => 'corea11y-alt', 'undone' => $done ? 1 : 0 ), admin_url( 'admin.php' ) ) );
		exit;
	}

	/**
	 * Accessibility statement screen.
	 */
	public function render_statement() {
		$latest = self::recent_audits( 1, 'complete' );
		$this->view(
			'statement',
			array(
				's'      => CoreA11y_Statement::get(),
				'page'   => CoreA11y_Statement::page(),
				'latest' => $latest ? $latest[0] : null,
			)
		);
	}

	/**
	 * Save the statement and optionally create its page (admin-post handler).
	 */
	public function save_statement() {
		if ( ! current_user_can( self::CAP ) ) {
			wp_die( esc_html__( 'You do not have permission to do this.', 'corea11y' ), 403 );
		}
		check_admin_referer( 'corea11y_save_statement' );
		$input = isset( $_POST['statement'] ) && is_array( $_POST['statement'] ) ? wp_unslash( $_POST['statement'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Sanitized field by field in CoreA11y_Statement::save().
		CoreA11y_Statement::save( $input );
		$flag = 'saved';
		if ( isset( $_POST['do'] ) && 'create' === $_POST['do'] && current_user_can( 'publish_pages' ) ) {
			$id   = CoreA11y_Statement::create_page();
			$flag = is_wp_error( $id ) ? 'saved' : 'created';
		}
		wp_safe_redirect( add_query_arg( 'statement', $flag, admin_url( 'admin.php?page=corea11y-statement' ) ) );
		exit;
	}

	/**
	 * Dismiss an issue (admin-post handler).
	 */
	public function dismiss_issue() {
		if ( ! current_user_can( self::CAP ) ) {
			wp_die( esc_html__( 'You do not have permission to do this.', 'corea11y' ), 403 );
		}
		check_admin_referer( 'corea11y_dismiss' );
		$audit  = isset( $_POST['audit'] ) ? absint( $_POST['audit'] ) : 0;
		$reason = isset( $_POST['reason'] ) ? sanitize_key( wp_unslash( $_POST['reason'] ) ) : '';
		$note   = isset( $_POST['note'] ) ? sanitize_textarea_field( wp_unslash( $_POST['note'] ) ) : '';
		$fp     = CoreA11y_Dismiss::dismiss( isset( $_POST['issue'] ) ? absint( $_POST['issue'] ) : 0, $reason, $note );
		$args   = is_wp_error( $fp ) ? array( 'dismiss_error' => '1' ) : array( 'dismissed' => $fp );
		wp_safe_redirect( add_query_arg( $args, self::report_url( $audit ) ) . '#corea11y-notice' );
		exit;
	}

	/**
	 * Restore a dismissed issue (admin-post handler).
	 */
	public function restore_issue() {
		if ( ! current_user_can( self::CAP ) ) {
			wp_die( esc_html__( 'You do not have permission to do this.', 'corea11y' ), 403 );
		}
		check_admin_referer( 'corea11y_restore' );
		$audit = isset( $_POST['audit'] ) ? absint( $_POST['audit'] ) : 0;
		$fp    = isset( $_POST['fp'] ) ? sanitize_key( wp_unslash( $_POST['fp'] ) ) : '';
		CoreA11y_Dismiss::restore( $fp );
		wp_safe_redirect( add_query_arg( 'restored', '1', self::report_url( $audit ) ) . '#corea11y-notice' );
		exit;
	}

	/**
	 * Undo a logged change or a whole batch (admin-post handler).
	 */
	public function undo_fix() {
		if ( ! current_user_can( self::CAP ) ) {
			wp_die( esc_html__( 'You do not have permission to do this.', 'corea11y' ), 403 );
		}
		check_admin_referer( 'corea11y_undo' );

		$entry = isset( $_POST['entry'] ) ? sanitize_key( wp_unslash( $_POST['entry'] ) ) : '';
		$batch = isset( $_POST['batch'] ) ? sanitize_key( wp_unslash( $_POST['batch'] ) ) : '';
		$redo  = ! empty( $_POST['redo'] );
		self::fixes_redirect( $redo ? 'redone' : 'undone', CoreA11y_Fixes::undo( $entry, $batch ) );
	}

	/**
	 * Back to the Quick fixes screen with a result message.
	 *
	 * @param string $flag  saved|undone|redone.
	 * @param string $batch Batch id of the change ('' if nothing changed).
	 */
	private static function fixes_redirect( $flag, $batch ) {
		$args = array( 'page' => 'corea11y-fixes' );
		if ( $batch ) {
			$args[ $flag ] = '1';
			$args['batch'] = $batch;
		} else {
			$args['nochange'] = '1';
		}
		wp_safe_redirect( add_query_arg( $args, admin_url( 'admin.php' ) ) );
		exit;
	}

	/**
	 * Hidden form fields for an undo button.
	 *
	 * @param string $entry Log entry id.
	 * @param string $batch Batch id.
	 * @param bool   $redo  Whether this undoes an undo.
	 */
	public static function undo_fields( $entry = '', $batch = '', $redo = false ) {
		echo '<input type="hidden" name="action" value="corea11y_undo_fix">';
		if ( $redo ) {
			echo '<input type="hidden" name="redo" value="1">';
		}
		wp_nonce_field( 'corea11y_undo' );
		if ( $entry ) {
			echo '<input type="hidden" name="entry" value="' . esc_attr( $entry ) . '">';
		}
		if ( $batch ) {
			echo '<input type="hidden" name="batch" value="' . esc_attr( $batch ) . '">';
		}
	}

	/**
	 * Comparison screen.
	 */
	public function render_compare() {
		$audits = self::recent_audits( 100, 'complete' );
		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- Read-only view selection.
		$after_id  = isset( $_GET['after'] ) ? absint( $_GET['after'] ) : ( isset( $audits[0] ) ? (int) $audits[0]->id : 0 );
		$before_id = isset( $_GET['before'] ) ? absint( $_GET['before'] ) : ( isset( $audits[1] ) ? (int) $audits[1]->id : 0 );
		// phpcs:enable
		$cmp = null;
		if ( $before_id && $after_id ) {
			$cmp = $before_id === $after_id
				? new WP_Error( 'corea11y_same', __( 'Pick two different audits to compare.', 'corea11y' ) )
				: CoreA11y_Compare::build( min( $before_id, $after_id ), max( $before_id, $after_id ) );
		}
		$this->view(
			'compare',
			array(
				'audits'    => $audits,
				'before_id' => min( $before_id, $after_id ) ? min( $before_id, $after_id ) : $before_id,
				'after_id'  => max( $before_id, $after_id ),
				'cmp'       => $cmp,
			)
		);
	}

	/**
	 * URL of the comparison screen.
	 *
	 * @param int $before Earlier audit id.
	 * @param int $after  Later audit id.
	 * @return string
	 */
	public static function compare_url( $before, $after ) {
		return add_query_arg(
			array(
				'page'   => 'corea11y-compare',
				'before' => absint( $before ),
				'after'  => absint( $after ),
			),
			admin_url( 'admin.php' )
		);
	}

	/**
	 * Nonce-protected export URL.
	 *
	 * @param string $type report|compare.
	 * @param array  $args Extra query args.
	 * @return string
	 */
	public static function export_url( $type, array $args ) {
		return wp_nonce_url( add_query_arg( array_merge( array( 'action' => 'corea11y_export', 'type' => $type ), array_map( 'absint', $args ) ), admin_url( 'admin-post.php' ) ), 'corea11y_export' );
	}

	/**
	 * Output the print-optimized document (admin-post handler).
	 */
	public function export() {
		if ( ! current_user_can( self::CAP ) ) {
			wp_die( esc_html__( 'You do not have permission to do this.', 'corea11y' ), 403 );
		}
		check_admin_referer( 'corea11y_export' );

		wp_register_style( 'corea11y-admin', COREA11Y_URL . 'assets/css/admin.css', array(), COREA11Y_VERSION );
		wp_register_style( 'corea11y-print', COREA11Y_URL . 'assets/css/print.css', array( 'corea11y-admin' ), COREA11Y_VERSION );

		$type = isset( $_GET['type'] ) && 'compare' === $_GET['type'] ? 'compare' : 'report';
		if ( 'compare' === $type ) {
			$before = isset( $_GET['before'] ) ? absint( $_GET['before'] ) : 0;
			$after  = isset( $_GET['after'] ) ? absint( $_GET['after'] ) : 0;
			$cmp    = CoreA11y_Compare::build( min( $before, $after ), max( $before, $after ) );
			if ( is_wp_error( $cmp ) ) {
				wp_die( esc_html( $cmp->get_error_message() ) );
			}
			$title = __( 'Accessibility progress report', 'corea11y' );
			$data  = array( 'cmp' => $cmp );
		} else {
			$audit = CoreA11y_Audit::get( isset( $_GET['audit'] ) ? absint( $_GET['audit'] ) : 0 );
			if ( ! $audit ) {
				wp_die( esc_html__( 'Audit not found.', 'corea11y' ) );
			}
			$title = __( 'Accessibility audit report', 'corea11y' );
			$data  = self::report_data( $audit );
		}

		nocache_headers();
		include COREA11Y_DIR . 'admin/views/print.php';
		exit;
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
