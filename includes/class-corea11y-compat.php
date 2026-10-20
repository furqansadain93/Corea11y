<?php
/**
 * Compatibility with caching and optimization plugins, plus support information.
 *
 * Quick fixes are applied as pages are displayed, so a page cache can keep serving the
 * old version after a fix is turned on or undone. Optimization plugins can also delay
 * or combine scripts, which would stop the small fixes script from running for keyboard
 * and screen reader users. This class handles both.
 *
 * @package CoreA11y
 */

defined( 'ABSPATH' ) || exit;

/**
 * Cache purging, script optimization exclusions and support info.
 */
class CoreA11y_Compat {

	/**
	 * Script handles that must run as soon as the page loads, unchanged.
	 *
	 * @var string[]
	 */
	const SCRIPT_HANDLES = array( 'corea11y-fixes', 'corea11y-highlighter' );

	/**
	 * Patterns other plugins match against script URLs or inline code.
	 *
	 * @var string[]
	 */
	const SCRIPT_PATTERNS = array( 'corea11y/assets/js/', 'corea11yFixes', 'corea11yHighlighter' );

	/**
	 * Hook into WordPress.
	 */
	public static function register() {
		add_action( 'corea11y_fixes_changed', array( __CLASS__, 'purge_caches' ) );
		add_action( 'corea11y_statement_changed', array( __CLASS__, 'purge_caches' ) );

		add_filter( 'script_loader_tag', array( __CLASS__, 'script_attributes' ), 10, 2 );
		add_filter( 'wp_inline_script_attributes', array( __CLASS__, 'inline_script_attributes' ) );

		// WP Rocket.
		add_filter( 'rocket_delay_js_exclusions', array( __CLASS__, 'add_patterns' ) );
		add_filter( 'rocket_exclude_defer_js', array( __CLASS__, 'add_patterns' ) );
		add_filter( 'rocket_exclude_js', array( __CLASS__, 'add_patterns' ) );
		add_filter( 'rocket_defer_inline_exclusions', array( __CLASS__, 'add_patterns' ) );
		// LiteSpeed Cache.
		add_filter( 'litespeed_optm_js_defer_exc', array( __CLASS__, 'add_patterns' ) );
		add_filter( 'litespeed_optimize_js_excludes', array( __CLASS__, 'add_patterns' ) );
		// SiteGround Optimizer (uses script handles).
		add_filter( 'sgo_js_async_exclude', array( __CLASS__, 'add_handles' ) );
		add_filter( 'sgo_javascript_combine_exclude', array( __CLASS__, 'add_handles' ) );
		add_filter( 'sgo_js_minify_exclude', array( __CLASS__, 'add_handles' ) );
		// Autoptimize (comma-separated string).
		add_filter( 'autoptimize_filter_js_exclude', array( __CLASS__, 'autoptimize_exclude' ) );
		// WP-Optimize.
		add_filter( 'wp-optimize-minify-default-exclusions', array( __CLASS__, 'add_patterns' ) );
		// Perfmatters.
		add_filter( 'perfmatters_delay_js_exclusions', array( __CLASS__, 'add_patterns' ) );
	}

	/**
	 * Ask optimizers that read script attributes to leave CoreA11y scripts alone.
	 *
	 * @param string $tag    Script tag HTML.
	 * @param string $handle Script handle.
	 * @return string
	 */
	public static function script_attributes( $tag, $handle ) {
		if ( ! in_array( $handle, self::SCRIPT_HANDLES, true ) || false !== strpos( $tag, 'data-no-optimize' ) ) {
			return $tag;
		}
		return str_replace( '<script ', '<script data-no-optimize="1" data-no-defer="1" data-no-minify="1" data-cfasync="false" ', $tag );
	}

	/**
	 * Same for the small inline settings scripts printed with CoreA11y scripts.
	 *
	 * @param array $attributes Inline script attributes.
	 * @return array
	 */
	public static function inline_script_attributes( $attributes ) {
		$id = isset( $attributes['id'] ) ? (string) $attributes['id'] : '';
		foreach ( self::SCRIPT_HANDLES as $handle ) {
			if ( 0 === strpos( $id, $handle . '-js-' ) ) {
				$attributes['data-no-optimize'] = '1';
				$attributes['data-no-defer']    = '1';
				$attributes['data-cfasync']     = 'false';
				break;
			}
		}
		return $attributes;
	}

