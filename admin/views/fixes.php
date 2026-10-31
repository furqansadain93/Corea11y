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
 * @var array       $plan      Pages to re-check (CoreA11y_Fixes::verify_plan()).
 * @var bool        $autostart Start the re-check straight away.
 * @var array       $verified  Verification results keyed by fix id.
 */

defined( 'ABSPATH' ) || exit;

$corea11y_on  = count( array_filter( $state ) );
$corea11y_fmt = get_option( 'date_format' ) . ' ' . get_option( 'time_format' );
?>
<div class="wrap corea11y">
	<?php CoreA11y_UI::brand_bar(); ?>
	<div class="corea11y-page-head">
		<div>
			<h1><?php esc_html_e( 'Quick fixes', 'msacalab-accessibility-checker' ); ?></h1>
			<p class="corea11y-meta"><?php esc_html_e( 'Safe, one-click fixes for common problems. Nothing in your theme or content is edited, and every change can be undone.', 'msacalab-accessibility-checker' ); ?></p>
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
			<p><strong><?php echo $corea11y_undone ? esc_html__( 'Undone.', 'msacalab-accessibility-checker' ) : ( $corea11y_redone ? esc_html__( 'Redone.', 'msacalab-accessibility-checker' ) : esc_html__( 'Fixes saved.', 'msacalab-accessibility-checker' ) ); ?></strong></p>
			<ul class="corea11y-change-list">
				<?php foreach ( $corea11y_entries as $corea11y_e ) : ?>
					<li>
						<?php
						$corea11y_t = isset( $fixes[ $corea11y_e['fix'] ] ) ? $fixes[ $corea11y_e['fix'] ]['title'] : sprintf( /* translators: %s: internal fix name. */ __( 'Pro fix (%s)', 'msacalab-accessibility-checker' ), $corea11y_e['fix'] );
						/* translators: %s: fix name. */
						echo esc_html( sprintf( $corea11y_e['on'] ? __( 'Turned on: %s', 'msacalab-accessibility-checker' ) : __( 'Turned off: %s', 'msacalab-accessibility-checker' ), $corea11y_t ) );
						?>
					</li>
				<?php endforeach; ?>
			</ul>
			<?php $corea11y_caches = CoreA11y_Compat::active_caches(); ?>
			<p class="corea11y-muted">
				<?php
				if ( $corea11y_caches ) {
					/* translators: %s: names of caching plugins, e.g. "WP Rocket". */
					echo esc_html( sprintf( __( 'We also cleared the page cache (%s), so visitors see this change now.', 'msacalab-accessibility-checker' ), implode( ', ', $corea11y_caches ) ) );
				} else {
					esc_html_e( 'If your host or a CDN such as Cloudflare caches your pages, clear that cache to see this change straight away.', 'msacalab-accessibility-checker' );
				}
				?>
			</p>
			<?php if ( $autostart && $plan['fixes'] ) : ?>
				<p class="corea11y-verify-line"><span class="spinner is-active" aria-hidden="true"></span><span id="corea11y-verify-status" role="status"><?php esc_html_e( 'Checking that the fixes worked…', 'msacalab-accessibility-checker' ); ?></span></p>
			<?php endif; ?>
			<div class="corea11y-actions">
				<?php if ( $autostart && $plan['fixes'] ) : ?>
					<button type="button" class="button button-primary" id="corea11y-verify-fixes" data-autostart><?php esc_html_e( 'Check again', 'msacalab-accessibility-checker' ); ?></button>
				<?php else : ?>
					<a class="button button-primary" href="<?php echo esc_url( admin_url( 'admin.php?page=corea11y&corea11y_autorun=1' ) ); ?>"><?php esc_html_e( 'Verify with a new audit', 'msacalab-accessibility-checker' ); ?></a>
				<?php endif; ?>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<?php CoreA11y_Admin::undo_fields( '', $corea11y_batch, $corea11y_undone ); ?>
					<button type="submit" class="button"><?php echo $corea11y_undone ? esc_html__( 'Redo', 'msacalab-accessibility-checker' ) : esc_html( _n( 'Undo this change', 'Undo these changes', count( $corea11y_entries ), 'msacalab-accessibility-checker' ) ); ?></button>
				</form>
			</div>
		</div>
	<?php elseif ( $corea11y_nochg ) : ?>
		<div class="notice notice-info" role="status"><p><?php esc_html_e( 'Nothing changed.', 'msacalab-accessibility-checker' ); ?></p></div>
	<?php endif; ?>

	<?php
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Display only.
	$corea11y_just_checked = isset( $_GET['verified'] );
	if ( $corea11y_just_checked && $verified ) :
		?>
		<div class="notice notice-success corea11y-verify" role="status">
			<p><strong><?php esc_html_e( 'Check complete.', 'msacalab-accessibility-checker' ); ?></strong> <?php esc_html_e( 'MSACAlab Accessibility Checker opened the pages where these problems were found and checked them again:', 'msacalab-accessibility-checker' ); ?></p>
			<ul class="corea11y-change-list">
				<?php foreach ( $verified as $corea11y_vid => $corea11y_v ) : ?>
					<?php
					if ( time() - (int) $corea11y_v['time'] > 300 || ! isset( $fixes[ $corea11y_vid ] ) ) {
						continue;
					}
					?>
					<li><?php echo esc_html( $fixes[ $corea11y_vid ]['title'] . ': ' . CoreA11y_UI::verify_text( $corea11y_v ) ); ?></li>
				<?php endforeach; ?>
			</ul>
			<p class="corea11y-muted"><?php esc_html_e( 'Problems the fixes don\'t cover are not part of this check. Run a full audit to update your score.', 'msacalab-accessibility-checker' ); ?></p>
		</div>
	<?php elseif ( ! $autostart && $plan['fixes'] ) : ?>
		<div class="corea11y-verify-box">
			<p>
				<?php
				/* translators: %d: number of quick fixes. */
				echo esc_html( sprintf( _n( '%d fix that is on hasn\'t been checked against your latest audit yet.', '%d fixes that are on haven\'t been checked against your latest audit yet.', count( $plan['fixes'] ), 'msacalab-accessibility-checker' ), count( $plan['fixes'] ) ) );
				?>
			</p>
			<p><button type="button" class="button" id="corea11y-verify-fixes"><?php esc_html_e( 'Check that they work', 'msacalab-accessibility-checker' ); ?></button> <span id="corea11y-verify-status" role="status"></span></p>
		</div>
	<?php endif; ?>

	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
		<input type="hidden" name="action" value="corea11y_save_fixes">
		<?php wp_nonce_field( 'corea11y_save_fixes' ); ?>

		<?php
		$corea11y_unneeded = array();
		foreach ( $fixes as $corea11y_uid => $corea11y_ufix ) {
			if ( $latest && empty( $state[ $corea11y_uid ] ) && ! empty( $corea11y_ufix['rules'] ) && empty( $found[ $corea11y_uid ] ) ) {
				$corea11y_unneeded[ $corea11y_uid ] = $corea11y_ufix;
			}
		}
		?>
		<ul class="corea11y-fixes" id="corea11y-fix-list">
			<?php foreach ( array_diff_key( $fixes, $corea11y_unneeded ) as $corea11y_id => $corea11y_fix ) : ?>
				<?php require COREA11Y_DIR . 'admin/views/partials/fix-card.php'; ?>
			<?php endforeach; ?>
		</ul>

		<?php if ( $corea11y_unneeded ) : ?>
			<details class="corea11y-unneeded">
				<summary>
					<?php
					/* translators: %s: number of quick fixes. */
					echo esc_html( sprintf( _n( 'Not needed on your site (%s fix)', 'Not needed on your site (%s fixes)', count( $corea11y_unneeded ), 'msacalab-accessibility-checker' ), number_format_i18n( count( $corea11y_unneeded ) ) ) );
					?>
				</summary>
				<p class="corea11y-muted"><?php esc_html_e( 'Your latest audit found no problems these fixes solve, often because your theme already handles them. You don\'t need to turn them on, but you can.', 'msacalab-accessibility-checker' ); ?></p>
				<ul class="corea11y-fixes">
					<?php foreach ( $corea11y_unneeded as $corea11y_id => $corea11y_fix ) : ?>
						<?php require COREA11Y_DIR . 'admin/views/partials/fix-card.php'; ?>
					<?php endforeach; ?>
				</ul>
			</details>
		<?php endif; ?>


		<p class="corea11y-actions">
			<?php submit_button( __( 'Save fixes', 'msacalab-accessibility-checker' ), 'primary', 'submit', false ); ?>
			<?php if ( $corea11y_on ) : ?>
				<button type="submit" class="button" name="corea11y_all_off" value="1"><?php esc_html_e( 'Turn all fixes off', 'msacalab-accessibility-checker' ); ?></button>
			<?php endif; ?>
		</p>
	</form>

	<?php
	if ( ! CoreA11y_Pro_Catalog::pro_installed() ) {
		require COREA11Y_DIR . 'admin/views/partials/pro-catalog.php';
	}
	?>

	<section class="corea11y-section">
		<h2><?php esc_html_e( 'Change history', 'msacalab-accessibility-checker' ); ?></h2>
		<?php if ( ! $log ) : ?>
			<p><?php esc_html_e( 'No changes yet.', 'msacalab-accessibility-checker' ); ?></p>
		<?php else : ?>
			<table class="widefat striped corea11y-table">
				<caption class="screen-reader-text"><?php esc_html_e( 'Quick fix changes, newest first', 'msacalab-accessibility-checker' ); ?></caption>
				<thead><tr><th scope="col"><?php esc_html_e( 'When', 'msacalab-accessibility-checker' ); ?></th><th scope="col"><?php esc_html_e( 'Fix', 'msacalab-accessibility-checker' ); ?></th><th scope="col"><?php esc_html_e( 'Change', 'msacalab-accessibility-checker' ); ?></th><th scope="col"><?php esc_html_e( 'By', 'msacalab-accessibility-checker' ); ?></th><th scope="col"><?php esc_html_e( 'Undo', 'msacalab-accessibility-checker' ); ?></th></tr></thead>
				<tbody>
					<?php foreach ( array_slice( $log, 0, 20 ) as $corea11y_entry ) : ?>
						<?php
						$corea11y_user = get_userdata( (int) $corea11y_entry['user'] );
						$corea11y_name = isset( $fixes[ $corea11y_entry['fix'] ] ) ? $fixes[ $corea11y_entry['fix'] ]['title'] : sprintf( /* translators: %s: internal fix name. */ __( 'Pro fix (%s)', 'msacalab-accessibility-checker' ), $corea11y_entry['fix'] );
						$corea11y_kind = isset( $corea11y_entry['kind'] ) && 'undo' === $corea11y_entry['kind'];
						?>
						<tr>
							<td><?php echo esc_html( wp_date( $corea11y_fmt, (int) $corea11y_entry['time'] ) ); ?></td>
							<td><?php echo esc_html( $corea11y_name ); ?></td>
							<td>
								<?php
								if ( $corea11y_kind ) {
									echo $corea11y_entry['on'] ? esc_html__( 'Turned back on (undo)', 'msacalab-accessibility-checker' ) : esc_html__( 'Turned off (undo)', 'msacalab-accessibility-checker' );
								} else {
									echo $corea11y_entry['on'] ? esc_html__( 'Turned on', 'msacalab-accessibility-checker' ) : esc_html__( 'Turned off', 'msacalab-accessibility-checker' );
								}
								?>
							</td>
							<td><?php echo esc_html( $corea11y_user ? $corea11y_user->display_name : '—' ); ?></td>
							<td>
								<?php if ( CoreA11y_Fixes::can_undo( $corea11y_entry ) ) : ?>
									<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
										<?php CoreA11y_Admin::undo_fields( $corea11y_entry['id'] ); ?>
										<button type="submit" class="button button-small">
											<?php esc_html_e( 'Undo', 'msacalab-accessibility-checker' ); ?><span class="screen-reader-text">
											<?php
											/* translators: 1: change, 2: fix name. */
											echo esc_html( sprintf( __( '%1$s: %2$s', 'msacalab-accessibility-checker' ), $corea11y_entry['on'] ? __( 'turned on', 'msacalab-accessibility-checker' ) : __( 'turned off', 'msacalab-accessibility-checker' ), $corea11y_name ) );
											?>
											</span>
										</button>
									</form>
								<?php else : ?>
									<span class="corea11y-muted"><?php esc_html_e( 'Changed since', 'msacalab-accessibility-checker' ); ?></span>
								<?php endif; ?>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		<?php endif; ?>
	</section>

	<p class="corea11y-note"><?php esc_html_e( 'Some fixes use a small script on your pages because many themes hard-code these parts of the page. Visitors with JavaScript turned off simply see your site as before.', 'msacalab-accessibility-checker' ); ?></p>
</div>
