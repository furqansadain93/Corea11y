<?php
/**
 * Colour contrast approval queue (CoreA11y Pro).
 *
 * Suggests the closest readable text colour for each low-contrast element found in
 * the latest audit. Nothing changes until you approve a suggestion. Approved colours
 * are applied as pages are shown, only on the page and element where the problem was
 * found, never written into theme or content files, and every change can be undone.
 *
 * @package CoreA11y
 */

defined( 'ABSPATH' ) || exit;

/**
 * Colour contrast approval queue.
 */
class CoreA11y_Pro_Contrast {

	const PAGE    = 'corea11y-contrast';
	const OPTION  = 'corea11y_contrast';
	const LOG     = 'corea11y_contrast_log';
	const LOG_MAX = 30;

	/**
	 * Hooks.
	 */
	public static function register() {
		add_action( 'admin_menu', array( __CLASS__, 'menu' ), 20 );
		add_action( 'admin_post_corea11y_contrast_apply', array( __CLASS__, 'handle_apply' ) );
		add_action( 'admin_post_corea11y_contrast_undo', array( __CLASS__, 'handle_undo' ) );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue' ), 20 );
		add_action( 'corea11y_rule_actions', array( __CLASS__, 'report_link' ), 10, 2 );
	}

	/**
	 * Admin menu entry, right after Alt text.
	 */
	public static function menu() {
		add_submenu_page( CoreA11y_Admin::SLUG, __( 'Colour contrast', 'corea11y' ), __( 'Colour contrast', 'corea11y' ), CoreA11y_Admin::CAP, self::PAGE, array( __CLASS__, 'render' ), 3 );
	}

	/**
	 * Link from the report's contrast problem to this screen.
	 *
	 * @param string $rule  Rule id.
	 * @param object $audit Audit row.
	 */
	public static function report_link( $rule, $audit ) {
		if ( ! in_array( $rule, array( 'color-contrast', 'corea11y-icon-contrast' ), true ) ) {
			return;
		}
		echo '<p class="corea11y-quickfix"><strong>' . esc_html__( 'Suggested colours ready:', 'corea11y' ) . '</strong> <a href="' . esc_url( admin_url( 'admin.php?page=' . self::PAGE ) ) . '">' . esc_html__( 'Review and approve them', 'corea11y' ) . '</a></p>';
	}

	/* Colour maths ------------------------------------------------------- */

	/**
	 * Normalise a hex colour to #rrggbb, or '' if invalid.
	 *
	 * @param string $hex Colour.
	 * @return string
	 */
	public static function hex( $hex ) {
		$hex = strtolower( trim( (string) $hex ) );
		if ( preg_match( '/^#?([0-9a-f])([0-9a-f])([0-9a-f])$/', $hex, $m ) ) {
			return '#' . $m[1] . $m[1] . $m[2] . $m[2] . $m[3] . $m[3];
		}
		if ( preg_match( '/^#?([0-9a-f]{6})$/', $hex, $m ) ) {
			return '#' . $m[1];
		}
		return '';
	}

	/**
	 * Hex to HSL (0-1 each).
	 *
	 * @param string $hex Colour.
	 * @return float[]
	 */
	private static function hsl( $hex ) {
		list( $r, $g, $b ) = array_map(
			static function ( $c ) {
				return hexdec( $c ) / 255;
			},
			str_split( substr( $hex, 1 ), 2 )
		);
		$max = max( $r, $g, $b );
		$min = min( $r, $g, $b );
		$l   = ( $max + $min ) / 2;
		$h   = 0;
		$s   = 0;
		if ( $max !== $min ) {
			$d = $max - $min;
			$s = $l > 0.5 ? $d / ( 2 - $max - $min ) : $d / ( $max + $min );
			if ( $max === $r ) {
				$h = ( $g - $b ) / $d + ( $g < $b ? 6 : 0 );
			} elseif ( $max === $g ) {
				$h = ( $b - $r ) / $d + 2;
			} else {
				$h = ( $r - $g ) / $d + 4;
			}
			$h /= 6;
		}
		return array( $h, $s, $l );
	}

	/**
	 * HSL (0-1 each) to hex.
	 *
	 * @param float $h Hue.
	 * @param float $s Saturation.
	 * @param float $l Lightness.
	 * @return string
	 */
	private static function from_hsl( $h, $s, $l ) {
		if ( 0.0 === (float) $s ) {
			$r = $l;
			$g = $l;
			$b = $l;
		} else {
			$q = $l < 0.5 ? $l * ( 1 + $s ) : $l + $s - $l * $s;
			$p = 2 * $l - $q;
			$r = self::hue( $p, $q, $h + 1 / 3 );
			$g = self::hue( $p, $q, $h );
			$b = self::hue( $p, $q, $h - 1 / 3 );
		}
		return sprintf( '#%02x%02x%02x', (int) round( $r * 255 ), (int) round( $g * 255 ), (int) round( $b * 255 ) );
	}

