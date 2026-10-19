<?php
/**
 * Before/after comparison body, shared by the admin screen and the PDF export.
 *
 * @package CoreA11y
 * @var array $cmp      Comparison from CoreA11y_Compare::build().
 * @var bool  $corea11y_is_print True when rendering for PDF.
 */

defined( 'ABSPATH' ) || exit;

$corea11y_before = $cmp['before'];
$corea11y_after  = $cmp['after'];
$corea11y_sum    = $cmp['summary'];
$corea11y_tb     = (int) $cmp['total']['before'];
$corea11y_ta     = (int) $cmp['total']['after'];
$corea11y_delta  = $corea11y_ta - $corea11y_tb;
$corea11y_fmt    = get_option( 'date_format' );
$corea11y_d1     = get_date_from_gmt( $corea11y_before->started_at, $corea11y_fmt );
$corea11y_d2     = get_date_from_gmt( $corea11y_after->started_at, $corea11y_fmt );
$corea11y_pct    = $corea11y_tb > 0 ? round( 100 * $corea11y_sum['resolved'] / $corea11y_tb ) : 0;

if ( $corea11y_tb > 0 && 0 === $corea11y_ta ) {
	$corea11y_headline = __( 'Every issue from the earlier audit is gone. Outstanding!', 'corea11y' );
} elseif ( $corea11y_delta < 0 ) {
	/* translators: %s: number of issues. */
	$corea11y_headline = sprintf( _n( '%s fewer issue than before. Nice work!', '%s fewer issues than before. Nice work!', -$corea11y_delta, 'corea11y' ), number_format_i18n( -$corea11y_delta ) );
} elseif ( 0 === $corea11y_delta && 0 === $corea11y_sum['new'] && 0 === $corea11y_sum['resolved'] ) {
	$corea11y_headline = __( 'No change between these audits yet.', 'corea11y' );
} elseif ( $corea11y_delta > 0 ) {
	/* translators: %s: number of issues. */
	$corea11y_headline = sprintf( _n( '%s more issue than before.', '%s more issues than before.', $corea11y_delta, 'corea11y' ), number_format_i18n( $corea11y_delta ) );
} else {
	$corea11y_headline = __( 'Some issues were fixed and some new ones appeared.', 'corea11y' );
}
?>
<section class="corea11y-hero corea11y-hero-compare" aria-labelledby="corea11y-cmp-title">
	<div class="corea11y-rings">
		<?php
		// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped in CoreA11y_UI.
		/* translators: %s: date. */
		echo CoreA11y_UI::score_ring( null === $corea11y_before->score ? null : (int) $corea11y_before->score, sprintf( __( 'Before · %s', 'corea11y' ), $corea11y_d1 ), 120 );
		echo '<span class="corea11y-arrow" aria-hidden="true">&rarr;</span>';
		/* translators: %s: date. */
		echo CoreA11y_UI::score_ring( null === $corea11y_after->score ? null : (int) $corea11y_after->score, sprintf( __( 'After · %s', 'corea11y' ), $corea11y_d2 ), 120 );
		// phpcs:enable
		?>
	</div>
	<div class="corea11y-hero-text">
		<h2 id="corea11y-cmp-title"><?php echo esc_html( $corea11y_headline ); ?></h2>
		<?php if ( $corea11y_delta > 0 ) : ?>
			<p><?php esc_html_e( 'New issues usually come from new content, a new page in the scan, or a theme or plugin update. Check the "New" column below.', 'corea11y' ); ?></p>
		<?php elseif ( $corea11y_sum['resolved'] > 0 ) : ?>
			<p>
				<?php
				/* translators: %s: percentage. */
				echo esc_html( sprintf( __( 'You resolved %s%% of the issues found in the earlier audit.', 'corea11y' ), number_format_i18n( $corea11y_pct ) ) );
				?>
			</p>
		<?php endif; ?>
		<div class="corea11y-tiles">
			<?php
			// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped in CoreA11y_UI.
			echo CoreA11y_UI::stat( number_format_i18n( $corea11y_sum['resolved'] ), __( 'resolved', 'corea11y' ), CoreA11y_UI::concept_tip( 'resolved', __( 'resolved', 'corea11y' ) ), 'corea11y-tile-good' );
			echo CoreA11y_UI::stat( number_format_i18n( $corea11y_sum['remaining'] ), __( 'remaining', 'corea11y' ), CoreA11y_UI::concept_tip( 'remaining', __( 'remaining', 'corea11y' ) ) );
			echo CoreA11y_UI::stat( number_format_i18n( $corea11y_sum['new'] ), __( 'new', 'corea11y' ), CoreA11y_UI::concept_tip( 'new', __( 'new', 'corea11y' ) ), 'corea11y-tile-warn' );
			// phpcs:enable
			?>
		</div>
	</div>
</section>

