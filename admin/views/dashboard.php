<?php
/**
 * Dashboard view.
 *
 * @package CoreA11y
 * @var object|null $latest   Latest audit row.
 * @var object|null $previous Previous audit row.
 */

defined( 'ABSPATH' ) || exit;

$corea11y_checks = CoreA11y_Checklist::summary();
$corea11y_total  = array_sum( $corea11y_checks );
$corea11y_scopes = CoreA11y_Settings::scopes();
$corea11y_scope  = CoreA11y_Settings::get( 'scan_scope' );
?>
<div class="wrap corea11y">
	<h1><?php esc_html_e( 'CoreA11y Dashboard', 'corea11y' ); ?></h1>

	<div class="corea11y-card">
		<h2><?php echo $latest ? esc_html__( 'Run a new audit', 'corea11y' ) : esc_html__( 'Run your first audit', 'corea11y' ); ?></h2>
		<p>
			<?php
			printf(
				/* translators: %s: the selected set of pages, e.g. "One page per template". */
				esc_html__( 'Pages to scan: %s.', 'corea11y' ),
				esc_html( isset( $corea11y_scopes[ $corea11y_scope ] ) ? $corea11y_scopes[ $corea11y_scope ] : $corea11y_scope )
			);
			?>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=' . CoreA11y_Settings::PAGE ) ); ?>"><?php esc_html_e( 'Change', 'corea11y' ); ?></a>
		</p>
		<p><?php esc_html_e( 'Keep this tab open while the audit runs. Each page is loaded in the background and checked with axe-core.', 'corea11y' ); ?></p>
		<p>
			<button type="button" class="button button-primary" id="corea11y-run-audit"><?php esc_html_e( 'Run audit', 'corea11y' ); ?></button>
			<button type="button" class="button" id="corea11y-stop-audit" hidden><?php esc_html_e( 'Stop and save results so far', 'corea11y' ); ?></button>
		</p>
		<div id="corea11y-progress" hidden>
			<p id="corea11y-progress-text" role="status" aria-live="polite"></p>
			<progress id="corea11y-progress-bar" value="0" max="1" aria-labelledby="corea11y-progress-text"></progress>
			<ul id="corea11y-progress-log" class="corea11y-log"></ul>
		</div>
	</div>

	<?php if ( $latest ) : ?>
		<?php
		$corea11y_summary = json_decode( (string) $latest->summary, true );
		$corea11y_counts  = isset( $corea11y_summary['counts'] ) ? $corea11y_summary['counts'] : null;
		?>
		<h2><?php esc_html_e( 'Latest audit', 'corea11y' ); ?></h2>
		<div class="corea11y-grid">
			<div class="corea11y-card">
				<p class="corea11y-stat"><?php echo esc_html( number_format_i18n( (int) $latest->issues_total ) ); ?></p>
				<p><?php esc_html_e( 'issues found', 'corea11y' ); ?></p>
				<?php if ( $previous && 'complete' === $previous->status ) : ?>
					<p>
						<?php
						$corea11y_diff = (int) $latest->issues_total - (int) $previous->issues_total;
						printf(
							/* translators: %s: change in issue count since the previous audit, e.g. "-12". */
							esc_html__( 'Change since previous audit: %s', 'corea11y' ),
							esc_html( ( $corea11y_diff > 0 ? '+' : '' ) . number_format_i18n( $corea11y_diff ) )
						);
						?>
					</p>
				<?php endif; ?>
			</div>
			<div class="corea11y-card">
				<p class="corea11y-stat"><?php echo null === $latest->score ? '—' : esc_html( (int) $latest->score ); ?></p>
				<p><?php esc_html_e( 'scan score out of 100', 'corea11y' ); ?></p>
			</div>
			<div class="corea11y-card">
				<p class="corea11y-stat"><?php echo esc_html( number_format_i18n( (int) $latest->pages_scanned ) ); ?></p>
				<p><?php esc_html_e( 'pages scanned', 'corea11y' ); ?></p>
			</div>
		</div>
		<?php if ( $corea11y_counts ) : ?>
			<p>
				<?php
				$corea11y_parts = array();
				foreach ( CoreA11y_Rules::impacts() as $corea11y_key => $corea11y_label ) {
					$corea11y_parts[] = sprintf( '%s: %s', $corea11y_label, number_format_i18n( (int) $corea11y_counts['impact'][ $corea11y_key ] ) );
				}
				echo esc_html( implode( ' · ', $corea11y_parts ) );
				?>
			</p>
		<?php endif; ?>
		<p><a class="button" href="<?php echo esc_url( CoreA11y_Admin::report_url( $latest->id ) ); ?>"><?php esc_html_e( 'View full report', 'corea11y' ); ?></a></p>
	<?php endif; ?>

	<div class="corea11y-card">
		<h2><?php esc_html_e( 'Manual checklist', 'corea11y' ); ?></h2>
		<p>
			<?php
			printf(
				/* translators: 1: number of checks marked pass, 2: total number of checks. */
				esc_html__( '%1$d of %2$d manual checks passed.', 'corea11y' ),
				(int) $corea11y_checks['pass'],
				(int) $corea11y_total
			);
			?>
		</p>
		<p><a href="<?php echo esc_url( admin_url( 'admin.php?page=corea11y-checklist' ) ); ?>"><?php esc_html_e( 'Open the checklist', 'corea11y' ); ?></a></p>
	</div>

	<p class="corea11y-note">
		<?php esc_html_e( 'CoreA11y reports automated scan results and your own manual checks. Automated tools cannot confirm full WCAG conformance on their own.', 'corea11y' ); ?>
	</p>
</div>
