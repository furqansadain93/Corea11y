<?php
/**
 * Manual checklist for WCAG criteria that software can't fully judge.
 *
 * @package CoreA11y
 */

defined( 'ABSPATH' ) || exit;

/**
 * Checklist items and stored owner results.
 */
class CoreA11y_Checklist {

	const OPTION = 'corea11y_checklist';

	/**
	 * Allowed result values and labels.
	 *
	 * @return array<string,string>
	 */
	public static function statuses() {
		return array(
			''     => __( 'Not checked', 'msacalab-accessibility-checker' ),
			'pass' => __( 'Pass', 'msacalab-accessibility-checker' ),
			'fail' => __( 'Fail', 'msacalab-accessibility-checker' ),
			'na'   => __( 'Not applicable', 'msacalab-accessibility-checker' ),
		);
	}

	/**
	 * Checklist items. Each: WCAG criterion, level, title, how to check.
	 *
	 * @return array<string,array>
	 */
	public static function items() {
		return array(
			'alt-meaningful'     => array( '1.1.1', 'A', __( 'Alt text describes each image\'s purpose', 'msacalab-accessibility-checker' ), __( 'Open the Media Library and read the alt text of your key images. It should say what the image shows or does in context, not just repeat the file name. Purely decorative images should have empty alt text.', 'msacalab-accessibility-checker' ) ),
			'captions'           => array( '1.2.2', 'A', __( 'Videos have accurate captions', 'msacalab-accessibility-checker' ), __( 'Play each video with captions on. Captions should match the speech and include important sounds. Auto-generated captions usually need correcting.', 'msacalab-accessibility-checker' ) ),
			'audio-description'  => array( '1.2.5', 'AA', __( 'Videos describe important visual information', 'msacalab-accessibility-checker' ), __( 'If a video shows information only visually (on-screen text, actions), it needs narration or an audio-described version.', 'msacalab-accessibility-checker' ) ),
			'reading-order'      => array( '1.3.2', 'A', __( 'Reading order makes sense', 'msacalab-accessibility-checker' ), __( 'Turn off page styles (or use reader view) and read the page top to bottom. Content should still appear in a logical order.', 'msacalab-accessibility-checker' ) ),
			'sensory'            => array( '1.3.3', 'A', __( 'Instructions don\'t rely on shape, color, size or position alone', 'msacalab-accessibility-checker' ), __( 'Look for instructions like "click the green button" or "see the box on the right" and add a text label to them.', 'msacalab-accessibility-checker' ) ),
			'use-of-color'       => array( '1.4.1', 'A', __( 'Color is not the only way information is shown', 'msacalab-accessibility-checker' ), __( 'Links in body text, required form fields, errors and chart data should also use underlines, text, icons or patterns.', 'msacalab-accessibility-checker' ) ),
			'hover-focus'        => array( '1.4.13', 'AA', __( 'Tooltips and pop-ups on hover can be dismissed and hovered', 'msacalab-accessibility-checker' ), __( 'Content that appears on hover or focus must close with Escape, stay open while the pointer moves onto it, and not vanish on its own.', 'msacalab-accessibility-checker' ) ),
			'keyboard'           => array( '2.1.1', 'A', __( 'Everything works with a keyboard', 'msacalab-accessibility-checker' ), __( 'Put the mouse away. Use Tab, Shift+Tab, Enter, Space and arrow keys to use menus, forms, sliders, tabs and pop-ups.', 'msacalab-accessibility-checker' ) ),
			'keyboard-trap'      => array( '2.1.2', 'A', __( 'Keyboard focus never gets stuck', 'msacalab-accessibility-checker' ), __( 'While tabbing, make sure you can always move focus out of every component, including embedded videos, maps and chat widgets.', 'msacalab-accessibility-checker' ) ),
			'focus-order'        => array( '2.4.3', 'A', __( 'Focus moves in a logical order', 'msacalab-accessibility-checker' ), __( 'Tab through each page template. Focus should follow the visual order, and open dialogs should receive focus.', 'msacalab-accessibility-checker' ) ),
			'headings-labels'    => array( '2.4.6', 'AA', __( 'Headings and labels are descriptive', 'msacalab-accessibility-checker' ), __( 'Each heading should tell you what the section is about, and each form label should say what to enter.', 'msacalab-accessibility-checker' ) ),
			'focus-not-obscured' => array( '2.4.11', 'AA', __( 'Focused items aren\'t hidden behind sticky headers or banners', 'msacalab-accessibility-checker' ), __( 'Tab through pages with a sticky header, cookie banner or chat button visible. The focused element must stay at least partly visible.', 'msacalab-accessibility-checker' ) ),
			'dragging'           => array( '2.5.7', 'AA', __( 'Anything you drag can also be done without dragging', 'msacalab-accessibility-checker' ), __( 'Sliders, sortable lists and maps need a click or tap alternative, such as buttons.', 'msacalab-accessibility-checker' ) ),
			'consistent-nav'     => array( '3.2.3', 'AA', __( 'Navigation is consistent across pages', 'msacalab-accessibility-checker' ), __( 'Menus and repeated links should appear in the same order on every page.', 'msacalab-accessibility-checker' ) ),
			'error-identify'     => array( '3.3.1', 'A', __( 'Form errors are clearly described in text', 'msacalab-accessibility-checker' ), __( 'Submit each form with mistakes. Every error should be explained in text next to the field, not only with a red border.', 'msacalab-accessibility-checker' ) ),
			'error-suggest'      => array( '3.3.3', 'AA', __( 'Form errors suggest how to fix them', 'msacalab-accessibility-checker' ), __( 'Error messages should say what is expected, for example "Enter a date as DD/MM/YYYY".', 'msacalab-accessibility-checker' ) ),
			'redundant-entry'    => array( '3.3.7', 'A', __( 'Users don\'t have to re-enter the same information', 'msacalab-accessibility-checker' ), __( 'In multi-step forms or checkout, information entered earlier should be filled in or selectable.', 'msacalab-accessibility-checker' ) ),
			'accessible-auth'    => array( '3.3.8', 'AA', __( 'Login doesn\'t require solving a puzzle or memorizing', 'msacalab-accessibility-checker' ), __( 'Login and account forms should allow password managers and paste, and avoid puzzle CAPTCHAs without an alternative.', 'msacalab-accessibility-checker' ) ),
			'status-messages'    => array( '4.1.3', 'AA', __( 'Status messages are announced to screen readers', 'msacalab-accessibility-checker' ), __( 'Messages like "Added to cart" or "Form sent" should be announced without moving focus. Test with a screen reader.', 'msacalab-accessibility-checker' ) ),
			'tables-make-sense' => array( '1.3.1', 'A', __( 'Data tables make sense when read aloud', 'msacalab-accessibility-checker' ), __( 'Check each data table: column and row headings should be marked as header cells, and the table should have a caption or heading that says what it shows. Avoid tables for layout.', 'msacalab-accessibility-checker' ) ),
			'resize-text' => array( '1.4.4', 'AA', __( 'Text can be zoomed to 200%', 'msacalab-accessibility-checker' ), __( 'Zoom your browser to 200% (Ctrl or Cmd and +). All text should get bigger, and nothing should be cut off or overlap.', 'msacalab-accessibility-checker' ) ),
			'reflow' => array( '1.4.10', 'AA', __( 'Pages work on a narrow screen without sideways scrolling', 'msacalab-accessibility-checker' ), __( 'Set your browser window to about 320px wide, or zoom to 400%. Content should fit the width, with no need to scroll left and right (except maps, data tables and similar).', 'msacalab-accessibility-checker' ) ),
			'text-spacing' => array( '1.4.12', 'AA', __( 'Text still works with extra spacing', 'msacalab-accessibility-checker' ), __( 'Use a text spacing bookmarklet to increase line, letter and word spacing. No text should be cut off or overlap.', 'msacalab-accessibility-checker' ) ),
			'timing' => array( '2.2.1', 'A', __( 'Time limits can be turned off or extended', 'msacalab-accessibility-checker' ), __( 'If anything times out (sessions, forms, quizzes), visitors must be warned and able to extend the time, unless the limit is essential.', 'msacalab-accessibility-checker' ) ),
			'pause-moving' => array( '2.2.2', 'A', __( 'Moving content can be paused', 'msacalab-accessibility-checker' ), __( 'Carousels, sliders, animations and auto-playing video that last more than five seconds need a pause or stop button.', 'msacalab-accessibility-checker' ) ),
			'flashing' => array( '2.3.1', 'A', __( 'Nothing flashes more than three times a second', 'msacalab-accessibility-checker' ), __( 'Check videos, animations and GIFs. Content should not flash more than three times in one second, as it can trigger seizures.', 'msacalab-accessibility-checker' ) ),
			'page-titles' => array( '2.4.2', 'A', __( 'Page titles describe each page', 'msacalab-accessibility-checker' ), __( 'Look at the browser tab for each template. Every page should have a unique title that says what it is, for example "Contact us - Your Site".', 'msacalab-accessibility-checker' ) ),
			'link-purpose' => array( '2.4.4', 'A', __( 'Link text makes sense', 'msacalab-accessibility-checker' ), __( 'Read the links on a page. Each should say where it goes, on its own or with the sentence around it. Replace "click here" with descriptive text.', 'msacalab-accessibility-checker' ) ),
			'lang-parts' => array( '3.1.2', 'AA', __( 'Text in another language is marked', 'msacalab-accessibility-checker' ), __( 'If a page contains a passage in another language, mark it with that language (in the block editor, select the text and use the Language option where available, or add a lang attribute) so screen readers pronounce it correctly.', 'msacalab-accessibility-checker' ) ),
			'no-surprises' => array( '3.2.1, 3.2.2', 'A', __( 'Nothing changes unexpectedly when you focus or select something', 'msacalab-accessibility-checker' ), __( 'Tab through forms and change dropdowns and options. The page should not jump, submit or open new windows unless you press a button.', 'msacalab-accessibility-checker' ) ),
			'plain-language' => array( '', '', __( 'Content uses plain language and explains abbreviations', 'msacalab-accessibility-checker' ), __( 'Read key pages as a newcomer. Use short sentences and common words, and spell out abbreviations the first time they appear. (A recommended practice, not a WCAG AA requirement.)', 'msacalab-accessibility-checker' ) ),
			'screen-reader'      => array( '', '', __( 'One page tested with a screen reader', 'msacalab-accessibility-checker' ), __( 'Use NVDA (Windows, free) or VoiceOver (Mac/iPhone, built in) to navigate your home page and one form. Note anything confusing.', 'msacalab-accessibility-checker' ) ),
		);
	}