<section class="corea11y-charts">
	<?php
	$corea11y_rows = array();
	foreach ( CoreA11y_Rules::impacts() as $corea11y_key => $corea11y_label ) {
		$corea11y_rows[] = array(
			'label'  => $corea11y_label,
			'before' => $cmp['severity'][ $corea11y_key ]['before'],
			'after'  => $cmp['severity'][ $corea11y_key ]['after'],
			'tip'    => CoreA11y_UI::concept_tip( $corea11y_key, $corea11y_label ),
		);
	}
	$corea11y_rows[] = array(
		'label'  => __( 'Total', 'corea11y' ),
		'before' => $corea11y_tb,
		'after'  => $corea11y_ta,
	);
	echo CoreA11y_UI::paired_bars( $corea11y_rows, __( 'Issues before and after, by severity', 'corea11y' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped in CoreA11y_UI.
	?>
</section>

<?php if ( $cmp['rules'] ) : ?>
	<section class="corea11y-section">
		<h2><?php esc_html_e( 'Change by problem', 'corea11y' ); ?></h2>
		<table class="widefat striped corea11y-table corea11y-compare-table">
			<thead>
				<tr>
					<th scope="col"><?php esc_html_e( 'Problem', 'corea11y' ); ?></th>
					<th scope="col" class="num"><?php esc_html_e( 'Before', 'corea11y' ); ?></th>
					<th scope="col" class="num"><?php esc_html_e( 'After', 'corea11y' ); ?></th>
					<th scope="col" class="num"><?php esc_html_e( 'Resolved', 'corea11y' ); ?></th>
					<th scope="col" class="num"><?php esc_html_e( 'New', 'corea11y' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $cmp['rules'] as $corea11y_r ) : ?>
					<?php $corea11y_ex = CoreA11y_Explain::get( $corea11y_r['rule'], $corea11y_r['help'] ); ?>
					<tr>
						<th scope="row">
							<?php echo esc_html( $corea11y_ex['title'] ); ?>
							<?php
							if ( ! $corea11y_is_print ) {
								echo CoreA11y_UI::rule_tip( $corea11y_ex ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped in CoreA11y_UI.
							}
							?>
							<span class="corea11y-badge corea11y-badge-<?php echo esc_attr( $corea11y_r['impact'] ); ?>"><?php echo esc_html( CoreA11y_Rules::impacts()[ $corea11y_r['impact'] ] ); ?></span>
							<?php if ( 0 === $corea11y_r['after'] && $corea11y_r['before'] > 0 ) : ?>
								<span class="corea11y-badge corea11y-badge-good"><?php esc_html_e( 'Fixed', 'corea11y' ); ?></span>
							<?php endif; ?>
						</th>
						<td class="num"><?php echo esc_html( number_format_i18n( $corea11y_r['before'] ) ); ?></td>
						<td class="num"><?php echo esc_html( number_format_i18n( $corea11y_r['after'] ) ); ?></td>
						<td class="num corea11y-good"><?php echo $corea11y_r['resolved'] ? esc_html( '−' . number_format_i18n( $corea11y_r['resolved'] ) ) : '0'; ?></td>
						<td class="num corea11y-warn"><?php echo $corea11y_r['new'] ? esc_html( '+' . number_format_i18n( $corea11y_r['new'] ) ) : '0'; ?></td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	</section>
<?php endif; ?>

<section class="corea11y-section">
	<h2><?php esc_html_e( 'What was compared', 'corea11y' ); ?></h2>
	<p>
		<?php
		/* translators: %s: number of pages. */
		echo esc_html( sprintf( _n( '%s page was scanned in both audits and compared, plus site-wide content checks.', '%s pages were scanned in both audits and compared, plus site-wide content checks.', count( $cmp['shared'] ), 'corea11y' ), number_format_i18n( count( $cmp['shared'] ) ) ) );
		?>
	</p>
	<?php if ( $cmp['only_before'] || $cmp['only_after'] ) : ?>
		<p><?php esc_html_e( 'These pages were scanned in only one audit, so their issues are not counted as resolved or new:', 'corea11y' ); ?></p>
		<ul>
			<?php foreach ( $cmp['only_before'] as $corea11y_label ) : ?>
				<li>
					<?php
					/* translators: %s: page name. */
					echo esc_html( sprintf( __( '%s (earlier audit only)', 'corea11y' ), $corea11y_label ) );
					?>
				</li>
			<?php endforeach; ?>
			<?php foreach ( $cmp['only_after'] as $corea11y_label ) : ?>
				<li>
					<?php
					/* translators: %s: page name. */
					echo esc_html( sprintf( __( '%s (later audit only)', 'corea11y' ), $corea11y_label ) );
					?>
				</li>
			<?php endforeach; ?>
		</ul>
	<?php endif; ?>
	<p class="corea11y-note"><?php esc_html_e( 'Comparisons are based on automated scan results. An element counts as the same issue when the same problem is found on the same element of the same page.', 'corea11y' ); ?></p>
</section>
