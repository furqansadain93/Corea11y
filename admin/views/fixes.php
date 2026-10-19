<?php
/**
 * Quick fixes screen.
 *
 * @package CoreA11y
 * @var array       $fixes  Fix definitions.
 * @var array       $state  Fix on/off state.
 * @var array       $found  Issue counts per fix from the latest audit.
 * @var object|null $latest Latest completed audit.
 * @var array       $log    Change history.
 */

defined( 'ABSPATH' ) || exit;

$corea11y_on  = count( array_filter( $state ) );
$corea11y_fmt = get_option( 'date_format' ) . ' ' . get_option( 'time_format' );
?>
<div class="wrap corea11y">
	<div class="corea11y-page-head">
		<div>
			<h1><?php esc_html_e( 'Quick fixes', 'corea11y' ); ?></h1>
			<p class="corea11y-meta"><?php esc_html_e( 'Safe, one-click fixes for common problems. Nothing in your theme or content is edited, and every change can be undone.', 'corea11y' ); ?></p>
		</div>
	</div>

	<?php
	// phpcs:disable WordPress.Security.NonceVerification.Recommended -- Display-only flags after redirect.
	$corea11y_batch   = isset( $_GET['batch'] ) ? sanitize_key( wp_unslash( $_GET['batch'] ) ) : '';
	$corea11y_saved   = isset( $_GET['saved'] );
	$corea11y_undone  = isset( $_GET['undone'] );
	$corea11y_redone  = isset( $_GET['redone'] );
	$corea11y_nochg   = isset( $_GET['nochange'] );
	// phpcs:enable
	$corea11y_entries = $corea11y_batch ? CoreA11y_Fixes::batch_entries( $corea11y_batch ) : array();
	?>

	<?php if ( $corea11y_entries && ( $corea11y_saved || $corea11y_undone || $corea11y_redone ) ) : ?>
		<div class="notice <?php echo $corea11y_undone ? 'notice-info' : 'notice-success'; ?> corea11y-verify" role="status">
			<p><strong><?php echo $corea11y_undone ? esc_html__( 'Undone.', 'corea11y' ) : ( $corea11y_redone ? esc_html__( 'Redone.', 'corea11y' ) : esc_html__( 'Fixes saved.', 'corea11y' ) ); ?></strong></p>
			<ul class="corea11y-change-list">
				<?php foreach ( $corea11y_entries as $corea11y_e ) : ?>
					<li>
						<?php
						$corea11y_t = isset( $fixes[ $corea11y_e['fix'] ] ) ? $fixes[ $corea11y_e['fix'] ]['title'] : $corea11y_e['fix'];
						/* translators: %s: fix name. */
						echo esc_html( sprintf( $corea11y_e['on'] ? __( 'Turned on: %s', 'corea11y' ) : __( 'Turned off: %s', 'corea11y' ), $corea11y_t ) );
						?>
					</li>
				<?php endforeach; ?>
			</ul>
			<div class="corea11y-actions">
				<a class="button button-primary" href="<?php echo esc_url( admin_url( 'admin.php?page=corea11y&corea11y_autorun=1' ) ); ?>"><?php esc_html_e( 'Verify with a new audit', 'corea11y' ); ?></a>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<?php CoreA11y_Admin::undo_fields( '', $corea11y_batch, $corea11y_undone ); ?>
					<button type="submit" class="button"><?php echo $corea11y_undone ? esc_html__( 'Redo', 'corea11y' ) : esc_html( _n( 'Undo this change', 'Undo these changes', count( $corea11y_entries ), 'corea11y' ) ); ?></button>
				</form>
			</div>
		</div>
	<?php elseif ( $corea11y_nochg ) : ?>
		<div class="notice notice-info" role="status"><p><?php esc_html_e( 'Nothing changed.', 'corea11y' ); ?></p></div>
	<?php endif; ?>

	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
		<input type="hidden" name="action" value="corea11y_save_fixes">
		<?php wp_nonce_field( 'corea11y_save_fixes' ); ?>

		<ul class="corea11y-fixes">
			<?php foreach ( $fixes as $corea11y_id => $corea11y_fix ) : ?>
				<?php
				$corea11y_field = 'corea11y-fix-' . $corea11y_id;
				$corea11y_count = isset( $found[ $corea11y_id ] ) ? (int) $found[ $corea11y_id ] : 0;
				?>
				<li class="corea11y-fix<?php echo $state[ $corea11y_id ] ? ' is-on' : ''; ?>">
					<div class="corea11y-fix-text">
						<h2 class="corea11y-fix-title"><label for="<?php echo esc_attr( $corea11y_field ); ?>"><?php echo esc_html( $corea11y_fix['title'] ); ?></label></h2>
						<p id="<?php echo esc_attr( $corea11y_field . '-desc' ); ?>"><?php echo esc_html( $corea11y_fix['desc'] ); ?></p>
						<p class="corea11y-badges">
							<span class="corea11y-badge">
								<?php
								/* translators: %s: WCAG success criterion numbers. */
								echo esc_html( sprintf( __( 'WCAG %s', 'corea11y' ), $corea11y_fix['wcag'] ) );
								?>
							</span>
							<?php if ( $latest && $corea11y_count ) : ?>
								<span class="corea11y-badge corea11y-badge-serious">
									<?php
									/* translators: %s: number of issues. */
									echo esc_html( sprintf( _n( 'Fixes %s issue in your latest audit', 'Fixes %s issues in your latest audit', $corea11y_count, 'corea11y' ), number_format_i18n( $corea11y_count ) ) );
									?>
								</span>
							<?php elseif ( $latest && $corea11y_fix['rules'] ) : ?>
								<span class="corea11y-badge corea11y-badge-good"><?php esc_html_e( 'Not needed in your latest audit', 'corea11y' ); ?></span>
							<?php elseif ( ! $corea11y_fix['rules'] ) : ?>
								<span class="corea11y-badge"><?php esc_html_e( 'Recommended for every site', 'corea11y' ); ?></span>
							<?php endif; ?>
						</p>
					</div>
					<div class="corea11y-fix-toggle">
						<?php if ( $state[ $corea11y_id ] ) : ?>
							<button type="submit" class="button corea11y-undo-btn" name="corea11y_undo_fix" value="<?php echo esc_attr( $corea11y_id ); ?>">
								<?php esc_html_e( 'Undo', 'corea11y' ); ?><span class="screen-reader-text"> <?php echo esc_html( $corea11y_fix['title'] ); ?></span>
							</button>
						<?php endif; ?>
						<input type="checkbox" role="switch" class="corea11y-switch" id="<?php echo esc_attr( $corea11y_field ); ?>" name="corea11y_fix[<?php echo esc_attr( $corea11y_id ); ?>]" value="1" aria-describedby="<?php echo esc_attr( $corea11y_field . '-desc' ); ?>" <?php checked( $state[ $corea11y_id ] ); ?>>
					</div>
				</li>
			<?php endforeach; ?>
		</ul>

		<p class="corea11y-actions">
			<?php submit_button( __( 'Save fixes', 'corea11y' ), 'primary', 'submit', false ); ?>
			<?php if ( $corea11y_on ) : ?>
				<button type="submit" class="button" name="corea11y_all_off" value="1"><?php esc_html_e( 'Turn all fixes off', 'corea11y' ); ?></button>
			<?php endif; ?>
		</p>
	</form>

	<section class="corea11y-section">
		<h2><?php esc_html_e( 'Change history', 'corea11y' ); ?></h2>
		<?php if ( ! $log ) : ?>
			<p><?php esc_html_e( 'No changes yet.', 'corea11y' ); ?></p>
		<?php else : ?>
			<table class="widefat striped corea11y-table">
				<caption class="screen-reader-text"><?php esc_html_e( 'Quick fix changes, newest first', 'corea11y' ); ?></caption>
				<thead><tr><th scope="col"><?php esc_html_e( 'When', 'corea11y' ); ?></th><th scope="col"><?php esc_html_e( 'Fix', 'corea11y' ); ?></th><th scope="col"><?php esc_html_e( 'Change', 'corea11y' ); ?></th><th scope="col"><?php esc_html_e( 'By', 'corea11y' ); ?></th><th scope="col"><?php esc_html_e( 'Undo', 'corea11y' ); ?></th></tr></thead>
				<tbody>
					<?php foreach ( array_slice( $log, 0, 20 ) as $corea11y_entry ) : ?>
						<?php
						$corea11y_user = get_userdata( (int) $corea11y_entry['user'] );
						$corea11y_name = isset( $fixes[ $corea11y_entry['fix'] ] ) ? $fixes[ $corea11y_entry['fix'] ]['title'] : $corea11y_entry['fix'];
						$corea11y_kind = isset( $corea11y_entry['kind'] ) && 'undo' === $corea11y_entry['kind'];
						?>
						<tr>
							<td><?php echo esc_html( wp_date( $corea11y_fmt, (int) $corea11y_entry['time'] ) ); ?></td>
							<td><?php echo esc_html( $corea11y_name ); ?></td>
							<td>
								<?php
								if ( $corea11y_kind ) {
									echo $corea11y_entry['on'] ? esc_html__( 'Turned back on (undo)', 'corea11y' ) : esc_html__( 'Turned off (undo)', 'corea11y' );
								} else {
									echo $corea11y_entry['on'] ? esc_html__( 'Turned on', 'corea11y' ) : esc_html__( 'Turned off', 'corea11y' );
								}
								?>
							</td>
							<td><?php echo esc_html( $corea11y_user ? $corea11y_user->display_name : '—' ); ?></td>
							<td>
								<?php if ( CoreA11y_Fixes::can_undo( $corea11y_entry ) ) : ?>
									<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
										<?php CoreA11y_Admin::undo_fields( $corea11y_entry['id'] ); ?>
										<button type="submit" class="button button-small">
											<?php esc_html_e( 'Undo', 'corea11y' ); ?><span class="screen-reader-text">
											<?php
											/* translators: 1: change, 2: fix name. */
											echo esc_html( sprintf( __( '%1$s: %2$s', 'corea11y' ), $corea11y_entry['on'] ? __( 'turned on', 'corea11y' ) : __( 'turned off', 'corea11y' ), $corea11y_name ) );
											?>
											</span>
										</button>
									</form>
								<?php else : ?>
									<span class="corea11y-muted"><?php esc_html_e( 'Changed since', 'corea11y' ); ?></span>
								<?php endif; ?>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		<?php endif; ?>
	</section>

	<p class="corea11y-note"><?php esc_html_e( 'Some fixes use a small script on your pages because many themes hard-code these parts of the page. Visitors with JavaScript turned off simply see your site as before.', 'corea11y' ); ?></p>
</div>
