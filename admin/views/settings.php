<?php
/**
 * Settings view.
 *
 * @package CoreA11y
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="wrap corea11y">
	<h1><?php esc_html_e( 'CoreA11y Settings', 'corea11y' ); ?></h1>
	<?php settings_errors(); ?>
	<form action="options.php" method="post">
		<?php
		settings_fields( CoreA11y_Settings::GROUP );
		do_settings_sections( CoreA11y_Settings::PAGE );
		submit_button();
		?>
	</form>

	<h2><?php esc_html_e( 'Support information', 'corea11y' ); ?></h2>
	<p id="corea11y-support-help"><?php esc_html_e( 'If something isn\'t working, copy this into your support request. It describes your setup and stays on your site until you choose to share it.', 'corea11y' ); ?></p>
	<p><label for="corea11y-support-info" class="screen-reader-text"><?php esc_html_e( 'Support information', 'corea11y' ); ?></label>
	<textarea id="corea11y-support-info" class="large-text code" rows="10" readonly aria-describedby="corea11y-support-help"><?php echo esc_textarea( CoreA11y_Compat::support_info() ); ?></textarea></p>
	<p>
		<button type="button" class="button" id="corea11y-copy-info" hidden data-copied="<?php esc_attr_e( 'Copied to your clipboard.', 'corea11y' ); ?>" data-select="<?php esc_attr_e( 'Selected. Press Ctrl+C (or Cmd+C) to copy.', 'corea11y' ); ?>"><?php esc_html_e( 'Copy support information', 'corea11y' ); ?></button>
		<span id="corea11y-copy-status" role="status"></span>
	</p>
</div>
