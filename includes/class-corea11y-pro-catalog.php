<?php
/**
 * The list of Pro quick fixes: names, descriptions and the audit rules each one solves.
 *
 * This file holds words only, no fix code. The free version uses it to show what Pro
 * adds (and how many problems on the site Pro could fix); Pro reads its fix
 * definitions from here too, so both always show the same list.
 *
 * @package CoreA11y
 */

defined( 'ABSPATH' ) || exit;

/**
 * Pro fix catalog.
 */
class CoreA11y_Pro_Catalog {

	/**
	 * Pro fix definitions, keyed by id (same shape as CoreA11y_Fixes::all()).
	 *
	 * @return array<string,array{title:string,desc:string,wcag:string,rules:array}>
	 */
	public static function fixes() {
		return array(
			'media-alt'         => array(
				'title' => __( 'Use Media Library alt text in posts', 'msacalab-accessibility-checker' ),
				'desc'  => __( 'When an image in a post has no alt text but you have written one in the Media Library, use it. Images you marked decorative are left alone.', 'msacalab-accessibility-checker' ),
				'wcag'  => '1.1.1',
				'rules' => array( 'image-alt', 'corea11y-content-img-alt' ),
			),
			'link-names'        => array(
				'title' => __( 'Name icon-only links', 'msacalab-accessibility-checker' ),
				'desc'  => __( 'Gives links with no text a name screen readers can announce, using their tooltip or, for social media and email icons, the service name.', 'msacalab-accessibility-checker' ),
				'wcag'  => '2.4.4, 4.1.2',
				'rules' => array( 'link-name' ),
			),
			'button-names'      => array(
				'title' => __( 'Name icon-only buttons', 'msacalab-accessibility-checker' ),
				'desc'  => __( 'Gives buttons with no text a name, using their tooltip or a clear purpose such as "Search", "Menu" or "Close".', 'msacalab-accessibility-checker' ),
				'wcag'  => '4.1.2',
				'rules' => array( 'button-name' ),
			),
			'new-tab'           => array(
				'title' => __( 'Warn when links open a new tab', 'msacalab-accessibility-checker' ),
				'desc'  => __( 'Adds "(opens in a new tab)" for screen readers to links that open a new tab or window without saying so. Nothing changes visually.', 'msacalab-accessibility-checker' ),
				'wcag'  => '3.2.5',
				'rules' => array( 'corea11y-link-new-window' ),
			),
			'file-type'         => array(
				'title' => __( 'Say the file type on file links', 'msacalab-accessibility-checker' ),
				'desc'  => __( 'Tells screen readers when a link opens a PDF, Word, Excel, PowerPoint or ZIP file. Nothing changes visually.', 'msacalab-accessibility-checker' ),
				'wcag'  => '2.4.4',
				'rules' => array( 'corea11y-link-file-type' ),
			),
			'read-more'         => array(
				'title' => __( 'Make "Read more" links specific', 'msacalab-accessibility-checker' ),
				'desc'  => __( 'Adds the post title to "Read more" and "Continue reading" links for screen readers, so each says which post it opens.', 'msacalab-accessibility-checker' ),
				'wcag'  => '2.4.4',
				'rules' => array( 'corea11y-link-generic' ),
			),
			'link-titles'       => array(
				'title' => __( 'Remove tooltips that repeat link text', 'msacalab-accessibility-checker' ),
				'desc'  => __( 'Removes link tooltips that only repeat the link text, so screen readers don\'t say it twice.', 'msacalab-accessibility-checker' ),
				'wcag'  => '',
				'rules' => array( 'corea11y-link-title-repeat' ),
			),
			'autocomplete'      => array(
				'title' => __( 'Help browsers fill in forms', 'msacalab-accessibility-checker' ),
				'desc'  => __( 'Marks common fields (name, email, phone, address) so browsers and assistive technology can fill them in automatically.', 'msacalab-accessibility-checker' ),
				'wcag'  => '1.3.5',
				'rules' => array(),
			),
			'placeholder-names' => array(
				'title' => __( 'Give every form field a name', 'msacalab-accessibility-checker' ),
				'desc'  => __( 'Keeps placeholder text as the field\'s name for screen readers after someone starts typing, and makes form labels your theme hides completely readable to screen readers. Nothing changes visually. A visible label is still best.', 'msacalab-accessibility-checker' ),
				'wcag'  => '1.3.1, 4.1.2',
				'rules' => array( 'corea11y-hidden-label' ),
			),
			'main-landmark'     => array(
				'title' => __( 'Mark the main content area', 'msacalab-accessibility-checker' ),
				'desc'  => __( 'Marks your theme\'s main content area as "main", so screen reader users can jump straight to it.', 'msacalab-accessibility-checker' ),
				'wcag'  => '1.3.1',
				'rules' => array( 'landmark-one-main', 'corea11y-no-landmarks' ),
			),
			'decorative-svg'    => array(
				'title' => __( 'Hide decorative icons in links and buttons', 'msacalab-accessibility-checker' ),
				'desc'  => __( 'Icons inside links and buttons that already have text are hidden from screen readers, so only the text is read.', 'msacalab-accessibility-checker' ),
				'wcag'  => '1.1.1',
				'rules' => array(),
			),
			'focus-obscured'    => array(
				'title' => __( 'Keep focused items out from under sticky headers', 'msacalab-accessibility-checker' ),
				'desc'  => __( 'When a sticky header or bar covers the top of the screen, scrolls keyboard focus into view below it.', 'msacalab-accessibility-checker' ),
				'wcag'  => '2.4.11',
				'rules' => array(),
			),
			'underline-links'   => array(
				'title' => __( 'Underline links in text', 'msacalab-accessibility-checker' ),
				'desc'  => __( 'Underlines links inside paragraphs and lists, so they can be told apart without relying on color. This changes how your site looks.', 'msacalab-accessibility-checker' ),
				'wcag'  => '1.4.1',
				'rules' => array( 'link-in-text-block' ),
				'look'  => true,
			),
			'menu-names' => array(
				'title' => __( 'Name your menus', 'msacalab-accessibility-checker' ),
				'desc'  => __( 'Gives each menu a name screen readers announce, such as "Main menu" or "Footer menu", taken from Appearance > Menus.', 'msacalab-accessibility-checker' ),
				'wcag'  => '1.3.1, 2.4.1',
				'rules' => array( 'landmark-unique' ),
			),
			'header-footer' => array(
				'title' => __( 'Mark the header and footer', 'msacalab-accessibility-checker' ),
				'desc'  => __( 'Marks your theme\'s header and footer areas, so screen reader users can jump to them like the main content.', 'msacalab-accessibility-checker' ),
				'wcag'  => '1.3.1',
				'rules' => array( 'region' ),
			),
			'page-heading' => array(
				'title' => __( 'Add a main heading where a page has none', 'msacalab-accessibility-checker' ),
				'desc'  => __( 'When a page has no main (H1) heading, adds the page title as one for screen readers. Nothing changes visually.', 'msacalab-accessibility-checker' ),
				'wcag'  => '1.3.1, 2.4.6',
				'rules' => array( 'page-has-heading-one' ),
			),
			'dropdown-keyboard' => array(
				'title' => __( 'Open dropdown menus with the keyboard', 'msacalab-accessibility-checker' ),
				'desc'  => __( 'Submenus that only open on mouse hover also open when keyboard users Tab into them, and close with Escape.', 'msacalab-accessibility-checker' ),
				'wcag'  => '2.1.1',
				'rules' => array(),
			),
			'autoplay' => array(
				'title' => __( 'Stop sound from playing automatically', 'msacalab-accessibility-checker' ),
				'desc'  => __( 'Videos and audio that would start playing with sound wait until the visitor presses play. Muted background videos are left alone.', 'msacalab-accessibility-checker' ),
				'wcag'  => '1.4.2',
				'rules' => array( 'no-autoplay-audio' ),
			),
			'merge-links' => array(
				'title' => __( 'Combine duplicate image and text links', 'msacalab-accessibility-checker' ),
				'desc'  => __( 'When an image and the text next to it link to the same page, keyboard and screen reader users get one link instead of two.', 'msacalab-accessibility-checker' ),
				'wcag'  => '2.4.4',
				'rules' => array( 'corea11y-link-adjacent-duplicate' ),
			),
			'empty-links' => array(
				'title' => __( 'Skip empty links', 'msacalab-accessibility-checker' ),
				'desc'  => __( 'Links with nothing inside them are skipped by the keyboard and screen readers, so visitors don\'t land on a blank stop.', 'msacalab-accessibility-checker' ),
				'wcag'  => '2.4.4',
				'rules' => array(),
			),
			'image-link-names' => array(
				'title' => __( 'Name image links from your own site', 'msacalab-accessibility-checker' ),
				'desc'  => __( 'When a linked image has no alt text, uses the title of the page or image it links to on your site.', 'msacalab-accessibility-checker' ),
				'wcag'  => '1.1.1, 2.4.4',
				'rules' => array(),
			),
			'page-numbers' => array(
				'title' => __( 'Say "Page" on page-number links', 'msacalab-accessibility-checker' ),
				'desc'  => __( 'Page-number links such as "2" are read as "Page 2", and the current page is announced as current.', 'msacalab-accessibility-checker' ),
				'wcag'  => '2.4.4',
				'rules' => array(),
			),
			'table-headers' => array(
				'title' => __( 'Connect table headers to their cells', 'msacalab-accessibility-checker' ),
				'desc'  => __( 'Tells screen readers which cells each table header describes, so tables are read row by row correctly.', 'msacalab-accessibility-checker' ),
				'wcag'  => '1.3.1',
				'rules' => array( 'th-has-data-cells' ),
			),
			'image-titles' => array(
				'title' => __( 'Remove tooltips that repeat alt text', 'msacalab-accessibility-checker' ),
				'desc'  => __( 'Removes image tooltips that only repeat the alt text, so screen readers don\'t read it twice.', 'msacalab-accessibility-checker' ),
				'wcag'  => '',
				'rules' => array( 'corea11y-image-title-repeat' ),
			),
			'form-label-links' => array(
				'title' => __( 'Connect form labels to their fields', 'msacalab-accessibility-checker' ),
				'desc'  => __( 'When a form shows a label that isn\'t linked to its field (common with page builders), links them so screen readers read the label.', 'msacalab-accessibility-checker' ),
				'wcag'  => '1.3.1, 4.1.2',
				'rules' => array( 'label' ),
			),
			'required-fields' => array(
				'title' => __( 'Announce required fields', 'msacalab-accessibility-checker' ),
				'desc'  => __( 'Fields marked with * or "(required)" are announced as required to screen reader users.', 'msacalab-accessibility-checker' ),
				'wcag'  => '3.3.2',
				'rules' => array(),
			),
			'form-messages' => array(
				'title' => __( 'Read out form messages', 'msacalab-accessibility-checker' ),
				'desc'  => __( 'Error and success messages from common form plugins are read out by screen readers when they appear.', 'msacalab-accessibility-checker' ),
				'wcag'  => '4.1.3',
				'rules' => array(),
			),
			'target-size' => array(
				'title' => __( 'Make small icon links easier to tap', 'msacalab-accessibility-checker' ),
				'desc'  => __( 'Gives icon-only links and buttons a tap area of at least 24 by 24 pixels. This can change how your site looks.', 'msacalab-accessibility-checker' ),
				'wcag'  => '2.5.8',
				'rules' => array( 'target-size' ),
				'look'  => true,
			),
			'placeholder-contrast' => array(
				'title' => __( 'Make placeholder text readable', 'msacalab-accessibility-checker' ),
				'desc'  => __( 'Makes faint placeholder text in form fields dark enough to read (or light enough on dark fields). This changes how your site looks.', 'msacalab-accessibility-checker' ),
				'wcag'  => '1.4.3',
				'rules' => array(),
				'look'  => true,
			),
		);
	}

