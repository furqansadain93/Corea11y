<?php
/**
 * Dashboard view.
 *
 * @package CoreA11y
 * @var object|null $latest   Latest completed audit row.
 * @var object|null $previous Previous completed audit row.
 */

defined( 'ABSPATH' ) || exit;

$corea11y_checks = CoreA11y_Checklist::summary();
$corea11y_total  = array_sum( $corea11y_checks );
$corea11y_done   = $corea11y_total - (int) $corea11y_checks[''];
$corea11y_scopes = CoreA11y_Settings::scopes();
$corea11y_scope  = CoreA11y_Settings::get( 'scan_scope' );
$corea11y_user   = wp_get_current_user();
?>
<div class="wrap corea11y">
	<?php CoreA11y_UI::brand_bar(); ?>
	<div class="corea11y-page-head">
		<div>
			<h1>
				<?php
				/* translators: %s: user's display name. */
				echo esc_html( sprintf( __( 'Hi %s, let\'s make your site welcoming for everyone', 'corea11y' ), $corea11y_user->display_name ) );
				?>
			</h1>
			<p class="corea11y-meta"><?php esc_html_e( 'CoreA11y finds accessibility problems, explains them in plain language and tracks your progress.', 'corea11y' ); ?></p>
		</div>
	</div>

	<?php
	if ( $latest ) {
		$corea11y_fix_audit = $latest;
		require COREA11Y_DIR . 'admin/views/partials/fix-now.php';
	}
	?>

	<?php if ( ! $latest ) : ?>
		<ol class="corea11y-steps">
			<li class="corea11y-step"><h2><?php esc_html_e( 'Run an audit', 'corea11y' ); ?></h2><p><?php esc_html_e( 'We check your pages against WCAG 2.2. It takes about a minute.', 'corea11y' ); ?></p></li>
			<li class="corea11y-step"><h2><?php esc_html_e( 'Read the report', 'corea11y' ); ?></h2><p><?php esc_html_e( 'Every problem comes with a "?" that explains why it matters and how to fix it.', 'corea11y' ); ?></p></li>
			<li class="corea11y-step"><h2><?php esc_html_e( 'Fix, starting with critical', 'corea11y' ); ?></h2><p><?php esc_html_e( 'Critical problems block some visitors completely, so they make the biggest difference.', 'corea11y' ); ?></p></li>
			<li class="corea11y-step"><h2><?php esc_html_e( 'Re-audit and celebrate', 'corea11y' ); ?></h2><p><?php esc_html_e( 'Compare before and after, and download a PDF to share your progress.', 'corea11y' ); ?></p></li>
		</ol>
	<?php endif; ?>

	<div class="corea11y-card">
		<h2><?php echo $latest ? esc_html__( 'Run a new audit', 'corea11y' ) : esc_html__( 'Ready when you are', 'corea11y' ); ?></h2>
		<p>
			<?php
			printf(
				/* translators: %s: the selected set of pages, e.g. "One page per template". */
				esc_html__( 'Pages to scan: %s.', 'corea11y' ),
				'<strong>' . esc_html( isset( $corea11y_scopes[ $corea11y_scope ] ) ? $corea11y_scopes[ $corea11y_scope ] : $corea11y_scope ) . '</strong>'
			);
			?>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=' . CoreA11y_Settings::PAGE ) ); ?>"><?php esc_html_e( 'Change', 'corea11y' ); ?></a>
		</p>
		<p class="corea11y-meta"><?php esc_html_e( 'Keep this tab open while the audit runs. Your pages are opened quietly in the background, so visitors notice nothing.', 'corea11y' ); ?></p>
		<p>
			<button type="button" class="button button-primary button-hero" id="corea11y-run-audit"><?php echo $latest ? esc_html__( 'Run audit', 'corea11y' ) : esc_html__( 'Run my first audit', 'corea11y' ); ?></button>
			<button type="button" class="button" id="corea11y-stop-audit" hidden><?php esc_html_e( 'Stop and save results so far', 'corea11y' ); ?></button>
		</p>
		<div id="corea11y-progress" class="corea11y-progress-wrap" hidden>
			<p id="corea11y-progress-text" role="status" aria-live="polite"></p>
			<progress id="corea11y-progress-bar" value="0" max="1" aria-labelledby="corea11y-progress-text"></progress>
			<ul id="corea11y-progress-log" class="corea11y-log"></ul>
		</div>
	</div>

	<?php if ( $latest ) : ?>
		<?php
		$corea11y_band = CoreA11y_UI::band( null === $latest->score ? null : (int) $latest->score );
		$corea11y_diff = $previous ? (int) $latest->issues_total - (int) $previous->issues_total : null;
		?>
		<section class="corea11y-hero" aria-labelledby="corea11y-latest-title">
			<?php echo CoreA11y_UI::score_ring( null === $latest->score ? null : (int) $latest->score ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped in CoreA11y_UI. ?>
			<div class="corea11y-hero-text">
				<h2 id="corea11y-latest-title">
					<?php
					if ( null !== $corea11y_diff && $corea11y_diff < 0 ) {
						/* translators: %s: number of issues. */
						echo esc_html( sprintf( _n( 'Great progress: %s fewer issue than last time!', 'Great progress: %s fewer issues than last time!', -$corea11y_diff, 'corea11y' ), number_format_i18n( -$corea11y_diff ) ) );
					} elseif ( 0 === (int) $latest->issues_total ) {
						esc_html_e( 'The automated checks found no problems in your latest audit. Great work! Use the manual checklist for what software can\'t check.', 'corea11y' );
					} else {
						/* translators: %s: number of issues. */
						echo esc_html( sprintf( _n( 'Your latest audit found %s issue', 'Your latest audit found %s issues', (int) $latest->issues_total, 'corea11y' ), number_format_i18n( (int) $latest->issues_total ) ) );
					}
					?>
				</h2>
				<p>
					<?php
					/* translators: 1: date, 2: number of pages. */
					echo esc_html( sprintf( __( 'Audited %1$s across %2$s pages.', 'corea11y' ), get_date_from_gmt( $latest->started_at, get_option( 'date_format' ) ), number_format_i18n( (int) $latest->pages_scanned ) ) );
					echo ' ';
					echo CoreA11y_UI::concept_tip( 'score', __( 'Scan score', 'corea11y' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped in CoreA11y_UI.
					?>
				</p>
				<p class="corea11y-actions">
					<a class="button button-primary" href="<?php echo esc_url( CoreA11y_Admin::report_url( $latest->id ) ); ?>"><?php esc_html_e( 'View report', 'corea11y' ); ?></a>
					<a class="button" href="<?php echo esc_url( CoreA11y_Admin::export_url( 'report', array( 'audit' => $latest->id ) ) ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'Download PDF', 'corea11y' ); ?><span class="screen-reader-text"> <?php esc_html_e( '(opens in a new tab)', 'corea11y' ); ?></span></a>
					<?php if ( $previous ) : ?>
						<a class="button" href="<?php echo esc_url( CoreA11y_Admin::compare_url( $previous->id, $latest->id ) ); ?>"><?php esc_html_e( 'See before & after', 'corea11y' ); ?></a>
					<?php endif; ?>
				</p>
			</div>
		</section>
	<?php endif; ?>

	<div class="corea11y-card">
		<h2>
			<?php esc_html_e( 'Manual checklist', 'corea11y' ); ?>
			<?php echo CoreA11y_UI::tip( __( 'Manual checklist', 'corea11y' ), __( 'Some WCAG requirements need human judgment, like whether alt text is meaningful or a video\'s captions are accurate. The checklist walks you through them one by one.', 'corea11y' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped in CoreA11y_UI. ?>
		</h2>
		<p>
			<?php
			printf(
				/* translators: 1: number of checks reviewed, 2: total number of checks, 3: number passed. */
				esc_html__( '%1$d of %2$d checks reviewed, %3$d passed.', 'corea11y' ),
				(int) $corea11y_done,
				(int) $corea11y_total,
				(int) $corea11y_checks['pass']
			);
			?>
		</p>
		<?php
		$corea11y_rows = array(
			array(
				'label' => __( 'Passed', 'corea11y' ),
				'value' => (int) $corea11y_checks['pass'],
				'class' => 'corea11y-fill-brand',
			),
			array(
				'label' => __( 'Failed', 'corea11y' ),
				'value' => (int) $corea11y_checks['fail'],
				'class' => 'corea11y-fill-critical',
			),
			array(
				'label' => __( 'Not checked yet', 'corea11y' ),
				'value' => (int) $corea11y_checks[''],
				'class' => 'corea11y-fill-minor',
			),
		);
		echo CoreA11y_UI::bars( $corea11y_rows, __( 'Checklist progress', 'corea11y' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped in CoreA11y_UI.
		?>
		<p><a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=corea11y-checklist' ) ); ?>"><?php esc_html_e( 'Open the checklist', 'corea11y' ); ?></a></p>
	</div>

	<p class="corea11y-note">
		<?php esc_html_e( 'CoreA11y reports automated scan results and your own manual checks. Automated tools can\'t confirm full WCAG conformance on their own.', 'corea11y' ); ?>
	</p>
</div>
