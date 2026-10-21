<?php
/**
 * CoreA11y Pro quick fixes.
 *
 * Registered through Lite's `corea11y_quick_fixes` filter, so they share Lite's
 * settings, change history, undo, cache clearing and "fixes N issues" badges.
 *
 * @package CoreA11y
 */

defined( 'ABSPATH' ) || exit;

/**
 * Pro fixes: definitions, server-side application and the front-end script.
 */
class CoreA11y_Pro_Fixes {

	const HANDLE = 'corea11y-pro-fixes';

	/**
	 * Fixes applied by the front-end script.
	 *
	 * @var string[]
	 */
	const JS_FIXES = array( 'new-tab', 'file-type', 'link-names', 'button-names', 'link-titles', 'autocomplete', 'placeholder-names', 'main-landmark', 'decorative-svg', 'focus-obscured', 'menu-names', 'header-footer', 'page-heading', 'dropdown-keyboard', 'autoplay', 'merge-links', 'empty-links', 'page-numbers', 'table-headers', 'image-titles', 'form-label-links', 'required-fields', 'form-messages', 'target-size', 'placeholder-contrast' );

	/**
	 * Hook into WordPress and CoreA11y.
	 */
	public static function register() {
		add_filter( 'corea11y_quick_fixes', array( __CLASS__, 'add_fixes' ) );
		add_action( 'init', array( __CLASS__, 'setup' ), 11 );

		// Keep optimization plugins away from the Pro script, like Lite's.
		add_filter( 'script_loader_tag', array( __CLASS__, 'script_attributes' ), 10, 2 );
		foreach ( array( 'rocket_delay_js_exclusions', 'rocket_exclude_defer_js', 'rocket_exclude_js', 'litespeed_optm_js_defer_exc', 'litespeed_optimize_js_excludes', 'wp-optimize-minify-default-exclusions', 'perfmatters_delay_js_exclusions' ) as $hook ) {
			add_filter( $hook, array( __CLASS__, 'add_patterns' ) );
		}
		foreach ( array( 'sgo_js_async_exclude', 'sgo_javascript_combine_exclude', 'sgo_js_minify_exclude' ) as $hook ) {
			add_filter( $hook, array( __CLASS__, 'add_handle' ) );
		}
	}

