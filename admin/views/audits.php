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
	<h1><?php esc_html_e( 'Audits', 'corea11y' ); ?></h1>

	<?php if ( empty( $audits ) ) : ?>
		<p><?php esc_html_e( 'No audits have been run yet.', 'corea11y' ); ?></p>
	<?php else : ?>
		<table class="widefat striped">
			<caption class="screen-reader-text"><?php esc_html_e( 'Audit history, newest first', 'corea11y' ); ?></caption>
			<thead>
				<tr>
					<th scope="col"><?php esc_html_e( 'Date', 'corea11y' ); ?></th>
					<th scope="col"><?php esc_html_e( 'Status', 'corea11y' ); ?></th>
					<th scope="col"><?php esc_html_e( 'Pages scanned', 'corea11y' ); ?></th>
					<th scope="col"><?php esc_html_e( 'Issues', 'corea11y' ); ?></th>
					<th scope="col"><?php esc_html_e( 'Score', 'corea11y' ); ?></th>
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
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	<?php endif; ?>
</div>
