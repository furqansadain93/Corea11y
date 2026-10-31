<?php
/**
 * Before/after comparison screen.
 *
 * @package CoreA11y
 * @var array          $audits    Completed audits, newest first.
 * @var int            $before_id Selected earlier audit.
 * @var int            $after_id  Selected later audit.
 * @var array|WP_Error $cmp       Comparison, or error.
 */

defined( 'ABSPATH' ) || exit;

$corea11y_is_print = false;
$corea11y_fmt      = get_option( 'date_format' ) . ' ' . get_option( 'time_format' );
?>
<div class="wrap corea11y">
	<?php CoreA11y_UI::brand_bar(); ?>
	<div class="corea11y-page-head">
		<div>
			<h1><?php esc_html_e( 'Before & after', 'msacalab-accessibility-checker' ); ?></h1>
			<p class="corea11y-meta"><?php esc_html_e( 'See what you fixed, what is left, and anything new.', 'msacalab-accessibility-checker' ); ?></p>
		</div>
		<?php if ( $cmp && ! is_wp_error( $cmp ) ) : ?>
			<div class="corea11y-actions">
				<a class="button button-primary" href="<?php echo esc_url( CoreA11y_Admin::export_url( 'compare', array( 'before' => $before_id, 'after' => $after_id ) ) ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'Download PDF', 'msacalab-accessibility-checker' ); ?><span class="screen-reader-text"> <?php esc_html_e( '(opens in a new tab)', 'msacalab-accessibility-checker' ); ?></span></a>
			</div>
		<?php endif; ?>
	</div>

	<?php if ( count( $audits ) < 2 ) : ?>
		<div class="corea11y-card corea11y-empty">
			<h2><?php esc_html_e( 'You need two audits to compare', 'msacalab-accessibility-checker' ); ?></h2>
			<p><?php esc_html_e( 'Run an audit, fix a few things, then run another one. This page will show your progress side by side.', 'msacalab-accessibility-checker' ); ?></p>
			<p><a class="button button-primary" href="<?php echo esc_url( admin_url( 'admin.php?page=corea11y' ) ); ?>"><?php esc_html_e( 'Go to the dashboard', 'msacalab-accessibility-checker' ); ?></a></p>
		</div>
	<?php else : ?>
		<form method="get" class="corea11y-card corea11y-compare-form">
			<input type="hidden" name="page" value="corea11y-compare">
			<p>
				<label for="corea11y-before"><?php esc_html_e( 'Before', 'msacalab-accessibility-checker' ); ?></label>
				<select id="corea11y-before" name="before">
					<?php foreach ( $audits as $corea11y_a ) : ?>
						<option value="<?php echo esc_attr( $corea11y_a->id ); ?>" <?php selected( $before_id, (int) $corea11y_a->id ); ?>>
							<?php
							/* translators: 1: date, 2: number of issues. */
							echo esc_html( sprintf( __( '%1$s (%2$s issues)', 'msacalab-accessibility-checker' ), get_date_from_gmt( $corea11y_a->started_at, $corea11y_fmt ), number_format_i18n( (int) $corea11y_a->issues_total ) ) );
							?>
						</option>
					<?php endforeach; ?>
				</select>
			</p>
			<p>
				<label for="corea11y-after"><?php esc_html_e( 'After', 'msacalab-accessibility-checker' ); ?></label>
				<select id="corea11y-after" name="after">
					<?php foreach ( $audits as $corea11y_a ) : ?>
						<option value="<?php echo esc_attr( $corea11y_a->id ); ?>" <?php selected( $after_id, (int) $corea11y_a->id ); ?>>
							<?php
							/* translators: 1: date, 2: number of issues. */
							echo esc_html( sprintf( __( '%1$s (%2$s issues)', 'msacalab-accessibility-checker' ), get_date_from_gmt( $corea11y_a->started_at, $corea11y_fmt ), number_format_i18n( (int) $corea11y_a->issues_total ) ) );
							?>
						</option>
					<?php endforeach; ?>
				</select>
			</p>
			<p><button type="submit" class="button"><?php esc_html_e( 'Compare', 'msacalab-accessibility-checker' ); ?></button></p>
		</form>

		<?php if ( is_wp_error( $cmp ) ) : ?>
			<div class="notice notice-error inline"><p><?php echo esc_html( $cmp->get_error_message() ); ?></p></div>
		<?php elseif ( $cmp ) : ?>
			<?php require COREA11Y_DIR . 'admin/views/partials/compare-body.php'; ?>
		<?php endif; ?>
	<?php endif; ?>
</div>
