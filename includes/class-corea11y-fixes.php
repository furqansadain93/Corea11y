<?php
/**
 * Quick fixes: safe, reversible site-wide fixes with one correct answer.
 *
 * Fixes never edit theme or content files. Each one hooks into WordPress output
 * (or adds a small front-end script where a theme hard-codes markup), so turning
 * a fix off restores the site exactly as it was.
 *
 * @package CoreA11y
 */

defined( 'ABSPATH' ) || exit;

/**
 * Registry, settings and front-end application of quick fixes.
 */
class CoreA11y_Fixes {

	const OPTION     = 'corea11y_fixes';
	const LOG_OPTION = 'corea11y_fix_log';
	const LOG_MAX    = 200;

	/**
	 * Available fixes.
	 *
	 * @return array<string,array{title:string,desc:string,wcag:string,rules:array}>
	 */
	public static function all() {
		$fixes = array(
			'skip-link'   => array(
				'title' => __( 'Add a "Skip to content" link', 'corea11y' ),
				'desc'  => __( 'Adds a link that appears when keyboard users press Tab, letting them jump past the menu straight to the main content. It is skipped if your theme already has one.', 'corea11y' ),
				'wcag'  => '2.4.1',
				'rules' => array( 'bypass' ),
			),
			'focus'       => array(
				'title' => __( 'Show a clear keyboard focus outline', 'corea11y' ),
				'desc'  => __( 'Draws a high-contrast outline around links, buttons and fields when they are reached with the keyboard, even if your theme removed it. Mouse clicks are not affected.', 'corea11y' ),
				'wcag'  => '2.4.7',
				'rules' => array(),
			),
			'lang'        => array(
				'title' => __( 'Add the page language', 'corea11y' ),
				'desc'  => __( 'Tells screen readers which language your pages are in, using the language from Settings > General, so text is pronounced correctly.', 'corea11y' ),
				'wcag'  => '3.1.1',
				'rules' => array( 'html-has-lang' ),
			),
			'zoom'        => array(
				'title' => __( 'Allow pinch-zoom on phones', 'corea11y' ),
				'desc'  => __( 'Removes the setting some themes use to block zooming, so visitors who need bigger text can zoom in on mobile.', 'corea11y' ),
				'wcag'  => '1.4.4',
				'rules' => array( 'meta-viewport', 'meta-viewport-large' ),
			),
			'form-labels' => array(
				'title' => __( 'Label search and comment fields', 'corea11y' ),
				'desc'  => __( 'Gives WordPress search boxes and comment form fields a name screen readers can announce, when your theme leaves it out.', 'corea11y' ),
				'wcag'  => '1.3.1, 4.1.2',
				'rules' => array( 'label' ),
			),
			'title'       => array(
				'title' => __( 'Add a missing page title', 'corea11y' ),
				'desc'  => __( 'Gives every page a title (shown in the browser tab and read first by screen readers) when your theme does not output one.', 'corea11y' ),
				'wcag'  => '2.4.2',
				'rules' => array( 'document-title' ),
			),
		);

		/**
		 * Filter the quick fixes. CoreA11y Pro adds its own here.
		 *
		 * @param array $fixes Fixes keyed by id.
		 */
		return apply_filters( 'corea11y_quick_fixes', $fixes );
	}

	/**
	 * Saved state for every fix.
	 *
	 * @return array<string,bool>
	 */
	public static function state() {
		$saved = get_option( self::OPTION, array() );
		$saved = is_array( $saved ) ? $saved : array();
		$out   = array();
		foreach ( array_keys( self::all() ) as $id ) {
			$out[ $id ] = ! empty( $saved[ $id ] );
		}
		return $out;
	}

	/**
	 * Whether a fix is on.
	 *
	 * @param string $id Fix id.
	 * @return bool
	 */
	public static function is_on( $id ) {
		$state = self::state();
		return ! empty( $state[ $id ] );
	}

	/**
	 * Fix id that resolves a rule, if any.
	 *
	 * @param string $rule_id Rule id.
	 * @return string
	 */
	public static function for_rule( $rule_id ) {
		foreach ( self::all() as $id => $fix ) {
			if ( in_array( $rule_id, $fix['rules'], true ) ) {
				return $id;
			}
		}
		return '';
	}

