<?php
/**
 * CoreA11y Pro settings, shown as an extra section on the CoreA11y Settings screen.
 *
 * @package CoreA11y
 */

defined( 'ABSPATH' ) || exit;

/**
 * Pro settings.
 */
class CoreA11y_Pro_Settings {

	const OPTION = 'corea11y_pro';

	/**
	 * Hook into WordPress.
	 */
	public static function register() {
		add_action( 'admin_init', array( __CLASS__, 'fields' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'assets' ) );
		add_action( 'update_option_' . self::OPTION, array( __CLASS__, 'changed' ), 10, 2 );
		add_action( 'add_option_' . self::OPTION, array( __CLASS__, 'added' ), 10, 2 );
	}

	/**
	 * Defaults.
	 *
	 * @return array
	 */
	public static function defaults() {
		return array(
			'weekly'      => true,
			'report_to'   => '',
			'brand_name'  => '',
			'brand_logo'  => 0,
			'brand_color' => '',
			'brand_foot'  => '',
		);
	}

	/**
	 * All settings, or one.
	 *
	 * @param string|null $key Key.
	 * @return mixed
	 */
	public static function get( $key = null ) {
		$saved = get_option( self::OPTION, array() );
		$all   = wp_parse_args( is_array( $saved ) ? $saved : array(), self::defaults() );
		if ( null === $key ) {
			return $all;
		}
		return isset( $all[ $key ] ) ? $all[ $key ] : null;
	}

	/**
	 * Email addresses that receive the weekly report.
	 *
	 * @return string[]
	 */
	public static function recipients() {
		$list = array_filter( array_map( 'trim', explode( ',', (string) self::get( 'report_to' ) ) ), 'is_email' );
		return $list ? array_values( $list ) : array( get_option( 'admin_email' ) );
	}

	/**
	 * Register the section and fields on Lite's Settings screen.
	 */
	public static function fields() {
		register_setting(
			CoreA11y_Settings::GROUP,
			self::OPTION,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( __CLASS__, 'sanitize' ),
				'default'           => self::defaults(),
			)
		);

		add_settings_section( 'corea11y_pro_monitor', __( 'Weekly check (Pro)', 'corea11y' ), array( __CLASS__, 'intro_monitor' ), CoreA11y_Settings::PAGE );
		add_settings_field( 'corea11y_pro_weekly', __( 'Weekly email', 'corea11y' ), array( __CLASS__, 'field_weekly' ), CoreA11y_Settings::PAGE, 'corea11y_pro_monitor', array( 'label_for' => 'corea11y-pro-weekly' ) );
		add_settings_field( 'corea11y_pro_report_to', __( 'Send it to', 'corea11y' ), array( __CLASS__, 'field_report_to' ), CoreA11y_Settings::PAGE, 'corea11y_pro_monitor', array( 'label_for' => 'corea11y-pro-report-to' ) );

