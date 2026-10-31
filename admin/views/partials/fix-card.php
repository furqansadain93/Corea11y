<?php
/**
 * One quick fix card on the Quick fixes screen (used inside the fixes form).
 *
 * @package CoreA11y
 * @var string $corea11y_id  Fix id.
 * @var array  $corea11y_fix Fix definition.
 * Plus the Quick fixes view's $state, $found, $latest and $verified.
 */

defined( 'ABSPATH' ) || exit;
?>
	<?php
	$corea11y_field = 'corea11y-fix-' . $corea11y_id;
	$corea11y_count  = isset( $found[ $corea11y_id ] ) ? (int) $found[ $corea11y_id ] : 0;
	$corea11y_locked = ! $state[ $corea11y_id ] && CoreA11y_Fixes::is_locked( $corea11y_id );
	?>
	<li class="corea11y-fix<?php echo $state[ $corea11y_id ] ? ' is-on' : ''; ?><?php echo $corea11y_locked ? ' is-locked' : ''; ?>">
		<div class="corea11y-fix-text">
			<h2 class="corea11y-fix-title"><label for="<?php echo esc_attr( $corea11y_field ); ?>"><?php echo esc_html( $corea11y_fix['title'] ); ?></label></h2>
			<p id="<?php echo esc_attr( $corea11y_field . '-desc' ); ?>"><?php echo esc_html( $corea11y_fix['desc'] ); ?></p>
			<?php if ( ! empty( $corea11y_fix['look'] ) ) : ?>
				<p class="corea11y-fix-look"><?php esc_html_e( 'This fix changes how your site looks. Check a few pages after turning it on.', 'msacalab-accessibility-checker' ); ?></p>
			<?php endif; ?>
			<?php if ( $corea11y_locked ) : ?>
				<p class="corea11y-fix-lock" id="<?php echo esc_attr( $corea11y_field . '-lock' ); ?>">
					<?php esc_html_e( 'Activate your MSACAlab Accessibility Checker Pro license to turn this fix on.', 'msacalab-accessibility-checker' ); ?>
					<?php
					$corea11y_lic = apply_filters( 'corea11y_license_url', '' );
					if ( $corea11y_lic ) :
						?>
						<a href="<?php echo esc_url( $corea11y_lic ); ?>"><?php esc_html_e( 'Activate license', 'msacalab-accessibility-checker' ); ?></a>
					<?php endif; ?>
				</p>
			<?php endif; ?>
			<p class="corea11y-badges">
				<?php if ( ! empty( $corea11y_fix['pro'] ) ) : ?>
					<span class="corea11y-badge corea11y-badge-pro"><?php esc_html_e( 'Pro', 'msacalab-accessibility-checker' ); ?></span>
				<?php endif; ?>
				<?php if ( '' !== $corea11y_fix['wcag'] ) : ?>
					<span class="corea11y-badge">
						<?php
						/* translators: %s: WCAG success criterion numbers. */
						echo esc_html( sprintf( __( 'WCAG %s', 'msacalab-accessibility-checker' ), $corea11y_fix['wcag'] ) );
						?>
					</span>
				<?php else : ?>
					<span class="corea11y-badge"><?php esc_html_e( 'Best practice', 'msacalab-accessibility-checker' ); ?></span>
				<?php endif; ?>
				<?php if ( $state[ $corea11y_id ] && isset( $verified[ $corea11y_id ] ) ) : ?>
					<?php $corea11y_v = $verified[ $corea11y_id ]; ?>
					<span class="corea11y-badge <?php echo 'verified' === $corea11y_v['status'] ? 'corea11y-badge-good' : 'corea11y-badge-moderate'; ?>">
						<?php echo esc_html( CoreA11y_UI::verify_text( $corea11y_v ) ); ?>
					</span>
				<?php elseif ( $latest && $corea11y_count ) : ?>
					<span class="corea11y-badge corea11y-badge-serious">
						<?php
						/* translators: %s: number of issues. */
						echo esc_html( sprintf( _n( 'Fixes %s issue in your latest audit', 'Fixes %s issues in your latest audit', $corea11y_count, 'msacalab-accessibility-checker' ), number_format_i18n( $corea11y_count ) ) );
						?>
					</span>
				<?php elseif ( $latest && $corea11y_fix['rules'] ) : ?>
					<span class="corea11y-badge corea11y-badge-good"><?php esc_html_e( 'Nothing to fix on your site', 'msacalab-accessibility-checker' ); ?></span>
				<?php elseif ( ! $corea11y_fix['rules'] ) : ?>
					<span class="corea11y-badge"><?php esc_html_e( 'Recommended for every site', 'msacalab-accessibility-checker' ); ?></span>
				<?php endif; ?>
			</p>
		</div>
		<div class="corea11y-fix-toggle">
			<?php if ( $state[ $corea11y_id ] ) : ?>
				<button type="submit" class="button corea11y-undo-btn" name="corea11y_undo_fix" value="<?php echo esc_attr( $corea11y_id ); ?>">
					<?php esc_html_e( 'Undo', 'msacalab-accessibility-checker' ); ?><span class="screen-reader-text"> <?php echo esc_html( $corea11y_fix['title'] ); ?></span>
				</button>
			<?php endif; ?>
			<input type="checkbox" role="switch" class="corea11y-switch" id="<?php echo esc_attr( $corea11y_field ); ?>" name="corea11y_fix[<?php echo esc_attr( $corea11y_id ); ?>]" value="1" aria-describedby="<?php echo esc_attr( $corea11y_field . '-desc' . ( $corea11y_locked ? ' ' . $corea11y_field . '-lock' : '' ) ); ?>" <?php checked( $state[ $corea11y_id ] ); ?> <?php disabled( $corea11y_locked ); ?>>
		</div>
	</li>
