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
</div>
