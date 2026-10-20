<?php
/**
 * Plugin settings.
 *
 * @package CoreA11y
 */

defined( 'ABSPATH' ) || exit;

/**
 * Stores settings in a single option and registers them with the Settings API.
 */
class CoreA11y_Settings {

	const OPTION = 'corea11y_settings';
	const GROUP  = 'corea11y_settings_group';
	const PAGE   = 'corea11y-settings';

	/**
	 * Default values.
	 *
	 * @return array
	 */
	public static function defaults() {
		return array(
			'scan_scope'          => 'sample',
			'custom_urls'         => '',
			'scan_mobile'         => true,
			'extended_checks'     => true,
			'delete_on_uninstall' => true,
		);
	}

	/**
	 * Get all settings merged with defaults, or a single key.
	 *
	 * @param string|null $key Optional key.
	 * @return mixed
	 */
	public static function get( $key = null ) {
		$saved    = get_option( self::OPTION, array() );
		$settings = wp_parse_args( is_array( $saved ) ? $saved : array(), self::defaults() );
		if ( null === $key ) {
			return $settings;
		}
		return isset( $settings[ $key ] ) ? $settings[ $key ] : null;
	}

	/**
	 * Allowed scan scopes and their labels.
	 *
	 * @return array<string,string>
	 */
	public static function scopes() {
		return array(
			'sample' => __( 'One page per template (fastest)', 'corea11y' ),
			'all'    => __( 'All published pages and posts', 'corea11y' ),
			'custom' => __( 'Only the URLs I list', 'corea11y' ),
		);
	}

	/**
	 * Register settings, section and fields.
	 */
	public static function register() {
		register_setting(
			self::GROUP,
			self::OPTION,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( __CLASS__, 'sanitize' ),
				'default'           => self::defaults(),
			)
		);

		add_settings_section( 'corea11y_scan', __( 'Scanning', 'corea11y' ), '__return_false', self::PAGE );
		add_settings_field( 'scan_scope', __( 'Pages to scan', 'corea11y' ), array( __CLASS__, 'field_scope' ), self::PAGE, 'corea11y_scan' );
		add_settings_field(
			'custom_urls',
			__( 'Custom URLs', 'corea11y' ),
			array( __CLASS__, 'field_custom_urls' ),
			self::PAGE,
			'corea11y_scan',
			array( 'label_for' => 'corea11y-custom-urls' )
		);
		add_settings_field( 'scan_mobile', __( 'Screen sizes', 'corea11y' ), array( __CLASS__, 'field_mobile' ), self::PAGE, 'corea11y_scan' );
		add_settings_field( 'extended_checks', __( 'Extended checks', 'corea11y' ), array( __CLASS__, 'field_extended' ), self::PAGE, 'corea11y_scan' );

		add_settings_section( 'corea11y_data', __( 'Data', 'corea11y' ), '__return_false', self::PAGE );
		add_settings_field( 'delete_on_uninstall', __( 'On uninstall', 'corea11y' ), array( __CLASS__, 'field_delete' ), self::PAGE, 'corea11y_data' );
	}

	/**
	 * Sanitize submitted settings.
	 *
	 * @param mixed $input Raw input.
	 * @return array
	 */
	public static function sanitize( $input ) {
		$input = is_array( $input ) ? $input : array();
		$out   = self::defaults();

		$scope             = isset( $input['scan_scope'] ) ? sanitize_key( $input['scan_scope'] ) : 'sample';
		$out['scan_scope'] = array_key_exists( $scope, self::scopes() ) ? $scope : 'sample';

		$urls = array();
		if ( isset( $input['custom_urls'] ) ) {
			$home = wp_parse_url( home_url(), PHP_URL_HOST );
			foreach ( preg_split( '/\r\n|\r|\n/', (string) $input['custom_urls'] ) as $line ) {
				$url = esc_url_raw( trim( $line ) );
				// Only same-site URLs can be scanned (the scanner loads pages in a same-origin frame).
				if ( $url && wp_parse_url( $url, PHP_URL_HOST ) === $home ) {
					$urls[] = $url;
				}
			}
		}
		$out['custom_urls'] = implode( "\n", array_unique( $urls ) );

		$out['scan_mobile']         = ! empty( $input['scan_mobile'] );
		$out['extended_checks']     = ! empty( $input['extended_checks'] );
		$out['delete_on_uninstall'] = ! empty( $input['delete_on_uninstall'] );

		return $out;
	}

	/**
	 * Scope radio buttons.
	 */
	public static function field_scope() {
		$current = self::get( 'scan_scope' );
		echo '<fieldset><legend class="screen-reader-text">' . esc_html__( 'Pages to scan', 'corea11y' ) . '</legend>';
		foreach ( self::scopes() as $value => $label ) {
			printf(
				'<label><input type="radio" name="%1$s[scan_scope]" value="%2$s" %3$s> %4$s</label><br>',
				esc_attr( self::OPTION ),
				esc_attr( $value ),
				checked( $current, $value, false ),
				esc_html( $label )
			);
		}
		echo '</fieldset>';
	}

	/**
	 * Custom URL list.
	 */
	public static function field_custom_urls() {
		printf(
			'<textarea id="corea11y-custom-urls" name="%1$s[custom_urls]" rows="5" class="large-text code" aria-describedby="corea11y-custom-urls-help">%2$s</textarea>',
			esc_attr( self::OPTION ),
			esc_textarea( self::get( 'custom_urls' ) )
		);
		echo '<p class="description" id="corea11y-custom-urls-help">' . esc_html__( 'One URL per line. Used only when "Only the URLs I list" is selected. URLs on other sites are ignored.', 'corea11y' ) . '</p>';
	}

	/**
	 * Phone-width scan checkbox.
	 */
	public static function field_mobile() {
		printf(
			'<label><input type="checkbox" name="%1$s[scan_mobile]" value="1" aria-describedby="corea11y-mobile-help" %2$s> %3$s</label><p class="description" id="corea11y-mobile-help">%4$s</p>',
			esc_attr( self::OPTION ),
			checked( (bool) self::get( 'scan_mobile' ), true, false ),
			esc_html__( 'Also check each page at phone width', 'corea11y' ),
			esc_html__( 'Finds problems in mobile menus and layouts. Audits take about twice as long.', 'corea11y' )
		);
	}

	/**
	 * Extended checks checkbox.
	 */
	public static function field_extended() {
		printf(
			'<label><input type="checkbox" name="%1$s[extended_checks]" value="1" aria-describedby="corea11y-extended-help" %2$s> %3$s</label><p class="description" id="corea11y-extended-help">%4$s</p>',
			esc_attr( self::OPTION ),
			checked( (bool) self::get( 'extended_checks' ), true, false ),
			esc_html__( 'Also run selected rules from the IBM Equal Access engine', 'corea11y' ),
			esc_html__( 'Catches form, table and markup problems the main engine doesn\'t cover. Runs only in your admin during audits; visitors never load it. Adds a second or two per page.', 'corea11y' )
		);
	}

	/**
	 * Delete-on-uninstall checkbox.
	 */
	public static function field_delete() {
		printf(
			'<label><input type="checkbox" name="%1$s[delete_on_uninstall]" value="1" %2$s> %3$s</label>',
			esc_attr( self::OPTION ),
			checked( (bool) self::get( 'delete_on_uninstall' ), true, false ),
			esc_html__( 'Delete all CoreA11y audits and settings when the plugin is deleted', 'corea11y' )
		);
	}
}