	/**
	 * HSL helper.
	 *
	 * @param float $p P.
	 * @param float $q Q.
	 * @param float $t T.
	 * @return float
	 */
	private static function hue( $p, $q, $t ) {
		if ( $t < 0 ) {
			++$t;
		}
		if ( $t > 1 ) {
			--$t;
		}
		if ( $t < 1 / 6 ) {
			return $p + ( $q - $p ) * 6 * $t;
		}
		if ( $t < 1 / 2 ) {
			return $q;
		}
		if ( $t < 2 / 3 ) {
			return $p + ( $q - $p ) * ( 2 / 3 - $t ) * 6;
		}
		return $p;
	}

	/**
	 * The closest colour to $fg (same hue, only lighter or darker) that reaches the
	 * required contrast on $bg.
	 *
	 * @param string $fg   Text colour.
	 * @param string $bg   Background colour.
	 * @param float  $need Required ratio, e.g. 4.5.
	 * @return string
	 */
	public static function suggest( $fg, $bg, $need ) {
		$target          = $need + 0.08; // A small margin, so rounding never tips it back under.
		list( $h, $s, $l ) = self::hsl( $fg );
		$best            = '';
		$best_dist       = 2;
		foreach ( array( 0.0, 1.0 ) as $end ) {
			$edge = self::from_hsl( $h, $s, $end );
			if ( CoreA11y_UI::contrast( $edge, $bg ) < $target ) {
				continue;
			}
			$lo = $l;
			$hi = $end;
			for ( $i = 0; $i < 24; $i++ ) {
				$mid = ( $lo + $hi ) / 2;
				if ( CoreA11y_UI::contrast( self::from_hsl( $h, $s, $mid ), $bg ) >= $target ) {
					$hi = $mid;
				} else {
					$lo = $mid;
				}
			}
			$dist = abs( $hi - $l );
			if ( $dist < $best_dist ) {
				$best_dist = $dist;
				$best      = self::from_hsl( $h, $s, $hi );
			}
		}
		if ( '' === $best ) {
			$best = CoreA11y_UI::contrast( '#000000', $bg ) >= CoreA11y_UI::contrast( '#ffffff', $bg ) ? '#000000' : '#ffffff';
		}
		return $best;
	}

	/* Data ----------------------------------------------------------------- */

	/**
	 * Path of a URL, normalised for matching ("/" for the home page).
	 *
	 * @param string $url URL or path.
	 * @return string
	 */
	public static function path( $url ) {
		$path = (string) wp_parse_url( $url, PHP_URL_PATH );
		return '/' . trim( $path, '/' );
	}

	/**
	 * Visible text of an element snippet, to recognise it on the page.
	 *
	 * @param string $snippet HTML.
	 * @return string
	 */
	private static function text( $snippet ) {
		$t = trim( preg_replace( '/\s+/u', ' ', html_entity_decode( wp_strip_all_tags( (string) $snippet ), ENT_QUOTES, 'UTF-8' ) ) );
		return function_exists( 'mb_substr' ) ? mb_substr( $t, 0, 60 ) : substr( $t, 0, 60 );
	}

	/**
	 * Approved colour changes, keyed by issue fingerprint.
	 *
	 * @return array
	 */
	public static function applied() {
		$a = get_option( self::OPTION, array() );
		return is_array( $a ) ? $a : array();
	}

	/**
	 * Change history (newest first).
	 *
	 * @return array
	 */
	public static function log() {
		$l = get_option( self::LOG, array() );
		return is_array( $l ) ? $l : array();
	}