	/**
	 * Save a new state and log each change. All changes from one save share a batch id,
	 * so they can be undone together.
	 *
	 * @param array<string,bool> $new  New state (missing ids are treated as off).
	 * @param string             $kind 'change' or 'undo'.
	 * @return string Batch id, or '' if nothing changed.
	 */
	public static function save( array $new, $kind = 'change' ) {
		$old   = self::state();
		$batch = '';
		$log   = self::log();
		$state = array();
		foreach ( $old as $id => $was ) {
			$now          = ! empty( $new[ $id ] );
			$state[ $id ] = $now;
			if ( $now !== $was ) {
				if ( '' === $batch ) {
					$batch = wp_generate_uuid4();
				}
				array_unshift(
					$log,
					array(
						'id'    => wp_generate_uuid4(),
						'batch' => $batch,
						'fix'   => $id,
						'on'    => $now,
						'kind'  => $kind,
						'time'  => time(),
						'user'  => get_current_user_id(),
					)
				);
			}
		}
		update_option( self::OPTION, $state );
		update_option( self::LOG_OPTION, array_slice( $log, 0, self::LOG_MAX ), false );

		if ( '' !== $batch ) {
			/**
			 * Fires after quick fixes are turned on or off (including undo and redo).
			 * CoreA11y clears known page caches here so visitors see the change at once.
			 *
			 * @param array<string,bool> $state New state.
			 * @param string             $batch Batch id.
			 */
			do_action( 'corea11y_fixes_changed', $state, $batch );
		}
		return $batch;
	}

	/**
	 * Turn one fix on or off, leaving the others unchanged.
	 *
	 * @param string $id   Fix id.
	 * @param bool   $on   New state.
	 * @param string $kind 'change' or 'undo'.
	 * @return string Batch id, or '' if nothing changed.
	 */
	public static function set( $id, $on, $kind = 'change' ) {
		$state = self::state();
		if ( ! array_key_exists( $id, $state ) ) {
			return '';
		}
		$state[ $id ] = (bool) $on;
		return self::save( $state, $kind );
	}

	/**
	 * Undo one logged change (or every change in a batch) by restoring the earlier state.
	 * Changes whose fix has since been changed again are skipped.
	 *
	 * @param string $entry_id Log entry id, or '' to use $batch.
	 * @param string $batch    Batch id.
	 * @return string Batch id of the undo, or '' if nothing could be undone.
	 */
	public static function undo( $entry_id, $batch = '' ) {
		$state = self::state();
		$found = false;
		foreach ( self::log() as $entry ) {
			$match = $entry_id ? ( isset( $entry['id'] ) && $entry['id'] === $entry_id ) : ( $batch && isset( $entry['batch'] ) && $entry['batch'] === $batch );
			if ( ! $match || ! array_key_exists( $entry['fix'], $state ) ) {
				continue;
			}
			if ( $state[ $entry['fix'] ] === (bool) $entry['on'] ) {
				$state[ $entry['fix'] ] = ! $entry['on'];
				$found                  = true;
			}
		}
		return $found ? self::save( $state, 'undo' ) : '';
	}

	/**
	 * Whether a logged change can still be undone (the fix is still in that state).
	 *
	 * @param array $entry Log entry.
	 * @return bool
	 */
	public static function can_undo( array $entry ) {
		if ( empty( $entry['id'] ) ) {
			return false;
		}
		$state = self::state();
		return array_key_exists( $entry['fix'], $state ) && $state[ $entry['fix'] ] === (bool) $entry['on'];
	}

	/**
	 * Log entries of a batch.
	 *
	 * @param string $batch Batch id.
	 * @return array
	 */
	public static function batch_entries( $batch ) {
		return array_values(
			array_filter(
				self::log(),
				static function ( $e ) use ( $batch ) {
					return isset( $e['batch'] ) && $e['batch'] === $batch;
				}
			)
		);
	}

	/**
	 * Change history, newest first.
	 *
	 * @return array
	 */
	public static function log() {
		$log = get_option( self::LOG_OPTION, array() );
		return is_array( $log ) ? $log : array();
	}

	/**
	 * Hook enabled fixes into the front end.
	 */
	public static function register() {
		add_action( 'init', array( __CLASS__, 'setup' ) );
	}

	/**
	 * Attach enabled fixes once WordPress (and translations) are ready.
	 */
	public static function setup() {
		if ( is_admin() ) {
			return;
		}
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue' ) );

		if ( self::is_on( 'lang' ) ) {
			add_filter( 'language_attributes', array( __CLASS__, 'filter_language_attributes' ), 99 );
		}
		if ( self::is_on( 'form-labels' ) ) {
			add_filter( 'get_search_form', array( __CLASS__, 'label_search_form' ), 99 );
			add_filter( 'render_block_core/search', array( __CLASS__, 'label_search_form' ), 99 );
			add_filter( 'comment_form_field_comment', array( __CLASS__, 'label_comment_field' ), 99 );
			add_filter( 'comment_form_field_author', array( __CLASS__, 'label_comment_field' ), 99 );
			add_filter( 'comment_form_field_email', array( __CLASS__, 'label_comment_field' ), 99 );
			add_filter( 'comment_form_field_url', array( __CLASS__, 'label_comment_field' ), 99 );
		}
	}

