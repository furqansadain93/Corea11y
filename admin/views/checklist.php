<?php
/**
 * Manual checklist view.
 *
 * @package CoreA11y
 * @var array $items   Checklist items.
 * @var array $results Saved results.
 */

defined( 'ABSPATH' ) || exit;

$corea11y_statuses = CoreA11y_Checklist::statuses();
?>
<div class="wrap corea11y">
	<h1><?php esc_html_e( 'Manual checklist', 'corea11y' ); ?></h1>
	<p class="corea11y-lead"><?php esc_html_e( 'Automated scans can\'t judge these WCAG 2.2 criteria. Check each one by hand, then record the result.', 'corea11y' ); ?></p>

	<?php // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Display-only flag after redirect. ?>
	<?php if ( isset( $_GET['updated'] ) ) : ?>
		<div class="notice notice-success is-dismissible" role="status"><p><?php esc_html_e( 'Checklist saved.', 'corea11y' ); ?></p></div>
	<?php endif; ?>

	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
		<input type="hidden" name="action" value="corea11y_save_checklist">
		<?php wp_nonce_field( 'corea11y_save_checklist' ); ?>

		<ol class="corea11y-checklist">
			<?php foreach ( $items as $corea11y_id => $corea11y_item ) : ?>
				<?php
				list( $corea11y_sc, $corea11y_level, $corea11y_title, $corea11y_how ) = $corea11y_item;
				$corea11y_current = isset( $results[ $corea11y_id ]['status'] ) ? $results[ $corea11y_id ]['status'] : '';
				$corea11y_field   = 'corea11y-check-' . $corea11y_id;
				?>
				<li class="corea11y-card">
					<h2 class="corea11y-check-title">
						<?php echo esc_html( $corea11y_title ); ?>
						<?php if ( $corea11y_sc ) : ?>
							<span class="corea11y-sc">
								<?php
								/* translators: 1: WCAG success criterion number, 2: conformance level (A or AA). */
								echo esc_html( sprintf( __( 'WCAG %1$s (%2$s)', 'corea11y' ), $corea11y_sc, $corea11y_level ) );
								?>
							</span>
						<?php endif; ?>
					</h2>
					<p id="<?php echo esc_attr( $corea11y_field . '-how' ); ?>"><?php echo esc_html( $corea11y_how ); ?></p>
					<label for="<?php echo esc_attr( $corea11y_field ); ?>"><?php esc_html_e( 'Result', 'corea11y' ); ?></label>
					<select id="<?php echo esc_attr( $corea11y_field ); ?>" name="corea11y_check[<?php echo esc_attr( $corea11y_id ); ?>]" aria-describedby="<?php echo esc_attr( $corea11y_field . '-how' ); ?>">
						<?php foreach ( $corea11y_statuses as $corea11y_value => $corea11y_label ) : ?>
							<option value="<?php echo esc_attr( $corea11y_value ); ?>" <?php selected( $corea11y_current, $corea11y_value ); ?>><?php echo esc_html( $corea11y_label ); ?></option>
						<?php endforeach; ?>
					</select>
				</li>
			<?php endforeach; ?>
		</ol>

		<?php submit_button( __( 'Save checklist', 'corea11y' ) ); ?>
	</form>
</div>