	/**
	 * Pro fix definitions.
	 *
	 * @param array $fixes Lite fixes.
	 * @return array
	 */
	public static function add_fixes( $fixes ) {
		$pro = array(
			'media-alt'         => array(
				'title' => __( 'Use Media Library alt text in posts', 'corea11y' ),
				'desc'  => __( 'When an image in a post has no alt text but you have written one in the Media Library, use it. Images you marked decorative are left alone.', 'corea11y' ),
				'wcag'  => '1.1.1',
				'rules' => array( 'image-alt', 'corea11y-content-img-alt' ),
			),
			'link-names'        => array(
				'title' => __( 'Name icon-only links', 'corea11y' ),
				'desc'  => __( 'Gives links with no text a name screen readers can announce, using their tooltip or, for social media and email icons, the service name.', 'corea11y' ),
				'wcag'  => '2.4.4, 4.1.2',
				'rules' => array( 'link-name' ),
			),
			'button-names'      => array(
				'title' => __( 'Name icon-only buttons', 'corea11y' ),
				'desc'  => __( 'Gives buttons with no text a name, using their tooltip or a clear purpose such as "Search", "Menu" or "Close".', 'corea11y' ),
				'wcag'  => '4.1.2',
				'rules' => array( 'button-name' ),
			),
			'new-tab'           => array(
				'title' => __( 'Warn when links open a new tab', 'corea11y' ),
				'desc'  => __( 'Adds "(opens in a new tab)" for screen readers to links that open a new tab or window without saying so. Nothing changes visually.', 'corea11y' ),
				'wcag'  => '3.2.5',
				'rules' => array( 'corea11y-link-new-window' ),
			),
			'file-type'         => array(
				'title' => __( 'Say the file type on file links', 'corea11y' ),
				'desc'  => __( 'Tells screen readers when a link opens a PDF, Word, Excel, PowerPoint or ZIP file. Nothing changes visually.', 'corea11y' ),
				'wcag'  => '2.4.4',
				'rules' => array( 'corea11y-link-file-type' ),
			),
			'read-more'         => array(
				'title' => __( 'Make "Read more" links specific', 'corea11y' ),
				'desc'  => __( 'Adds the post title to "Read more" and "Continue reading" links for screen readers, so each says which post it opens.', 'corea11y' ),
				'wcag'  => '2.4.4',
				'rules' => array( 'corea11y-link-generic' ),
			),
			'link-titles'       => array(
				'title' => __( 'Remove tooltips that repeat link text', 'corea11y' ),
				'desc'  => __( 'Removes link tooltips that only repeat the link text, so screen readers don\'t say it twice.', 'corea11y' ),
				'wcag'  => '',
				'rules' => array( 'corea11y-link-title-repeat' ),
			),
			'autocomplete'      => array(
				'title' => __( 'Help browsers fill in forms', 'corea11y' ),
				'desc'  => __( 'Marks common fields (name, email, phone, address) so browsers and assistive technology can fill them in automatically.', 'corea11y' ),
				'wcag'  => '1.3.5',
				'rules' => array(),
			),
			'placeholder-names' => array(
				'title' => __( 'Keep placeholder text as the field name', 'corea11y' ),
				'desc'  => __( 'For fields that only have placeholder text, keeps that text as the field\'s name for screen readers after someone starts typing. A visible label is still best.', 'corea11y' ),
				'wcag'  => '4.1.2',
				'rules' => array(),
			),
			'main-landmark'     => array(
				'title' => __( 'Mark the main content area', 'corea11y' ),
				'desc'  => __( 'Marks your theme\'s main content area as "main", so screen reader users can jump straight to it.', 'corea11y' ),
				'wcag'  => '1.3.1',
				'rules' => array( 'landmark-one-main' ),
			),
			'decorative-svg'    => array(
				'title' => __( 'Hide decorative icons in links and buttons', 'corea11y' ),
				'desc'  => __( 'Icons inside links and buttons that already have text are hidden from screen readers, so only the text is read.', 'corea11y' ),
				'wcag'  => '1.1.1',
				'rules' => array(),
			),
			'focus-obscured'    => array(
				'title' => __( 'Keep focused items out from under sticky headers', 'corea11y' ),
				'desc'  => __( 'When a sticky header or bar covers the top of the screen, scrolls keyboard focus into view below it.', 'corea11y' ),
				'wcag'  => '2.4.11',
				'rules' => array(),
			),
			'underline-links'   => array(
				'title' => __( 'Underline links in text', 'corea11y' ),
				'desc'  => __( 'Underlines links inside paragraphs and lists, so they can be told apart without relying on color. This changes how your site looks.', 'corea11y' ),
				'wcag'  => '1.4.1',
				'rules' => array( 'link-in-text-block' ),
				'look'  => true,
			),
			'menu-names' => array(
				'title' => __( 'Name your menus', 'corea11y' ),
				'desc'  => __( 'Gives each menu a name screen readers announce, such as "Main menu" or "Footer menu", taken from Appearance > Menus.', 'corea11y' ),
				'wcag'  => '1.3.1, 2.4.1',
				'rules' => array( 'landmark-unique' ),
			),
			'header-footer' => array(
				'title' => __( 'Mark the header and footer', 'corea11y' ),
				'desc'  => __( 'Marks your theme\'s header and footer areas, so screen reader users can jump to them like the main content.', 'corea11y' ),
				'wcag'  => '1.3.1',
				'rules' => array( 'region' ),
			),
			'page-heading' => array(
				'title' => __( 'Add a main heading where a page has none', 'corea11y' ),
				'desc'  => __( 'When a page has no main (H1) heading, adds the page title as one for screen readers. Nothing changes visually.', 'corea11y' ),
				'wcag'  => '1.3.1, 2.4.6',
				'rules' => array( 'page-has-heading-one' ),
			),
			'dropdown-keyboard' => array(
				'title' => __( 'Open dropdown menus with the keyboard', 'corea11y' ),
				'desc'  => __( 'Submenus that only open on mouse hover also open when keyboard users Tab into them, and close with Escape.', 'corea11y' ),
				'wcag'  => '2.1.1',
				'rules' => array(),
			),
			'autoplay' => array(
				'title' => __( 'Stop sound from playing automatically', 'corea11y' ),
				'desc'  => __( 'Videos and audio that would start playing with sound wait until the visitor presses play. Muted background videos are left alone.', 'corea11y' ),
				'wcag'  => '1.4.2',
				'rules' => array( 'no-autoplay-audio' ),
			),
			'merge-links' => array(
				'title' => __( 'Combine duplicate image and text links', 'corea11y' ),
				'desc'  => __( 'When an image and the text next to it link to the same page, keyboard and screen reader users get one link instead of two.', 'corea11y' ),
				'wcag'  => '2.4.4',
				'rules' => array( 'corea11y-link-adjacent-duplicate' ),
			),
			'empty-links' => array(
				'title' => __( 'Skip empty links', 'corea11y' ),
				'desc'  => __( 'Links with nothing inside them are skipped by the keyboard and screen readers, so visitors don\'t land on a blank stop.', 'corea11y' ),
				'wcag'  => '2.4.4',
				'rules' => array(),
			),
			'image-link-names' => array(
				'title' => __( 'Name image links from your own site', 'corea11y' ),
				'desc'  => __( 'When a linked image has no alt text, uses the title of the page or image it links to on your site.', 'corea11y' ),
				'wcag'  => '1.1.1, 2.4.4',
				'rules' => array(),
			),
			'page-numbers' => array(
				'title' => __( 'Say "Page" on page-number links', 'corea11y' ),
				'desc'  => __( 'Page-number links such as "2" are read as "Page 2", and the current page is announced as current.', 'corea11y' ),
				'wcag'  => '2.4.4',
				'rules' => array(),
			),
			'table-headers' => array(
				'title' => __( 'Connect table headers to their cells', 'corea11y' ),
				'desc'  => __( 'Tells screen readers which cells each table header describes, so tables are read row by row correctly.', 'corea11y' ),
				'wcag'  => '1.3.1',
				'rules' => array( 'th-has-data-cells' ),
			),
			'image-titles' => array(
				'title' => __( 'Remove tooltips that repeat alt text', 'corea11y' ),
				'desc'  => __( 'Removes image tooltips that only repeat the alt text, so screen readers don\'t read it twice.', 'corea11y' ),
				'wcag'  => '',
				'rules' => array(),
			),
			'form-label-links' => array(
				'title' => __( 'Connect form labels to their fields', 'corea11y' ),
				'desc'  => __( 'When a form shows a label that isn\'t linked to its field (common with page builders), links them so screen readers read the label.', 'corea11y' ),
				'wcag'  => '1.3.1, 4.1.2',
				'rules' => array( 'label' ),
			),
			'required-fields' => array(
				'title' => __( 'Announce required fields', 'corea11y' ),
				'desc'  => __( 'Fields marked with * or "(required)" are announced as required to screen reader users.', 'corea11y' ),
				'wcag'  => '3.3.2',
				'rules' => array(),
			),
			'form-messages' => array(
				'title' => __( 'Read out form messages', 'corea11y' ),
				'desc'  => __( 'Error and success messages from common form plugins are read out by screen readers when they appear.', 'corea11y' ),
				'wcag'  => '4.1.3',
				'rules' => array(),
			),
			'target-size' => array(
				'title' => __( 'Make small icon links easier to tap', 'corea11y' ),
				'desc'  => __( 'Gives icon-only links and buttons a tap area of at least 24 by 24 pixels. This can change how your site looks.', 'corea11y' ),
				'wcag'  => '2.5.8',
				'rules' => array( 'target-size' ),
				'look'  => true,
			),
			'placeholder-contrast' => array(
				'title' => __( 'Make placeholder text readable', 'corea11y' ),
				'desc'  => __( 'Makes faint placeholder text in form fields dark enough to read (or light enough on dark fields). This changes how your site looks.', 'corea11y' ),
				'wcag'  => '1.4.3',
				'rules' => array(),
				'look'  => true,
			),
		);
		foreach ( $pro as $id => $fix ) {
			$pro[ $id ]['pro'] = true;
		}
		return array_merge( $fixes, $pro );
	}

