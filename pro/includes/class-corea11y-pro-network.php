<?php
/**
 * CoreA11y Pro on WordPress Multisite (Business and Agency).
 *
 * Adds a Network Admin overview of every site's latest audit and quick fixes, and lets
 * a network admin copy one site's quick fixes to every site (and to sites created
 * later). Each copy is logged on each site, so it can be undone there or from here.
 *
 * @package CoreA11y
 */

defined( 'ABSPATH' ) || exit;

/**
 * Network overview and fix copying.
 */
class CoreA11y_Pro_Network {

	const SLUG     = 'corea11y-network';
	const DEFAULTS = 'corea11y_network';
	const MAX      = 200;

	/**
	 * Hook into WordPress.
	 */
	public static function register() {
		if ( ! is_multisite() ) {
			return;
		}
		add_action( 'network_admin_menu', array( __CLASS__, 'menu' ) );
		add_action( 'network_admin_edit_corea11y_copy_fixes', array( __CLASS__, 'copy_fixes' ) );
		add_action( 'network_admin_edit_corea11y_undo_copy', array( __CLASS__, 'undo_copy' ) );
		add_action( 'wp_initialize_site', array( __CLASS__, 'new_site' ), 200 );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'assets' ) );
	}

	/**
	 * Whether the network's license includes multisite tools (Business or Agency).
	 *
	 * @return bool
	 */
	public static function allowed() {
		switch_to_blog( get_main_site_id() );
		$ok = CoreA11y_Pro_License::has( CoreA11y_Pro_License::BUSINESS );
		restore_current_blog();
		return $ok;
	}

	/**
	 * Network Admin menu.
	 */
	public static function menu() {
		add_menu_page(
			__( 'CoreA11y network overview', 'corea11y' ),
			'CoreA11y',
			'manage_network_options',
			self::SLUG,
			array( __CLASS__, 'render' ),
			'dashicons-universal-access-alt',
			81
		);
	}

	/**
	 * Lite's admin styles on the network screen.
	 *
	 * @param string $hook Hook suffix.
	 */
	public static function assets( $hook ) {
		if ( false !== strpos( (string) $hook, self::SLUG ) ) {
			wp_enqueue_style( 'corea11y-admin', COREA11Y_URL . 'assets/css/admin.css', array(), COREA11Y_VERSION );
		}
	}

	/**
	 * Whether CoreA11y runs on a site (network-active, or active on that site).
	 *
	 * @return bool
	 */
	private static function active_here() {
		if ( ! function_exists( 'is_plugin_active' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}
		return is_plugin_active( plugin_basename( COREA11Y_FILE ) );
	}

	/**
	 * Overview row for the current (switched-to) site.
	 *
	 * @return array
	 */
	private static function site_row() {
		global $wpdb;
		$row = array(
			'name'     => get_bloginfo( 'name' ),
			'url'      => home_url( '/' ),
			'active'   => self::active_here(),
			'audit'    => null,
			'fixes'    => 0,
			'verified' => 0,
			'admin'    => admin_url( 'admin.php?page=corea11y' ),
		);
		if ( ! $row['active'] ) {
			return $row;
		}
		$table = $wpdb->prefix . 'corea11y_audits';
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Checking the plugin's own table.
		if ( $table === $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $table ) ) ) ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom table.
			$row['audit'] = $wpdb->get_row( $wpdb->prepare( "SELECT id, score, issues_total, started_at FROM %i WHERE status = 'complete' ORDER BY id DESC LIMIT 1", $table ) );
		}
		$saved           = get_option( CoreA11y_Fixes::OPTION, array() );
		$row['fixes']    = is_array( $saved ) ? count( array_filter( $saved ) ) : 0;
		$verified        = get_option( CoreA11y_Fixes::VERIFY, array() );
		$row['verified'] = is_array( $verified ) ? count(
			array_filter(
				$verified,
				static function ( $v ) {
					return isset( $v['status'] ) && 'verified' === $v['status'];
				}
			)
		) : 0;
		return $row;
	}

	/**
	 * Network overview screen.
	 */
	public static function render() {
		if ( ! current_user_can( 'manage_network_options' ) ) {
			return;
		}
		$allowed = self::allowed();
		$sites   = get_sites(
			array(
				'number'   => self::MAX,
				'fields'   => 'ids',
				'archived' => 0,
				'deleted'  => 0,
			)
		);
		$rows    = array();
		foreach ( $sites as $id ) {
			switch_to_blog( $id );
			$rows[ $id ] = self::site_row();
			restore_current_blog();
		}
		$fixes    = CoreA11y_Fixes::all();
		$defaults = get_site_option( self::DEFAULTS, array() );
		$last     = isset( $defaults['last_copy'] ) && is_array( $defaults['last_copy'] ) ? $defaults['last_copy'] : array();
		$fmt      = get_option( 'date_format' );
		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- Display-only flags after redirect.
		$copied = isset( $_GET['copied'] ) ? absint( $_GET['copied'] ) : null;
		$undone = isset( $_GET['undone'] );
		// phpcs:enable
		?>
		<div class="wrap corea11y">
			<?php CoreA11y_UI::brand_bar(); ?>
			<h1><?php esc_html_e( 'CoreA11y network overview', 'corea11y' ); ?></h1>
			<p class="corea11y-meta"><?php esc_html_e( 'The latest audit and quick fixes of every site in your network.', 'corea11y' ); ?></p>

			<?php if ( null !== $copied ) : ?>
				<div class="notice notice-success" role="status"><p>
					<?php
					/* translators: %d: number of sites. */
					echo esc_html( sprintf( _n( 'Quick fixes copied to %d site. Each site logged the change, so it can be undone there or below.', 'Quick fixes copied to %d sites. Each site logged the change, so it can be undone there or below.', $copied, 'corea11y' ), $copied ) );
					?>
				</p></div>
			<?php elseif ( $undone ) : ?>
				<div class="notice notice-info" role="status"><p><?php esc_html_e( 'The last copy was undone on every site.', 'corea11y' ); ?></p></div>
			<?php endif; ?>

			<?php if ( ! $allowed ) : ?>
				<div class="corea11y-upsell">
					<h2><?php esc_html_e( 'Network tools are part of the Business and Agency plans', 'corea11y' ); ?></h2>
					<p><?php esc_html_e( 'See every site\'s audit at a glance and copy quick fixes to all your sites at once. CoreA11y itself still works on each site, from that site\'s dashboard.', 'corea11y' ); ?></p>
				</div>
			<?php endif; ?>

			<table class="widefat striped corea11y-table">
				<caption class="screen-reader-text"><?php esc_html_e( 'Sites in this network', 'corea11y' ); ?></caption>
				<thead><tr>
					<th scope="col"><?php esc_html_e( 'Site', 'corea11y' ); ?></th>
					<th scope="col"><?php esc_html_e( 'Latest audit', 'corea11y' ); ?></th>
					<th scope="col"><?php esc_html_e( 'CoreA11y score', 'corea11y' ); ?></th>
					<th scope="col"><?php esc_html_e( 'Issues', 'corea11y' ); ?></th>
					<th scope="col"><?php esc_html_e( 'Quick fixes on', 'corea11y' ); ?></th>
					<th scope="col"><?php esc_html_e( 'Open', 'corea11y' ); ?></th>
				</tr></thead>
				<tbody>
				<?php foreach ( $rows as $r ) : ?>
					<tr>
						<th scope="row"><strong><?php echo esc_html( $r['name'] ); ?></strong><br><span class="corea11y-muted"><?php echo esc_html( $r['url'] ); ?></span></th>
						<?php if ( ! $r['active'] ) : ?>
							<td colspan="4"><?php esc_html_e( 'CoreA11y is not active on this site.', 'corea11y' ); ?></td>
							<td>—</td>
						<?php else : ?>
							<td><?php echo $r['audit'] ? esc_html( get_date_from_gmt( $r['audit']->started_at, $fmt ) ) : esc_html__( 'No audit yet', 'corea11y' ); ?></td>
							<td><?php echo ( $r['audit'] && null !== $r['audit']->score ) ? esc_html( (int) $r['audit']->score . '/100' ) : '—'; ?></td>
							<td><?php echo $r['audit'] ? esc_html( number_format_i18n( (int) $r['audit']->issues_total ) ) : '—'; ?></td>
							<td>
								<?php
								echo esc_html( number_format_i18n( $r['fixes'] ) );
								if ( $r['verified'] ) {
									/* translators: %d: number of verified fixes. */
									echo ' <span class="corea11y-muted">(' . esc_html( sprintf( __( '%d verified', 'corea11y' ), $r['verified'] ) ) . ')</span>';
								}
								?>
							</td>
							<td><a href="<?php echo esc_url( $r['admin'] ); ?>"><?php esc_html_e( 'CoreA11y dashboard', 'corea11y' ); ?><span class="screen-reader-text"> <?php echo esc_html( $r['name'] ); ?></span></a></td>
						<?php endif; ?>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
			<?php if ( count( $sites ) >= self::MAX ) : ?>
				<p class="corea11y-muted">
					<?php
					/* translators: %d: number of sites shown. */
					echo esc_html( sprintf( __( 'Showing the first %d sites.', 'corea11y' ), self::MAX ) );
					?>
				</p>
			<?php endif; ?>

			<?php if ( $allowed ) : ?>
				<section class="corea11y-section">
					<h2><?php esc_html_e( 'Copy quick fixes to every site', 'corea11y' ); ?></h2>
					<p><?php esc_html_e( 'Turns the same quick fixes on and off on every site where CoreA11y is active, using one site as the example. Every site logs the change, so it can be undone.', 'corea11y' ); ?></p>
					<form method="post" action="<?php echo esc_url( network_admin_url( 'edit.php?action=corea11y_copy_fixes' ) ); ?>">
						<?php wp_nonce_field( 'corea11y_copy_fixes' ); ?>
						<p>
							<label for="corea11y-source"><?php esc_html_e( 'Copy the quick fixes of', 'corea11y' ); ?></label>
							<select id="corea11y-source" name="source">
								<?php foreach ( $rows as $id => $r ) : ?>
									<?php if ( $r['active'] ) : ?>
										<option value="<?php echo esc_attr( $id ); ?>">
											<?php
											/* translators: 1: site name, 2: number of fixes on. */
											echo esc_html( sprintf( __( '%1$s (%2$d on)', 'corea11y' ), $r['name'], $r['fixes'] ) );
											?>
										</option>
									<?php endif; ?>
								<?php endforeach; ?>
							</select>
						</p>
						<p><label><input type="checkbox" name="new_sites" value="1" <?php checked( ! empty( $defaults['fixes'] ) ); ?>> <?php esc_html_e( 'Also turn them on for sites created later', 'corea11y' ); ?></label></p>
						<p><button type="submit" class="button button-primary"><?php esc_html_e( 'Copy to all sites', 'corea11y' ); ?></button></p>
					</form>
					<?php if ( $last ) : ?>
						<form method="post" action="<?php echo esc_url( network_admin_url( 'edit.php?action=corea11y_undo_copy' ) ); ?>">
							<?php wp_nonce_field( 'corea11y_undo_copy' ); ?>
							<p><button type="submit" class="button"><?php esc_html_e( 'Undo the last copy on every site', 'corea11y' ); ?></button></p>
						</form>
					<?php endif; ?>
				</section>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Copy one site's quick fixes to every site (network admin edit handler).
	 */
	public static function copy_fixes() {
		if ( ! current_user_can( 'manage_network_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to do this.', 'corea11y' ), 403 );
		}
		check_admin_referer( 'corea11y_copy_fixes' );
		if ( ! self::allowed() ) {
			wp_die( esc_html__( 'Network tools are part of the Business and Agency plans.', 'corea11y' ), 403 );
		}
		$source = isset( $_POST['source'] ) ? absint( $_POST['source'] ) : 0;
		if ( ! $source || ! get_site( $source ) ) {
			wp_die( esc_html__( 'Choose a site to copy from.', 'corea11y' ) );
		}
		switch_to_blog( $source );
		$state = CoreA11y_Fixes::state();
		restore_current_blog();

		$batches = array();
		foreach ( get_sites(
			array(
				'number'   => self::MAX,
				'fields'   => 'ids',
				'archived' => 0,
				'deleted'  => 0,
			)
		) as $id ) {
			if ( $id === $source ) {
				continue;
			}
			switch_to_blog( $id );
			if ( self::active_here() ) {
				$batch = CoreA11y_Fixes::save( $state );
				if ( $batch ) {
					$batches[ $id ] = $batch;
				}
			}
			restore_current_blog();
		}

		$defaults              = get_site_option( self::DEFAULTS, array() );
		$defaults              = is_array( $defaults ) ? $defaults : array();
		$defaults['fixes']     = ! empty( $_POST['new_sites'] ) ? $state : array();
		$defaults['last_copy'] = $batches;
		update_site_option( self::DEFAULTS, $defaults );

		wp_safe_redirect( add_query_arg( 'copied', count( $batches ), network_admin_url( 'admin.php?page=' . self::SLUG ) ) );
		exit;
	}

	/**
	 * Undo the last copy on every site (network admin edit handler).
	 */
	public static function undo_copy() {
		if ( ! current_user_can( 'manage_network_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to do this.', 'corea11y' ), 403 );
		}
		check_admin_referer( 'corea11y_undo_copy' );
		$defaults = get_site_option( self::DEFAULTS, array() );
		$last     = is_array( $defaults ) && isset( $defaults['last_copy'] ) && is_array( $defaults['last_copy'] ) ? $defaults['last_copy'] : array();
		foreach ( $last as $id => $batch ) {
			if ( get_site( (int) $id ) ) {
				switch_to_blog( (int) $id );
				CoreA11y_Fixes::undo( '', (string) $batch );
				restore_current_blog();
			}
		}
		$defaults['last_copy'] = array();
		update_site_option( self::DEFAULTS, $defaults );
		wp_safe_redirect( add_query_arg( 'undone', 1, network_admin_url( 'admin.php?page=' . self::SLUG ) ) );
		exit;
	}

	/**
	 * Turn the network's default quick fixes on for a newly created site.
	 *
	 * @param WP_Site $site New site.
	 */
	public static function new_site( $site ) {
		$defaults = get_site_option( self::DEFAULTS, array() );
		if ( empty( $defaults['fixes'] ) || ! is_array( $defaults['fixes'] ) || ! self::allowed() ) {
			return;
		}
		switch_to_blog( (int) $site->blog_id );
		if ( self::active_here() ) {
			CoreA11y_Fixes::save( $defaults['fixes'] );
		}
		restore_current_blog();
	}
}