	/**
	 * Append CoreA11y patterns to an exclusion list.
	 *
	 * @param mixed $list Existing list.
	 * @return array
	 */
	public static function add_patterns( $list ) {
		return array_values( array_unique( array_merge( (array) $list, self::SCRIPT_PATTERNS ) ) );
	}

	/**
	 * Append CoreA11y script handles to an exclusion list.
	 *
	 * @param mixed $list Existing list.
	 * @return array
	 */
	public static function add_handles( $list ) {
		return array_values( array_unique( array_merge( (array) $list, self::SCRIPT_HANDLES ) ) );
	}

	/**
	 * Autoptimize uses a comma-separated string.
	 *
	 * @param string $list Existing exclusions.
	 * @return string
	 */
	public static function autoptimize_exclude( $list ) {
		$list = trim( (string) $list );
		return ( '' === $list ? '' : rtrim( $list, ', ' ) . ', ' ) . implode( ', ', self::SCRIPT_PATTERNS );
	}

	/**
	 * Page caching plugins CoreA11y knows how to clear, keyed by name.
	 *
	 * @return array<string,bool> Name => whether it is active.
	 */
	public static function caches() {
		$loaded = array(
			'WP Rocket'            => function_exists( 'rocket_clean_domain' ),
			'W3 Total Cache'       => function_exists( 'w3tc_flush_all' ),
			'WP Super Cache'       => function_exists( 'wp_cache_clear_cache' ),
			'LiteSpeed Cache'      => defined( 'LSCWP_V' ),
			'WP Fastest Cache'     => class_exists( 'WpFastestCache' ),
			'SiteGround Optimizer' => function_exists( 'sg_cachepress_purge_cache' ),
			'Cache Enabler'        => class_exists( 'Cache_Enabler' ),
			'Breeze'               => class_exists( 'Breeze_PurgeCache' ),
			'Hummingbird'          => class_exists( 'Hummingbird\\WP_Hummingbird' ),
			'WP-Optimize'          => class_exists( 'WP_Optimize' ),
			'Nginx Helper'         => class_exists( 'Nginx_Helper' ),
			'Kinsta'               => class_exists( 'Kinsta\\Cache' ),
			'WP Engine'            => class_exists( 'WpeCommon' ),
		);
		// Plugin files, so a cache plugin that was just deactivated (but whose drop-in is
		// still loaded) isn't reported as active. Hosting caches have no plugin file.
		$files  = array(
			'WP Rocket'            => 'wp-rocket/wp-rocket.php',
			'W3 Total Cache'       => 'w3-total-cache/w3-total-cache.php',
			'WP Super Cache'       => 'wp-super-cache/wp-cache.php',
			'LiteSpeed Cache'      => 'litespeed-cache/litespeed-cache.php',
			'WP Fastest Cache'     => 'wp-fastest-cache/wpFastestCache.php',
			'SiteGround Optimizer' => 'sg-cachepress/sg-cachepress.php',
			'Cache Enabler'        => 'cache-enabler/cache-enabler.php',
			'Breeze'               => 'breeze/breeze.php',
			'Hummingbird'          => 'hummingbird-performance/wp-hummingbird.php',
			'WP-Optimize'          => 'wp-optimize/wp-optimize.php',
			'Nginx Helper'         => 'nginx-helper/nginx-helper.php',
		);
		$active = (array) get_option( 'active_plugins', array() );
		if ( is_multisite() ) {
			$active = array_merge( $active, array_keys( (array) get_site_option( 'active_sitewide_plugins', array() ) ) );
		}
		foreach ( $files as $name => $file ) {
			if ( $loaded[ $name ] && ! in_array( $file, $active, true ) ) {
				$loaded[ $name ] = false;
			}
		}
		return $loaded;
	}

	/**
	 * Names of active page caches.
	 *
	 * @return string[]
	 */
	public static function active_caches() {
		return array_keys( array_filter( self::caches() ) );
	}