	/**
	 * Attach the server-side Pro fixes that are on.
	 */
	public static function setup() {
		if ( is_admin() ) {
			return;
		}
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue' ) );
		if ( CoreA11y_Fixes::is_on( 'media-alt' ) ) {
			add_filter( 'wp_content_img_tag', array( __CLASS__, 'media_alt' ), 10, 3 );
		}
		if ( CoreA11y_Fixes::is_on( 'read-more' ) ) {
			add_filter( 'the_content_more_link', array( __CLASS__, 'more_link' ), 99 );
			add_filter( 'render_block_core/read-more', array( __CLASS__, 'read_more_block' ), 99, 3 );
		}
		if ( CoreA11y_Fixes::is_on( 'autoplay' ) ) {
			add_filter( 'the_content', array( __CLASS__, 'stop_autoplay' ), 99 );
			add_filter( 'widget_block_content', array( __CLASS__, 'stop_autoplay' ), 99 );
			add_filter( 'widget_text_content', array( __CLASS__, 'stop_autoplay' ), 99 );
		}
		if ( CoreA11y_Fixes::is_on( 'image-link-names' ) ) {
			add_filter( 'the_content', array( __CLASS__, 'image_link_names' ), 98 );
		}
	}

	/**
	 * Stop media from starting with sound: remove autoplay from unmuted <video> and
	 * <audio>, and turn off autoplay on unmuted YouTube/Vimeo embeds.
	 *
	 * @param string $html HTML.
	 * @return string
	 */
	public static function stop_autoplay( $html ) {
		if ( ! is_string( $html ) || ( false === stripos( $html, 'autoplay' ) ) ) {
			return $html;
		}
		$tags = new WP_HTML_Tag_Processor( $html );
		while ( $tags->next_tag() ) {
			$tag = $tags->get_tag();
			if ( ( 'VIDEO' === $tag || 'AUDIO' === $tag ) && null !== $tags->get_attribute( 'autoplay' ) && null === $tags->get_attribute( 'muted' ) ) {
				$tags->remove_attribute( 'autoplay' );
				if ( null === $tags->get_attribute( 'controls' ) ) {
					$tags->set_attribute( 'controls', true );
				}
			} elseif ( 'IFRAME' === $tag ) {
				$src = (string) $tags->get_attribute( 'src' );
				if ( preg_match( '/[?&]autoplay=1/i', $src ) && ! preg_match( '/[?&](mute|muted|background)=1/i', $src ) ) {
					$tags->set_attribute( 'src', preg_replace( '/([?&])autoplay=1/i', '${1}autoplay=0', $src ) );
				}
			}
		}
		return $tags->get_updated_html();
	}