	/**
	 * Suggestions from the latest audit, grouped by colour pair.
	 *
	 * @return array{audit:?object,groups:array,skipped:int}
	 */
	public static function suggestions() {
		global $wpdb;
		$out    = array(
			'audit'   => null,
			'groups'  => array(),
			'skipped' => 0,
		);
		$latest = CoreA11y_Admin::recent_audits( 1, 'complete' );
		if ( ! $latest ) {
			return $out;
		}
		$out['audit'] = $latest[0];
		$applied      = self::applied();
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom table.
		$rows = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM %i WHERE audit_id = %d AND rule_id IN ( 'color-contrast', 'corea11y-icon-contrast' ) AND status <> 'dismissed' ORDER BY id", CoreA11y_Install::tables()['issues'], (int) $latest[0]->id ) );
		foreach ( (array) $rows as $r ) {
			if ( isset( $applied[ $r->fingerprint ] ) ) {
				continue;
			}
			if ( ! preg_match( '/foreground color: (#[0-9a-f]{3,6}), background color: (#[0-9a-f]{3,6}).*?Expected contrast ratio of ([\d.]+):1/is', (string) $r->detail, $m ) ) {
				++$out['skipped'];
				continue;
			}
			$fg   = self::hex( $m[1] );
			$bg   = self::hex( $m[2] );
			$need = (float) $m[3];
			if ( ! $fg || ! $bg || $need <= 0 ) {
				++$out['skipped'];
				continue;
			}
			$key = md5( $fg . '|' . $bg . '|' . $need );
			if ( ! isset( $out['groups'][ $key ] ) ) {
				$to                    = self::suggest( $fg, $bg, $need );
				$out['groups'][ $key ] = array(
					'key'    => $key,
					'fg'     => $fg,
					'bg'     => $bg,
					'need'   => $need,
					'before' => CoreA11y_UI::contrast( $fg, $bg ),
					'to'     => $to,
					'after'  => CoreA11y_UI::contrast( $to, $bg ),
					'items'  => array(),
				);
			}
			$out['groups'][ $key ]['items'][] = array(
				'fp'       => $r->fingerprint,
				'path'     => self::path( $r->page_url ),
				'url'      => $r->page_url,
				'label'    => $r->page_label,
				'selector' => $r->selector,
				'text'     => self::text( $r->snippet ),
			);
		}
		uasort(
			$out['groups'],
			static function ( $a, $b ) {
				return count( $b['items'] ) <=> count( $a['items'] );
			}
		);
		return $out;
	}

	/**
	 * Approve colour changes.
	 *
	 * @param array $groups Groups from suggestions(), keyed by key.
	 * @param array $chosen Chosen colour per group key.
	 * @return array{batch:string,count:int,pages:int,adjusted:int}
	 */
	public static function apply( array $groups, array $chosen ) {
		$applied  = self::applied();
		$batch    = wp_generate_uuid4();
		$fps      = array();
		$pages    = array();
		$adjusted = 0;
		foreach ( $chosen as $key => $color ) {
			if ( empty( $groups[ $key ] ) ) {
				continue;
			}
			$g     = $groups[ $key ];
			$color = self::hex( $color );
			// Only colours that really pass are accepted; otherwise use the suggestion.
			if ( ! $color || CoreA11y_UI::contrast( $color, $g['bg'] ) < $g['need'] ) {
				if ( $color ) {
					++$adjusted;
				}
				$color = $g['to'];
			}
			foreach ( $g['items'] as $item ) {
				$applied[ $item['fp'] ] = array(
					'path'     => $item['path'],
					'selector' => $item['selector'],
					'text'     => $item['text'],
					'color'    => $color,
					'from'     => $g['fg'],
					'bg'       => $g['bg'],
					'label'    => $item['label'],
					'batch'    => $batch,
				);
				$fps[]                    = $item['fp'];
				$pages[ $item['path'] ] = true;
			}
		}
		if ( ! $fps ) {
			return array(
				'batch'    => '',
				'count'    => 0,
				'pages'    => 0,
				'adjusted' => 0,
			);
		}
		update_option( self::OPTION, $applied );
		$log = self::log();
		array_unshift(
			$log,
			array(
				'batch' => $batch,
				'time'  => time(),
				'user'  => get_current_user_id(),
				'fps'   => $fps,
				'pages' => count( $pages ),
				'kind'  => 'change',
			)
		);
		update_option( self::LOG, array_slice( $log, 0, self::LOG_MAX ), false );
		/** This action is documented in includes/class-corea11y-fixes.php */
		do_action( 'corea11y_fixes_changed', CoreA11y_Fixes::state(), $batch );
		return array(
			'batch'    => $batch,
			'count'    => count( $fps ),
			'pages'    => count( $pages ),
			'adjusted' => $adjusted,
		);
	}