	/**
	 * Clear page caches so visitors see a fix (or an undo) straight away.
	 *
	 * Every call is guarded: nothing runs unless that plugin is active.
	 */
	public static function purge_caches() {
		if ( function_exists( 'rocket_clean_domain' ) ) {
			rocket_clean_domain();
		}
		if ( function_exists( 'w3tc_flush_all' ) ) {
			w3tc_flush_all();
		}
		if ( function_exists( 'wp_cache_clear_cache' ) ) {
			wp_cache_clear_cache();
		}
		if ( function_exists( 'sg_cachepress_purge_cache' ) ) {
			sg_cachepress_purge_cache();
		}
		if ( class_exists( 'WpeCommon' ) ) {
			if ( method_exists( 'WpeCommon', 'purge_memcached' ) ) {
				WpeCommon::purge_memcached();
			}
			if ( method_exists( 'WpeCommon', 'purge_varnish_cache' ) ) {
				WpeCommon::purge_varnish_cache();
			}
		}
		// Plugins that clear their cache through an action. Harmless when not installed.
		// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Other plugins' hooks.
		do_action( 'litespeed_purge_all' );
		do_action( 'wpfc_clear_all_cache' );
		do_action( 'cache_enabler_clear_complete_cache' );
		do_action( 'breeze_clear_all_cache' );
		do_action( 'wphb_clear_page_cache' );
		do_action( 'rt_nginx_helper_purge_all' );
		do_action( 'wpo_cache_flush' );
		// phpcs:enable

		/**
		 * Fires after CoreA11y asks page caches to clear. Hook here to clear other caches
		 * (for example a CDN) when a quick fix changes.
		 */
		do_action( 'corea11y_purge_caches' );
	}

	/**
	 * Plain-text support information. Shown on the Settings screen for the site owner to
	 * copy into a support request. Nothing is sent anywhere.
	 *
	 * @return string
	 */
	public static function support_info() {
		global $wpdb;
		$theme   = wp_get_theme();
		$plugins = array();
		foreach ( (array) get_option( 'active_plugins', array() ) as $file ) {
			$data      = get_file_data( WP_PLUGIN_DIR . '/' . $file, array( 'Name' => 'Plugin Name', 'Version' => 'Version' ) );
			$plugins[] = ( $data['Name'] ? $data['Name'] : $file ) . ' ' . $data['Version'];
		}
		$fixes = array();
		foreach ( CoreA11y_Fixes::state() as $id => $on ) {
			$fixes[] = $id . '=' . ( $on ? 'on' : 'off' );
		}
		$last  = CoreA11y_Audit::latest();
		$lines = array(
			'CoreA11y: ' . COREA11Y_VERSION . ' (DB ' . get_option( CoreA11y_Install::DB_VERSION_OPTION ) . ', axe-core ' . CoreA11y_Audit::axe_version() . ')',
			'WordPress: ' . get_bloginfo( 'version' ) . ( is_multisite() ? ' (multisite)' : '' ),
			'PHP: ' . PHP_VERSION,
			'Database: ' . $wpdb->db_server_info(),
			'Theme: ' . $theme->get( 'Name' ) . ' ' . $theme->get( 'Version' ) . ( wp_is_block_theme() ? ' (block theme)' : '' ),
			'Page caches: ' . ( self::active_caches() ? implode( ', ', self::active_caches() ) : 'none detected' ),
			'Quick fixes: ' . implode( ', ', $fixes ),
			'Scan scope: ' . CoreA11y_Settings::get( 'scan_scope' ) . ', phone width: ' . ( CoreA11y_Settings::get( 'scan_mobile' ) ? 'yes' : 'no' ),
			'Last audit: ' . ( $last ? '#' . $last->id . ' ' . $last->status . ', ' . (int) $last->pages_scanned . '/' . (int) $last->pages_total . ' pages' : 'none' ),
		);
		if ( $last && ! empty( $last->summary['pages'] ) ) {
			foreach ( (array) $last->summary['pages'] as $page ) {
				if ( 'error' === $page['status'] && ! empty( $page['error'] ) ) {
					$lines[] = '  Not scanned: ' . $page['url'] . ' (' . $page['error'] . ')';
				}
			}
		}
		$lines[] = 'Active plugins: ' . implode( '; ', $plugins );
		return implode( "\n", $lines );
	}
}