	/**
	 * Groups for showing the list, in display order.
	 *
	 * @return array<string,array{title:string,ids:string[]}>
	 */
	public static function groups() {
		return array(
			'links'     => array(
				'title' => __( 'Links and buttons', 'msacalab-accessibility-checker' ),
				'ids'   => array( 'link-names', 'button-names', 'new-tab', 'file-type', 'read-more', 'link-titles', 'merge-links', 'empty-links', 'image-link-names', 'page-numbers' ),
			),
			'forms'     => array(
				'title' => __( 'Forms', 'msacalab-accessibility-checker' ),
				'ids'   => array( 'placeholder-names', 'form-label-links', 'required-fields', 'form-messages', 'autocomplete' ),
			),
			'keyboard'  => array(
				'title' => __( 'Keyboard and menus', 'msacalab-accessibility-checker' ),
				'ids'   => array( 'dropdown-keyboard', 'focus-obscured', 'menu-names' ),
			),
			'media'     => array(
				'title' => __( 'Images and media', 'msacalab-accessibility-checker' ),
				'ids'   => array( 'media-alt', 'decorative-svg', 'image-titles', 'autoplay' ),
			),
			'structure' => array(
				'title' => __( 'Page structure', 'msacalab-accessibility-checker' ),
				'ids'   => array( 'main-landmark', 'header-footer', 'page-heading', 'table-headers' ),
			),
			'look'      => array(
				'title' => __( 'Fixes that change how your site looks', 'msacalab-accessibility-checker' ),
				'ids'   => array( 'underline-links', 'target-size', 'placeholder-contrast' ),
			),
		);
	}