	/**
	 * Name links that contain only an image with no alt text, from the page or image
	 * they point to on this site. Links to other sites are left alone.
	 *
	 * @param string $html Post content.
	 * @return string
	 */
	public static function image_link_names( $html ) {
		if ( ! is_string( $html ) || false === stripos( $html, '<img' ) ) {
			return $html;
		}
		$lookups = 0;
		return preg_replace_callback(
			'#<a\b([^>]*)>(\s*)(<img\b[^>]*>)(\s*)</a>#i',
			static function ( $m ) use ( &$lookups ) {
				$a   = new WP_HTML_Tag_Processor( '<a' . $m[1] . '>' );
				$img = new WP_HTML_Tag_Processor( $m[3] );
				if ( ! $a->next_tag( 'a' ) || ! $img->next_tag( 'img' ) ) {
					return $m[0];
				}
				$alt = $img->get_attribute( 'alt' );
				if ( $a->get_attribute( 'aria-label' ) || $a->get_attribute( 'aria-labelledby' ) || ( is_string( $alt ) && '' !== trim( $alt ) ) ) {
					return $m[0];
				}
				$href = (string) $a->get_attribute( 'href' );
				if ( $lookups >= 30 || '' === $href ) {
					return $m[0];
				}
				++$lookups;
				$name = self::name_for_url( $href );
				if ( '' === $name ) {
					return $m[0];
				}
				$a->set_attribute( 'aria-label', $name );
				return $a->get_updated_html() . $m[2] . $m[3] . $m[4] . '</a>';
			},
			$html
		);
	}