	/**
	 * Undo one approval batch, or everything ('all').
	 *
	 * @param string $batch Batch id or 'all'.
	 * @return int Number of colour changes removed.
	 */
	public static function undo( $batch ) {
		$applied = self::applied();
		$removed = 0;
		foreach ( $applied as $fp => $a ) {
			if ( 'all' === $batch || $a['batch'] === $batch ) {
				unset( $applied[ $fp ] );
				++$removed;
			}
		}
		if ( $removed ) {
			update_option( self::OPTION, $applied );
			$log = self::log();
			foreach ( $log as $i => $e ) {
				if ( 'all' === $batch || $e['batch'] === $batch ) {
					$log[ $i ]['undone'] = time();
				}
			}
			update_option( self::LOG, $log, false );
			/** This action is documented in includes/class-corea11y-fixes.php */
			do_action( 'corea11y_fixes_changed', CoreA11y_Fixes::state(), 'contrast-undo' );
		}
		return $removed;
	}

	/* Front end -------------------------------------------------------------- */

	/**
	 * Apply approved colours on the page being shown (only rules for this page).
	 */
	public static function enqueue() {
		if ( is_admin() ) {
			return;
		}
		$applied = self::applied();
		if ( ! $applied ) {
			return;
		}
		$uri  = isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '/';
		$here = self::path( $uri );
		$out  = array();
		foreach ( $applied as $a ) {
			if ( $a['path'] === $here ) {
				$out[] = array(
					's' => $a['selector'],
					't' => $a['text'],
					'c' => $a['color'],
				);
			}
		}
		if ( ! $out ) {
			return;
		}
		wp_register_script( 'corea11y-contrast', false, array(), COREA11Y_PRO_VERSION, true );
		wp_enqueue_script( 'corea11y-contrast' );
		wp_add_inline_script(
			'corea11y-contrast',
			'(function(r){function n(s){return(s||"").replace(/\s+/g," ").trim()}function run(){r.forEach(function(x){var l;try{l=document.querySelectorAll(x.s)}catch(e){return}l.forEach(function(el){var t=n(el.textContent);if(x.t&&t.indexOf(x.t.slice(0,40))!==0)return;el.style.setProperty("color",x.c,"important")})})}if(document.readyState==="loading"){document.addEventListener("DOMContentLoaded",run)}else{run()}})(' . wp_json_encode( $out ) . ');'
		);
	}

	/* Admin ------------------------------------------------------------------- */

	/**
	 * Screen.
	 */
	public static function render() {
		$data     = self::suggestions();
		$licensed = CoreA11y_Pro_License::licensed();
		$applied  = self::applied();
		$log      = self::log();
		include COREA11Y_PRO_DIR . 'views/contrast.php';
	}

	/**
	 * Approve selected suggestions.
	 */
	public static function handle_apply() {
		if ( ! current_user_can( CoreA11y_Admin::CAP ) ) {
			wp_die( esc_html__( 'You do not have permission to do this.', 'corea11y' ), 403 );
		}
		check_admin_referer( 'corea11y_contrast_apply' );
		$args = array( 'page' => self::PAGE );
		if ( ! CoreA11y_Pro_License::licensed() ) {
			$args['locked'] = '1';
		} else {
			$picked = isset( $_POST['approve'] ) && is_array( $_POST['approve'] ) ? array_map( 'sanitize_key', array_keys( wp_unslash( $_POST['approve'] ) ) ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Keys sanitized with sanitize_key.
			$colors = isset( $_POST['color'] ) && is_array( $_POST['color'] ) ? map_deep( wp_unslash( $_POST['color'] ), 'sanitize_text_field' ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Sanitized with map_deep.
			$chosen = array();
			foreach ( $picked as $key ) {
				$chosen[ $key ] = isset( $colors[ $key ] ) ? (string) $colors[ $key ] : '';
			}
			$res = self::apply( self::suggestions()['groups'], $chosen );
			if ( $res['count'] ) {
				$args['applied']  = $res['count'];
				$args['pages']    = $res['pages'];
				$args['adjusted'] = $res['adjusted'];
				$args['batch']    = $res['batch'];
			} else {
				$args['none'] = '1';
			}
		}
		wp_safe_redirect( add_query_arg( $args, admin_url( 'admin.php' ) ) );
		exit;
	}

	/**
	 * Undo an approval.
	 */
	public static function handle_undo() {
		if ( ! current_user_can( CoreA11y_Admin::CAP ) ) {
			wp_die( esc_html__( 'You do not have permission to do this.', 'corea11y' ), 403 );
		}
		check_admin_referer( 'corea11y_contrast_undo' );
		$batch = isset( $_POST['batch'] ) ? sanitize_text_field( wp_unslash( $_POST['batch'] ) ) : '';
		$n     = $batch ? self::undo( $batch ) : 0;
		wp_safe_redirect(
			add_query_arg(
				array(
					'page'   => self::PAGE,
					'undone' => $n,
				),
				admin_url( 'admin.php' )
			)
		);
		exit;
	}
}
