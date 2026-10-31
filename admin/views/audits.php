<?php
/**
 * Audits list view.
 *
 * @package CoreA11y
 * @var array $audits Audit rows, newest first.
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="wrap corea11y">
	<?php CoreA11y_UI::brand_bar(); ?>
	<div class="corea11y-page-head"><div><h1><?php esc_html_e( 'Audits', 'msacalab-accessibility-checker' ); ?></h1><p class="corea11y-meta"><?php esc_html_e( 'Every audit you have run. Open one to see the full report.', 'msacalab-accessibility-checker' ); ?></p></div></div>

	<?php if ( empty( $audits ) ) : ?>
		<p><?php esc_html_e( 'No audits have been run yet.', 'msacalab-accessibility-checker' ); ?></p>
	<?php else : ?>
		<table class="widefat striped">
			<caption class="screen-reader-text"><?php esc_html_e( 'Audit history, newest first', 'msacalab-accessibility-checker' ); ?></caption>
			<thead>
				<tr>
					<th scope="col"><?php esc_html_e( 'Date', 'msacalab-accessibility-checker' ); ?></th>
					<th scope="col"><?php esc_html_e( 'Status', 'msacalab-accessibility-checker' ); ?></th>
					<th scope="col"><?php esc_html_e( 'Pages scanned', 'msacalab-accessibility-checker' ); ?></th>
					<th scope="col"><?php esc_html_e( 'Issues', 'msacalab-accessibility-checker' ); ?></th>
					<th scope="col"><?php esc_html_e( 'Score', 'msacalab-accessibility-checker' ); ?></th>
					<th scope="col"><?php esc_html_e( 'Export', 'msacalab-accessibility-checker' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $audits as $corea11y_audit ) : ?>
					<tr>
						<td>
							<a href="<?php echo esc_url( CoreA11y_Admin::report_url( $corea11y_audit->id ) ); ?>">
								<?php echo esc_html( $corea11y_audit->started_at ? get_date_from_gmt( $corea11y_audit->started_at, get_option( 'date_format' ) . ' ' . get_option( 'time_format' ) ) : '—' ); ?>
							</a>
						</td>
						<td><?php echo esc_html( ucfirst( $corea11y_audit->status ) ); ?></td>
						<td><?php echo esc_html( number_format_i18n( (int) $corea11y_audit->pages_scanned ) . ' / ' . number_format_i18n( (int) $corea11y_audit->pages_total ) ); ?></td>
						<td><?php echo esc_html( number_format_i18n( (int) $corea11y_audit->issues_total ) ); ?></td>
						<td><?php echo null === $corea11y_audit->score ? '—' : esc_html( (int) $corea11y_audit->score ); ?></td>
						<td>
							<?php if ( 'complete' === $corea11y_audit->status ) : ?>
								<a href="<?php echo esc_url( CoreA11y_Admin::export_url( 'report', array( 'audit' => $corea11y_audit->id ) ) ); ?>" target="_blank" rel="noopener">
									<?php esc_html_e( 'PDF', 'msacalab-accessibility-checker' ); ?><span class="screen-reader-text"> <?php echo esc_html( get_date_from_gmt( $corea11y_audit->started_at, get_option( 'date_format' ) ) ); ?> <?php esc_html_e( '(opens in a new tab)', 'msacalab-accessibility-checker' ); ?></span>
								</a>
							<?php endif; ?>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	<?php endif; ?>
</div>