	/**
	 * Title of the post, page or Media Library image a link on this site points to.
	 *
	 * @param string $href Link target.
	 * @return string
	 */
	public static function name_for_url( $href ) {
		static $cache = array();
		if ( isset( $cache[ $href ] ) ) {
			return $cache[ $href ];
		}
		$name = '';
		$home = wp_parse_url( home_url(), PHP_URL_HOST );
		$host = wp_parse_url( $href, PHP_URL_HOST );
		if ( ! $host || $host === $home ) {
			$url = $host ? $href : home_url( $href );
			if ( preg_match( '/\.(jpe?g|png|gif|webp|avif)(\?.*)?$/i', $url ) ) {
				$id = attachment_url_to_postid( preg_replace( '/-\d+x\d+(?=\.[a-z]+$)/i', '', strtok( $url, '?' ) ) );
				if ( $id ) {
					$name = trim( (string) get_post_meta( $id, '_wp_attachment_image_alt', true ) );
					if ( '' === $name ) {
						$name = trim( wp_strip_all_tags( get_the_title( $id ) ) );
					}
				}
			} else {
				$id = url_to_postid( $url );
				if ( $id ) {
					$name = trim( wp_strip_all_tags( get_the_title( $id ) ) );
				}
			}
		}
		$cache[ $href ] = $name;
		return $name;
	}

	/**
	 * Menu names for "Name your menus": by menu slug and by theme location.
	 *
	 * @return array{slugs:array,locations:array}
	 */
	public static function menu_names() {
		$slugs = array();
		foreach ( wp_get_nav_menus() as $menu ) {
			$slugs[ $menu->slug ] = $menu->name;
		}
		$locations  = array();
		$registered = get_registered_nav_menus();
		foreach ( get_nav_menu_locations() as $location => $menu_id ) {
			$menu = $menu_id ? wp_get_nav_menu_object( $menu_id ) : null;
			if ( $menu ) {
				$locations[ $location ] = $menu->name;
			} elseif ( isset( $registered[ $location ] ) ) {
				$locations[ $location ] = $registered[ $location ];
			}
		}
		return array(
			'slugs'     => $slugs,
			'locations' => $locations,
		);
	}

	/**
	 * Title to use for a page's main heading when it has none.
	 *
	 * @return string
	 */
	public static function page_heading() {
		if ( is_singular() ) {
			$title = single_post_title( '', false );
		} elseif ( is_front_page() || is_home() ) {
			$title = is_home() && ! is_front_page() ? single_post_title( '', false ) : get_bloginfo( 'name' );
		} elseif ( is_search() ) {
			/* translators: %s: search query. */
			$title = sprintf( __( 'Search results for "%s"', 'corea11y' ), get_search_query( false ) );
		} elseif ( is_404() ) {
			$title = __( 'Page not found', 'corea11y' );
		} else {
			$title = get_the_archive_title();
		}
		return trim( wp_strip_all_tags( (string) $title ) );
	}

