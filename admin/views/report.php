<?php
/**
 * Audit report screen.
 *
 * @package CoreA11y
 * @var object      $audit      Audit row.
 * @var array       $counts     Counts.
 * @var array       $groups     Grouped issues.
 * @var array       $principles Principle counts.
 * @var array       $top_pages  Top pages.
 * @var object|null $previous   Previous completed audit, if any.
 */

defined( 'ABSPATH' ) || exit;

$corea11y_is_print = false;
$corea11y_date     = $audit->started_at ? get_date_from_gmt( $audit->started_at, get_option( 'date_format' ) . ' ' . get_option( 'time_format' ) ) : '';
?>
<div class="wrap corea11y">
	<?php CoreA11y_UI::brand_bar(); ?>
	<p class="corea11y-crumb"><a href="<?php echo esc_url( admin_url( 'admin.php?page=corea11y-audits' ) ); ?>">&larr; <?php esc_html_e( 'All audits', 'corea11y' ); ?></a></p>
	<div class="corea11y-page-head">
		<div>
			<h1><?php esc_html_e( 'Audit report', 'corea11y' ); ?></h1>
			<p class="corea11y-meta"><?php echo esc_html( $corea11y_date ); ?> · <?php echo esc_html( wp_parse_url( home_url(), PHP_URL_HOST ) ); ?></p>
		</div>
		<div class="corea11y-actions">
			<a class="button button-primary" href="<?php echo esc_url( CoreA11y_Admin::export_url( 'report', array( 'audit' => $audit->id ) ) ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'Download PDF', 'corea11y' ); ?><span class="screen-reader-text"> <?php esc_html_e( '(opens in a new tab)', 'corea11y' ); ?></span></a>
			<?php
			/**
			 * Add buttons next to "Download PDF" on an audit report (CoreA11y Pro adds CSV export).
			 *
			 * @param object $audit Audit row.
			 */
			do_action( 'corea11y_report_actions', $audit );
			?>
			<?php if ( $previous ) : ?>
				<a class="button" href="<?php echo esc_url( CoreA11y_Admin::compare_url( $previous->id, $audit->id ) ); ?>"><?php esc_html_e( 'Compare with previous audit', 'corea11y' ); ?></a>
			<?php endif; ?>
			<a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=corea11y' ) ); ?>"><?php esc_html_e( 'Run a new audit', 'corea11y' ); ?></a>
		</div>
	</div>

	<?php if ( 'complete' !== $audit->status ) : ?>
		<div class="notice notice-warning inline"><p>
			<?php
			/* translators: %s: audit status, e.g. "running" or "abandoned". */
			echo esc_html( sprintf( __( 'This audit did not finish (status: %s). Results may be incomplete.', 'corea11y' ), $audit->status ) );
			?>
		</p></div>
	<?php endif; ?>

	<?php
	// phpcs:disable WordPress.Security.NonceVerification.Recommended -- Display-only flags after redirect.
	$corea11y_dfp      = isset( $_GET['dismissed'] ) ? sanitize_key( wp_unslash( $_GET['dismissed'] ) ) : '';
	$corea11y_restored = isset( $_GET['restored'] );
	// phpcs:enable
	?>
	<div id="corea11y-notice" tabindex="-1">
		<?php if ( $corea11y_dfp ) : ?>
			<div class="notice notice-success inline corea11y-verify" role="status">
				<p><strong><?php esc_html_e( 'Issue dismissed.', 'corea11y' ); ?></strong> <?php esc_html_e( 'It no longer counts in this report, and it stays dismissed in future audits.', 'corea11y' ); ?></p>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<input type="hidden" name="action" value="corea11y_restore">
					<input type="hidden" name="fp" value="<?php echo esc_attr( $corea11y_dfp ); ?>">
					<input type="hidden" name="audit" value="<?php echo esc_attr( $audit->id ); ?>">
					<?php wp_nonce_field( 'corea11y_restore' ); ?>
					<p><button type="submit" class="button"><?php esc_html_e( 'Undo', 'corea11y' ); ?></button></p>
				</form>
			</div>
		<?php elseif ( $corea11y_restored ) : ?>
			<div class="notice notice-info inline" role="status"><p><?php esc_html_e( 'Issue restored. It counts in your reports again.', 'corea11y' ); ?></p></div>
		<?php endif; ?>
	</div>

	<?php
	$corea11y_newest = CoreA11y_Admin::recent_audits( 1, 'complete' );
	if ( $corea11y_newest && (int) $corea11y_newest[0]->id === (int) $audit->id ) {
		$corea11y_fix_audit = $audit;
		require COREA11Y_DIR . 'admin/views/partials/fix-now.php';
	}
	?>

	<?php require COREA11Y_DIR . 'admin/views/partials/report-body.php'; ?>
</div>