	/**
	 * Enqueue the small front-end script and styles needed by enabled fixes.
	 */
	public static function enqueue() {
		$state = self::state();
		if ( ! in_array( true, $state, true ) ) {
			return;
		}

		$css = '';
		if ( $state['skip-link'] ) {
			$css .= '.corea11y-skip-link{position:absolute;left:-9999px;top:auto;width:1px;height:1px;overflow:hidden;z-index:100000}'
				. '.corea11y-skip-link:focus{left:8px;top:8px;width:auto;height:auto;padding:12px 18px;background:#fff;color:#1d2327;font:600 16px/1.2 sans-serif;text-decoration:underline;border:2px solid #1d2327;border-radius:4px}';
		}
		if ( $state['focus'] ) {
			$css .= ':where(a,button,input,select,textarea,summary,[tabindex]:not([tabindex="-1"]),[contenteditable="true"]):focus-visible{outline:3px solid #1d2327!important;outline-offset:2px!important;box-shadow:0 0 0 5px #fff!important}';
		}
		if ( $css ) {
			wp_register_style( 'corea11y-fixes', false, array(), COREA11Y_VERSION );
			wp_enqueue_style( 'corea11y-fixes' );
			wp_add_inline_style( 'corea11y-fixes', $css );
		}

		$js_fixes = array_keys( array_filter( array_intersect_key( $state, array_flip( array( 'skip-link', 'lang', 'zoom', 'form-labels', 'title' ) ) ) ) );
		if ( $js_fixes ) {
			wp_enqueue_script( 'corea11y-fixes', COREA11Y_URL . 'assets/js/fixes.js', array(), COREA11Y_VERSION, array( 'in_footer' => true ) );
			wp_localize_script(
				'corea11y-fixes',
				'corea11yFixes',
				array(
					'fixes'       => $js_fixes,
					'lang'        => get_bloginfo( 'language' ),
					'title'       => wp_get_document_title(),
					'skipText'    => __( 'Skip to content', 'corea11y' ),
					'searchLabel' => __( 'Search', 'corea11y' ),
				)
			);
		}
	}

	/**
	 * Make sure <html> has a lang attribute.
	 *
	 * @param string $output Attributes.
	 * @return string
	 */
	public static function filter_language_attributes( $output ) {
		if ( false === stripos( $output, 'lang=' ) ) {
			$output = trim( $output . ' lang="' . esc_attr( get_bloginfo( 'language' ) ) . '"' );
		}
		return $output;
	}

	/**
	 * Add an accessible name to search inputs that have none.
	 *
	 * @param string $html Form HTML.
	 * @return string
	 */
	public static function label_search_form( $html ) {
		if ( ! is_string( $html ) || '' === $html ) {
			return $html;
		}
		$labelled = array();
		$scan     = new WP_HTML_Tag_Processor( $html );
		while ( $scan->next_tag( 'label' ) ) {
			$for = $scan->get_attribute( 'for' );
			if ( is_string( $for ) && '' !== $for ) {
				$labelled[ $for ] = true;
			}
		}

		$tags = new WP_HTML_Tag_Processor( $html );
		while ( $tags->next_tag( 'input' ) ) {
			$type = strtolower( (string) $tags->get_attribute( 'type' ) );
			$name = (string) $tags->get_attribute( 'name' );
			if ( 'search' !== $type && 's' !== $name ) {
				continue;
			}
			$id = $tags->get_attribute( 'id' );
			if ( ( is_string( $id ) && isset( $labelled[ $id ] ) ) || $tags->get_attribute( 'aria-label' ) || $tags->get_attribute( 'aria-labelledby' ) ) {
				continue;
			}
			$tags->set_attribute( 'aria-label', __( 'Search', 'corea11y' ) );
		}
		return $tags->get_updated_html();
	}

	/**
	 * Add an accessible name to a comment form field when it has no linked label.
	 *
	 * @param string $field Field HTML.
	 * @return string
	 */
	public static function label_comment_field( $field ) {
		if ( ! is_string( $field ) || false !== stripos( $field, '<label' ) ) {
			return $field;
		}
		$names = array(
			'comment' => __( 'Comment', 'corea11y' ),
			'author'  => __( 'Name', 'corea11y' ),
			'email'   => __( 'Email', 'corea11y' ),
			'url'     => __( 'Website', 'corea11y' ),
		);
		$tags  = new WP_HTML_Tag_Processor( $field );
		while ( $tags->next_tag() ) {
			if ( ! in_array( $tags->get_tag(), array( 'INPUT', 'TEXTAREA' ), true ) ) {
				continue;
			}
			$name = (string) $tags->get_attribute( 'name' );
			if ( isset( $names[ $name ] ) && ! $tags->get_attribute( 'aria-label' ) ) {
				$tags->set_attribute( 'aria-label', $names[ $name ] );
			}
		}
		return $tags->get_updated_html();
	}
}