	/**
	 * Fill in missing alt text from the Media Library.
	 *
	 * @param string $image         Image tag.
	 * @param string $context       Context.
	 * @param int    $attachment_id Attachment id (0 if unknown).
	 * @return string
	 */
	public static function media_alt( $image, $context, $attachment_id ) {
		if ( ! $attachment_id || ! is_string( $image ) ) {
			return $image;
		}
		$tags = new WP_HTML_Tag_Processor( $image );
		if ( ! $tags->next_tag( 'img' ) || null !== $tags->get_attribute( 'alt' ) ) {
			return $image;
		}
		$alt = trim( (string) get_post_meta( $attachment_id, '_wp_attachment_image_alt', true ) );
		if ( '' === $alt ) {
			return $image;
		}
		$tags->set_attribute( 'alt', $alt );
		return $tags->get_updated_html();
	}

	/**
	 * Add the post title to the classic "more" link for screen readers.
	 *
	 * @param string $link Link HTML.
	 * @return string
	 */
	public static function more_link( $link ) {
		if ( ! is_string( $link ) || false !== strpos( $link, 'screen-reader-text' ) || false !== strpos( $link, 'aria-label' ) ) {
			return $link;
		}
		$title = get_the_title();
		if ( '' === $title ) {
			return $link;
		}
		/* translators: %s: post title. */
		$extra = '<span class="corea11y-sr">' . esc_html( sprintf( __( ': %s', 'corea11y' ), wp_strip_all_tags( $title ) ) ) . '</span>';
		$pos   = strrpos( $link, '</a>' );
		return false === $pos ? $link : substr_replace( $link, $extra . '</a>', $pos, 4 );
	}

	/**
	 * Add the post title to the Read More block for screen readers.
	 *
	 * @param string   $content  Block HTML.
	 * @param array    $block    Block.
	 * @param WP_Block $instance Block instance.
	 * @return string
	 */
	public static function read_more_block( $content, $block, $instance ) {
		if ( ! is_string( $content ) || '' === $content ) {
			return $content;
		}
		$post_id = ( $instance instanceof WP_Block && isset( $instance->context['postId'] ) ) ? (int) $instance->context['postId'] : 0;
		$title   = $post_id ? wp_strip_all_tags( get_the_title( $post_id ) ) : '';
		if ( '' === $title || false !== strpos( $content, 'screen-reader-text' ) ) {
			return $content;
		}
		$tags = new WP_HTML_Tag_Processor( $content );
		if ( $tags->next_tag( 'a' ) && ! $tags->get_attribute( 'aria-label' ) ) {
			$text = trim( wp_strip_all_tags( $content ) );
			/* translators: 1: link text such as "Read more", 2: post title. */
			$tags->set_attribute( 'aria-label', sprintf( __( '%1$s: %2$s', 'corea11y' ), $text ? $text : __( 'Read more', 'corea11y' ), $title ) );
			return $tags->get_updated_html();
		}
		return $content;
	}