	/**
	 * Whether Pro itself is installed (its fixes are then part of CoreA11y_Fixes::all()).
	 *
	 * @return bool
	 */
	public static function pro_installed() {
		return class_exists( 'CoreA11y_Pro_Fixes' );
	}

	/**
	 * Pro fix that would solve a rule the free fixes don't cover.
	 *
	 * @param string $rule_id Rule id.
	 * @return string
	 */
	public static function for_rule( $rule_id ) {
		foreach ( self::fixes() as $id => $fix ) {
			if ( in_array( $rule_id, $fix['rules'], true ) ) {
				return $id;
			}
		}
		return '';
	}

	/**
	 * Problems in an audit each Pro fix would solve (only rules no free fix covers).
	 *
	 * @param int $audit_id Audit id.
	 * @return array<string,int> Counts keyed by Pro fix id.
	 */
	public static function found( $audit_id ) {
		global $wpdb;
		$out = array();
		if ( ! $audit_id ) {
			return $out;
		}
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom table.
		$rows = $wpdb->get_results( $wpdb->prepare( "SELECT rule_id, COUNT(*) AS n FROM %i WHERE audit_id = %d AND status <> 'dismissed' GROUP BY rule_id", CoreA11y_Install::tables()['issues'], (int) $audit_id ) );
		foreach ( (array) $rows as $r ) {
			if ( '' !== CoreA11y_Fixes::for_rule( $r->rule_id ) ) {
				continue;
			}
			$id = self::for_rule( $r->rule_id );
			if ( '' !== $id ) {
				$out[ $id ] = ( isset( $out[ $id ] ) ? $out[ $id ] : 0 ) + (int) $r->n;
			}
		}
		return $out;
	}
}
