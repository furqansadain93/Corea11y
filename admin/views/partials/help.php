<?php
/**
 * Help and support box.
 *
 * @package CoreA11y
 */

defined( 'ABSPATH' ) || exit;

$corea11y_guide   = (string) apply_filters( 'corea11y_guide_url', 'https://corea11y.com/docs/wordpress/' );
$corea11y_forum   = 'https://wordpress.org/support/plugin/msacalab-accessibility-checker/';
$corea11y_mail    = 'support@corea11y.com';
$corea11y_is_pro  = CoreA11y_Pro_Catalog::pro_installed();
?>
<div class="corea11y-help">
	<section class="corea11y-card" aria-labelledby="corea11y-help-title">
		<h2 id="corea11y-help-title"><?php esc_html_e( 'Help and support', 'msacalab-accessibility-checker' ); ?></h2>
		<ul>
			<li><a href="<?php echo esc_url( $corea11y_guide ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'User guide', 'msacalab-accessibility-checker' ); ?><span class="screen-reader-text"> <?php esc_html_e( '(opens in a new tab)', 'msacalab-accessibility-checker' ); ?></span></a></li>
			<?php if ( $corea11y_is_pro ) : ?>
				<li>
					<?php esc_html_e( 'Pro support:', 'msacalab-accessibility-checker' ); ?>
					<a href="<?php echo esc_url( 'mailto:' . $corea11y_mail ); ?>"><?php echo esc_html( $corea11y_mail ); ?></a>
				</li>
			<?php else : ?>
				<li><a href="<?php echo esc_url( $corea11y_forum ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'Ask in the support forum on WordPress.org', 'msacalab-accessibility-checker' ); ?><span class="screen-reader-text"> <?php esc_html_e( '(opens in a new tab)', 'msacalab-accessibility-checker' ); ?></span></a></li>
				<li>
					<?php esc_html_e( 'Pro customers:', 'msacalab-accessibility-checker' ); ?>
					<a href="<?php echo esc_url( 'mailto:' . $corea11y_mail ); ?>"><?php echo esc_html( $corea11y_mail ); ?></a>
				</li>
			<?php endif; ?>
			<li>
				<?php esc_html_e( 'Billing, licenses and refunds:', 'msacalab-accessibility-checker' ); ?>
				<a href="<?php echo esc_url( 'mailto:' . $corea11y_mail ); ?>"><?php echo esc_html( $corea11y_mail ); ?></a>
			</li>
			<li><a href="<?php echo esc_url( admin_url( 'admin.php?page=' . CoreA11y_Settings::PAGE . '#corea11y-support' ) ); ?>"><?php esc_html_e( 'Copy your support information', 'msacalab-accessibility-checker' ); ?></a> <span class="corea11y-muted"><?php esc_html_e( '(include it when you ask for help)', 'msacalab-accessibility-checker' ); ?></span></li>
		</ul>
	</section>

</div>