		add_settings_section( 'corea11y_pro_brand', __( 'White-label PDF reports (Agency)', 'corea11y' ), array( __CLASS__, 'intro_brand' ), CoreA11y_Settings::PAGE );
		if ( CoreA11y_Pro_License::has( CoreA11y_Pro_License::AGENCY ) ) {
			add_settings_field( 'corea11y_pro_brand_name', __( 'Your company name', 'corea11y' ), array( __CLASS__, 'field_brand_name' ), CoreA11y_Settings::PAGE, 'corea11y_pro_brand', array( 'label_for' => 'corea11y-pro-brand-name' ) );
			add_settings_field( 'corea11y_pro_brand_logo', __( 'Your logo', 'corea11y' ), array( __CLASS__, 'field_brand_logo' ), CoreA11y_Settings::PAGE, 'corea11y_pro_brand' );
			add_settings_field( 'corea11y_pro_brand_color', __( 'Accent color', 'corea11y' ), array( __CLASS__, 'field_brand_color' ), CoreA11y_Settings::PAGE, 'corea11y_pro_brand', array( 'label_for' => 'corea11y-pro-brand-color' ) );
			add_settings_field( 'corea11y_pro_brand_foot', __( 'Footer line', 'corea11y' ), array( __CLASS__, 'field_brand_foot' ), CoreA11y_Settings::PAGE, 'corea11y_pro_brand', array( 'label_for' => 'corea11y-pro-brand-foot' ) );
		}
	}

	/**
	 * Sanitize.
	 *
	 * @param mixed $input Input.
	 * @return array
	 */
	public static function sanitize( $input ) {
		$input = is_array( $input ) ? $input : array();
		$old   = self::get();
		$out   = $old;

		$out['weekly'] = ! empty( $input['weekly'] );
		if ( isset( $input['report_to'] ) ) {
			$emails           = array_filter( array_map( 'sanitize_email', array_map( 'trim', explode( ',', (string) $input['report_to'] ) ) ), 'is_email' );
			$out['report_to'] = implode( ', ', array_slice( array_unique( $emails ), 0, 10 ) );
		}

		// Brand fields only change on the Agency plan (otherwise they aren't shown).
		if ( CoreA11y_Pro_License::has( CoreA11y_Pro_License::AGENCY ) ) {
			$out['brand_name']  = isset( $input['brand_name'] ) ? sanitize_text_field( $input['brand_name'] ) : '';
			$logo               = isset( $input['brand_logo'] ) ? absint( $input['brand_logo'] ) : 0;
			$out['brand_logo']  = ( $logo && wp_attachment_is_image( $logo ) ) ? $logo : 0;
			$color              = isset( $input['brand_color'] ) ? sanitize_hex_color( $input['brand_color'] ) : '';
			$out['brand_color'] = $color ? $color : '';
			$out['brand_foot']  = isset( $input['brand_foot'] ) ? sanitize_text_field( $input['brand_foot'] ) : '';
		}
		return $out;
	}

	/**
	 * Reschedule the weekly check after settings change.
	 *
	 * @param mixed $old Old value.
	 * @param mixed $new New value.
	 */
	public static function changed( $old, $new ) {
		CoreA11y_Pro_Monitor::schedule();
	}

	/**
	 * Same as changed() for the first save.
	 *
	 * @param string $name  Option name.
	 * @param mixed  $value Value.
	 */
	public static function added( $name, $value ) {
		CoreA11y_Pro_Monitor::schedule();
	}

	/**
	 * Media picker on the Settings screen (Agency only).
	 *
	 * @param string $hook Admin page hook.
	 */
	public static function assets( $hook ) {
		if ( false === strpos( (string) $hook, 'corea11y-settings' ) || ! CoreA11y_Pro_License::has( CoreA11y_Pro_License::AGENCY ) ) {
			return;
		}
		wp_enqueue_media();
		wp_register_script( 'corea11y-pro-settings', '', array( 'jquery' ), COREA11Y_PRO_VERSION, true );
		wp_enqueue_script( 'corea11y-pro-settings' );
		wp_add_inline_script(
			'corea11y-pro-settings',
			"(function($){var frame;$(document).on('click','#corea11y-pro-logo-pick',function(e){e.preventDefault();if(!frame){frame=wp.media({title:" . wp_json_encode( __( 'Choose your logo', 'corea11y' ) ) . ",library:{type:'image'},multiple:false});frame.on('select',function(){var a=frame.state().get('selection').first().toJSON();$('#corea11y-pro-brand-logo').val(a.id);$('#corea11y-pro-logo-preview').attr('src',(a.sizes&&a.sizes.medium?a.sizes.medium.url:a.url)).attr('alt',a.alt||'').prop('hidden',false);$('#corea11y-pro-logo-remove').prop('hidden',false);});}frame.open();});$(document).on('click','#corea11y-pro-logo-remove',function(e){e.preventDefault();$('#corea11y-pro-brand-logo').val('0');$('#corea11y-pro-logo-preview').prop('hidden',true);$(this).prop('hidden',true);$('#corea11y-pro-logo-pick').trigger('focus');});})(jQuery);"
		);
	}

	/**
	 * Section intro: weekly check.
	 */
	public static function intro_monitor() {
		echo '<p>' . esc_html__( 'Once a week, CoreA11y checks what changed on your site since the last audit (new or edited pages, images without alt text) and emails you a short summary. The check runs on your own server; nothing is sent anywhere except the email itself.', 'corea11y' ) . '</p>';
		if ( ! CoreA11y_Pro_License::licensed() ) {
			echo '<p class="corea11y-fix-lock">' . esc_html__( 'Activate your CoreA11y Pro license to use the weekly check.', 'corea11y' ) . '</p>';
		}
	}

	/**
	 * Section intro: brand.
	 */
	public static function intro_brand() {
		if ( CoreA11y_Pro_License::has( CoreA11y_Pro_License::AGENCY ) ) {
			echo '<p>' . esc_html__( 'Put your own name and logo on PDF reports you send to clients, instead of CoreA11y.', 'corea11y' ) . '</p>';
		} else {
			echo '<p class="corea11y-fix-lock">' . esc_html__( 'Available on the Agency plan: put your own name and logo on PDF reports you send to clients.', 'corea11y' ) . '</p>';
		}
	}

	/**
	 * Field: weekly on/off.
	 */
	public static function field_weekly() {
		$on = (bool) self::get( 'weekly' );
		printf(
			'<label><input type="checkbox" id="corea11y-pro-weekly" name="%1$s[weekly]" value="1" %2$s %3$s> %4$s</label>',
			esc_attr( self::OPTION ),
			checked( $on, true, false ),
			disabled( ! CoreA11y_Pro_License::licensed(), true, false ),
			esc_html__( 'Email me a weekly summary', 'corea11y' )
		);
		$next = wp_next_scheduled( CoreA11y_Pro_Monitor::HOOK );
		if ( $next ) {
			/* translators: %s: date and time of the next weekly check. */
			echo '<p class="description">' . esc_html( sprintf( __( 'Next check: %s', 'corea11y' ), wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $next ) ) ) . '</p>';
		}
	}

	/**
	 * Field: recipients.
	 */
	public static function field_report_to() {
		printf(
			'<input type="text" class="regular-text" id="corea11y-pro-report-to" name="%1$s[report_to]" value="%2$s" placeholder="%3$s" aria-describedby="corea11y-pro-report-to-desc" autocomplete="email">',
			esc_attr( self::OPTION ),
			esc_attr( self::get( 'report_to' ) ),
			esc_attr( get_option( 'admin_email' ) )
		);
		echo '<p class="description" id="corea11y-pro-report-to-desc">' . esc_html__( 'Separate several addresses with commas. Leave empty to use the site admin email.', 'corea11y' ) . '</p>';
	}

	/**
	 * Field: brand name.
	 */
	public static function field_brand_name() {
		printf(
			'<input type="text" class="regular-text" id="corea11y-pro-brand-name" name="%1$s[brand_name]" value="%2$s" autocomplete="organization">',
			esc_attr( self::OPTION ),
			esc_attr( self::get( 'brand_name' ) )
		);
		echo '<p class="description">' . esc_html__( 'Shown at the top of PDF reports. Leave empty to keep CoreA11y branding.', 'corea11y' ) . '</p>';
	}

	/**
	 * Field: logo.
	 */
	public static function field_brand_logo() {
		$id  = (int) self::get( 'brand_logo' );
		$src = $id ? wp_get_attachment_image_url( $id, 'medium' ) : '';
		printf( '<input type="hidden" id="corea11y-pro-brand-logo" name="%1$s[brand_logo]" value="%2$d">', esc_attr( self::OPTION ), (int) $id );
		printf(
			'<p><img id="corea11y-pro-logo-preview" src="%1$s" alt="%2$s" style="max-height:60px;max-width:240px" %3$s></p>',
			esc_url( $src ? $src : '' ),
			esc_attr( $id ? get_post_meta( $id, '_wp_attachment_image_alt', true ) : '' ),
			$src ? '' : 'hidden'
		);
		echo '<p><button type="button" class="button" id="corea11y-pro-logo-pick">' . esc_html__( 'Choose logo', 'corea11y' ) . '</button> ';
		echo '<button type="button" class="button-link" id="corea11y-pro-logo-remove" ' . ( $src ? '' : 'hidden' ) . '>' . esc_html__( 'Remove logo', 'corea11y' ) . '</button></p>';
		echo '<p class="description">' . esc_html__( 'Give the logo alt text in the Media Library (usually your company name). It is used in the PDF.', 'corea11y' ) . '</p>';
	}

	/**
	 * Field: accent color.
	 */
	public static function field_brand_color() {
		printf(
			'<input type="text" class="small-text" id="corea11y-pro-brand-color" name="%1$s[brand_color]" value="%2$s" placeholder="#3546d1" pattern="#[0-9a-fA-F]{3}([0-9a-fA-F]{3})?" aria-describedby="corea11y-pro-brand-color-desc">',
			esc_attr( self::OPTION ),
			esc_attr( self::get( 'brand_color' ) )
		);
		echo '<p class="description" id="corea11y-pro-brand-color-desc">' . esc_html__( 'A hex color such as #1a7f5a, used for headings and bars. Pick a dark color so text on it stays readable.', 'corea11y' ) . '</p>';
	}

	/**
	 * Field: footer line.
	 */
	public static function field_brand_foot() {
		printf(
			'<input type="text" class="large-text" id="corea11y-pro-brand-foot" name="%1$s[brand_foot]" value="%2$s" aria-describedby="corea11y-pro-brand-foot-desc">',
			esc_attr( self::OPTION ),
			esc_attr( self::get( 'brand_foot' ) )
		);
		echo '<p class="description" id="corea11y-pro-brand-foot-desc">' . esc_html__( 'For example your website or phone number. The note that automated results do not certify WCAG conformance is always kept.', 'corea11y' ) . '</p>';
	}
}