	/**
	 * Enqueue the Pro script and styles for the fixes that are on.
	 */
	public static function enqueue() {
		$state = CoreA11y_Fixes::state();
		$on    = array_keys( array_filter( $state ) );
		$js    = array_values( array_intersect( self::JS_FIXES, $on ) );

		$css = '';
		if ( $js || ! empty( $state['read-more'] ) ) {
			$css .= '.corea11y-sr{position:absolute!important;width:1px!important;height:1px!important;padding:0!important;margin:-1px!important;overflow:hidden!important;clip:rect(0,0,0,0)!important;white-space:nowrap!important;border:0!important}';
		}
		if ( ! empty( $state['dropdown-keyboard'] ) ) {
			$css .= '.corea11y-open>ul,.corea11y-open>.sub-menu,.corea11y-open>.children,.corea11y-open>.dropdown-menu{display:block!important;visibility:visible!important;opacity:1!important;pointer-events:auto!important}';
		}
		if ( ! empty( $state['target-size'] ) ) {
			$css .= '.corea11y-target{min-width:24px!important;min-height:24px!important}.corea11y-target-inline{display:inline-flex!important;align-items:center;justify-content:center}';
		}
		if ( ! empty( $state['placeholder-contrast'] ) ) {
			$css .= '.corea11y-ph-dark::placeholder{color:#5f5f5f!important;opacity:1!important}.corea11y-ph-light::placeholder{color:#c4c4c4!important;opacity:1!important}';
		}
		if ( ! empty( $state['underline-links'] ) ) {
			$css .= ':where(main,article,.entry-content,.wp-block-post-content) :where(p,li,td,dd,blockquote,figcaption) a:not([class*="button"]):not([role="button"]){text-decoration:underline!important;text-underline-offset:.15em}';
		}
		if ( $css ) {
			wp_register_style( self::HANDLE, false, array(), COREA11Y_PRO_VERSION );
			wp_enqueue_style( self::HANDLE );
			wp_add_inline_style( self::HANDLE, $css );
		}

		if ( $js ) {
			$min = file_exists( COREA11Y_PRO_DIR . 'assets/js/pro-fixes.min.js' ) && ! ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '.min' : '';
			wp_enqueue_script( self::HANDLE, COREA11Y_PRO_URL . 'assets/js/pro-fixes' . $min . '.js', array(), COREA11Y_PRO_VERSION, array( 'in_footer' => true ) );
			wp_localize_script(
				self::HANDLE,
				'corea11yProFixes',
				array(
					'fixes' => $js,
					'text'  => array(
						'newTab'   => __( '(opens in a new tab)', 'corea11y' ),
						'files'    => array(
							'pdf'  => __( 'PDF', 'corea11y' ),
							'doc'  => __( 'Word document', 'corea11y' ),
							'docx' => __( 'Word document', 'corea11y' ),
							'xls'  => __( 'Excel spreadsheet', 'corea11y' ),
							'xlsx' => __( 'Excel spreadsheet', 'corea11y' ),
							'ppt'  => __( 'PowerPoint', 'corea11y' ),
							'pptx' => __( 'PowerPoint', 'corea11y' ),
							'zip'  => __( 'ZIP file', 'corea11y' ),
						),
						'buttons'  => array(
							'search'  => __( 'Search', 'corea11y' ),
							'menu'    => __( 'Menu', 'corea11y' ),
							'close'   => __( 'Close', 'corea11y' ),
							'cart'    => __( 'Cart', 'corea11y' ),
							'account' => __( 'Account', 'corea11y' ),
							'next'    => __( 'Next', 'corea11y' ),
							'prev'    => __( 'Previous', 'corea11y' ),
							'play'    => __( 'Play', 'corea11y' ),
							'pause'   => __( 'Pause', 'corea11y' ),
						),
						'email'    => __( 'Email', 'corea11y' ),
						'phone'    => __( 'Call', 'corea11y' ),
						/* translators: %s: page number. */
						'page'     => __( 'Page %s', 'corea11y' ),
					),
					'menus'   => in_array( 'menu-names', $js, true ) ? self::menu_names() : null,
					'heading' => in_array( 'page-heading', $js, true ) ? self::page_heading() : '',
				)
			);
		}
	}

	/**
	 * Ask optimizers that read script attributes to leave the Pro script alone.
	 *
	 * @param string $tag    Script tag.
	 * @param string $handle Handle.
	 * @return string
	 */
	public static function script_attributes( $tag, $handle ) {
		if ( self::HANDLE !== $handle || false !== strpos( $tag, 'data-no-optimize' ) ) {
			return $tag;
		}
		return str_replace( '<script ', '<script data-no-optimize="1" data-no-defer="1" data-no-minify="1" data-cfasync="false" ', $tag );
	}

	/**
	 * Add Pro script patterns to an optimizer's exclusion list.
	 *
	 * @param mixed $list Existing list.
	 * @return array
	 */
	public static function add_patterns( $list ) {
		return array_values( array_unique( array_merge( (array) $list, array( '/pro/assets/js/pro-fixes', 'corea11yProFixes' ) ) ) );
	}

	/**
	 * Add the Pro script handle to an optimizer's exclusion list.
	 *
	 * @param mixed $list Existing list.
	 * @return array
	 */
	public static function add_handle( $list ) {
		return array_values( array_unique( array_merge( (array) $list, array( self::HANDLE ) ) ) );
	}
}
