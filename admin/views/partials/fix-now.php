<?php
/**
 * "N of your issues can be fixed with one click" box (dashboard and latest report).
 *
 * @package CoreA11y
 * @var object $corea11y_fix_audit Latest complete audit.
 */

defined( 'ABSPATH' ) || exit;

if ( empty( $corea11y_fix_audit ) ) {
	return;
}
$corea11y_opp = CoreA11y_Fixes::opportunity( (int) $corea11y_fix_audit->id );
if ( ! $corea11y_opp['fixes'] && ! $corea11y_opp['locked_issues'] ) {
	return;
}
?>
<section class="corea11y-fixnow" aria-labelledby="corea11y-fixnow-title">
	<svg class="corea11y-fixnow-icon" width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M4 13l5 5L20 7"/></svg>
	<div class="corea11y-fixnow-text">
		<?php if ( $corea11y_opp['fixes'] ) : ?>
			<h2 id="corea11y-fixnow-title">
				<?php
				/* translators: 1: issues a click can fix, 2: all issues in the audit. */
				echo esc_html( sprintf( _n( '%1$s of your %2$s issues can be fixed with one click', '%1$s of your %2$s issues can be fixed with one click', $corea11y_opp['total'], 'corea11y' ), number_format_i18n( $corea11y_opp['issues'] ), number_format_i18n( $corea11y_opp['total'] ) ) );
				?>
			</h2>
			<p>
				<?php
				$corea11y_n = count( $corea11y_opp['fixes'] );
				/* translators: %s: number of quick fixes. */
				echo esc_html( sprintf( _n( 'This turns on %s quick fix. CoreA11y then re-checks your pages to confirm it worked, and it can be undone at any time.', 'This turns on %s quick fixes. CoreA11y then re-checks your pages to confirm they worked, and every fix can be undone.', $corea11y_n, 'corea11y' ), number_format_i18n( $corea11y_n ) ) );
				?>
			</p>
		<?php else : ?>
			<h2 id="corea11y-fixnow-title">
				<?php
				/* translators: %s: number of issues. */
				echo esc_html( sprintf( _n( 'CoreA11y Pro can fix %s more issue with one click', 'CoreA11y Pro can fix %s more issues with one click', $corea11y_opp['locked_issues'], 'corea11y' ), number_format_i18n( $corea11y_opp['locked_issues'] ) ) );
				?>
			</h2>
			<p><?php esc_html_e( 'Activate your CoreA11y Pro license to turn these fixes on.', 'corea11y' ); ?></p>
		<?php endif; ?>
		<?php if ( $corea11y_opp['fixes'] && $corea11y_opp['locked_issues'] ) : ?>
			<p class="corea11y-fixnow-more">
				<?php
				/* translators: %s: number of issues. */
				echo esc_html( sprintf( _n( 'CoreA11y Pro can fix %s more.', 'CoreA11y Pro can fix %s more.', $corea11y_opp['locked_issues'], 'corea11y' ), number_format_i18n( $corea11y_opp['locked_issues'] ) ) );
				?>
			</p>
		<?php endif; ?>
	</div>
	<div class="corea11y-fixnow-actions">
		<?php if ( $corea11y_opp['fixes'] ) : ?>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="corea11y_fix_now">
				<?php wp_nonce_field( 'corea11y_fix_now' ); ?>
				<button type="submit" class="button button-primary button-hero"><?php esc_html_e( 'Fix them now', 'corea11y' ); ?></button>
			</form>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=corea11y-fixes' ) ); ?>"><?php esc_html_e( 'Choose fixes yourself', 'corea11y' ); ?></a>
		<?php else : ?>
			<a class="button button-primary button-hero" href="<?php echo esc_url( (string) apply_filters( 'corea11y_license_url', admin_url( 'admin.php?page=corea11y-fixes' ) ) ); ?>"><?php esc_html_e( 'Activate Pro', 'corea11y' ); ?></a>
		<?php endif; ?>
	</div>
</section>
