<?php
/**
 * Settings view.
 *
 * @package CoreA11y
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="wrap corea11y">
	<?php CoreA11y_UI::brand_bar(); ?>
	<h1><?php esc_html_e( 'MSACAlab Accessibility Checker settings', 'msacalab-accessibility-checker' ); ?></h1>
	<?php settings_errors(); ?>
	<form action="options.php" method="post">
		<?php
		settings_fields( CoreA11y_Settings::GROUP );
		do_settings_sections( CoreA11y_Settings::PAGE );
		submit_button();
		?>
	</form>

	<?php
	$corea11y_show_products = false;
	require COREA11Y_DIR . 'admin/views/partials/help.php';
	?>

	<h2 id="corea11y-support"><?php esc_html_e( 'Support information', 'msacalab-accessibility-checker' ); ?></h2>
	<p id="corea11y-support-help"><?php esc_html_e( 'If something isn\'t working, copy this into your support request. It describes your setup and stays on your site until you choose to share it.', 'msacalab-accessibility-checker' ); ?></p>
	<p><label for="corea11y-support-info" class="screen-reader-text"><?php esc_html_e( 'Support information', 'msacalab-accessibility-checker' ); ?></label>
	<textarea id="corea11y-support-info" class="large-text code" rows="10" readonly aria-describedby="corea11y-support-help"><?php echo esc_textarea( CoreA11y_Compat::support_info() ); ?></textarea></p>
	<p>
		<button type="button" class="button" id="corea11y-copy-info" hidden data-copied="<?php esc_attr_e( 'Copied to your clipboard.', 'msacalab-accessibility-checker' ); ?>" data-select="<?php esc_attr_e( 'Selected. Press Ctrl+C (or Cmd+C) to copy.', 'msacalab-accessibility-checker' ); ?>"><?php esc_html_e( 'Copy support information', 'msacalab-accessibility-checker' ); ?></button>
		<span id="corea11y-copy-status" role="status"></span>
	</p>
</div>