	/**
	 * Saved results keyed by item id.
	 *
	 * @return array<string,array>
	 */
	public static function results() {
		$saved = get_option( self::OPTION, array() );
		return is_array( $saved ) ? $saved : array();
	}

	/**
	 * Save results, keeping the date and user only for items whose status changed.
	 *
	 * @param array $raw Raw submitted statuses keyed by item id.
	 */
	public static function save( array $raw ) {
		$items    = self::items();
		$statuses = self::statuses();
		$old      = self::results();
		$new      = array();

		foreach ( $items as $id => $item ) {
			$status = isset( $raw[ $id ] ) ? sanitize_key( $raw[ $id ] ) : '';
			if ( ! array_key_exists( $status, $statuses ) || '' === $status ) {
				continue;
			}
			if ( isset( $old[ $id ]['status'] ) && $old[ $id ]['status'] === $status ) {
				$new[ $id ] = $old[ $id ];
				continue;
			}
			$new[ $id ] = array(
				'status' => $status,
				'time'   => time(),
				'user'   => get_current_user_id(),
			);
		}

		update_option( self::OPTION, $new, false );
	}

	/**
	 * Counts per status.
	 *
	 * @return array<string,int>
	 */
	public static function summary() {
		$counts  = array_fill_keys( array_keys( self::statuses() ), 0 );
		$results = self::results();
		foreach ( array_keys( self::items() ) as $id ) {
			$status = isset( $results[ $id ]['status'] ) ? $results[ $id ]['status'] : '';
			++$counts[ $status ];
		}
		return $counts;
	}
}
