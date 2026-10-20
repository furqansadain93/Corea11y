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
			''     => __( 'Not checked', 'corea11y' ),
			'pass' => __( 'Pass', 'corea11y' ),
			'fail' => __( 'Fail', 'corea11y' ),
			'na'   => __( 'Not applicable', 'corea11y' ),
		);
	}

	/**
	 * Checklist items. Each: WCAG criterion, level, title, how to check.
	 *
	 * @return array<string,array>
	 */
	public static function items() {
		return array(
			'alt-meaningful'     => array( '1.1.1', 'A', __( 'Alt text describes each image\'s purpose', 'corea11y' ), __( 'Open the Media Library and read the alt text of your key images. It should say what the image shows or does in context, not just repeat the file name. Purely decorative images should have empty alt text.', 'corea11y' ) ),
			'captions'           => array( '1.2.2', 'A', __( 'Videos have accurate captions', 'corea11y' ), __( 'Play each video with captions on. Captions should match the speech and include important sounds. Auto-generated captions usually need correcting.', 'corea11y' ) ),
			'audio-description'  => array( '1.2.5', 'AA', __( 'Videos describe important visual information', 'corea11y' ), __( 'If a video shows information only visually (on-screen text, actions), it needs narration or an audio-described version.', 'corea11y' ) ),
			'reading-order'      => array( '1.3.2', 'A', __( 'Reading order makes sense', 'corea11y' ), __( 'Turn off page styles (or use reader view) and read the page top to bottom. Content should still appear in a logical order.', 'corea11y' ) ),
			'sensory'            => array( '1.3.3', 'A', __( 'Instructions don\'t rely on shape, color, size or position alone', 'corea11y' ), __( 'Look for instructions like "click the green button" or "see the box on the right" and add a text label to them.', 'corea11y' ) ),
			'use-of-color'       => array( '1.4.1', 'A', __( 'Color is not the only way information is shown', 'corea11y' ), __( 'Links in body text, required form fields, errors and chart data should also use underlines, text, icons or patterns.', 'corea11y' ) ),
			'hover-focus'        => array( '1.4.13', 'AA', __( 'Tooltips and pop-ups on hover can be dismissed and hovered', 'corea11y' ), __( 'Content that appears on hover or focus must close with Escape, stay open while the pointer moves onto it, and not vanish on its own.', 'corea11y' ) ),
			'keyboard'           => array( '2.1.1', 'A', __( 'Everything works with a keyboard', 'corea11y' ), __( 'Put the mouse away. Use Tab, Shift+Tab, Enter, Space and arrow keys to use menus, forms, sliders, tabs and pop-ups.', 'corea11y' ) ),
			'keyboard-trap'      => array( '2.1.2', 'A', __( 'Keyboard focus never gets stuck', 'corea11y' ), __( 'While tabbing, make sure you can always move focus out of every component, including embedded videos, maps and chat widgets.', 'corea11y' ) ),
			'focus-order'        => array( '2.4.3', 'A', __( 'Focus moves in a logical order', 'corea11y' ), __( 'Tab through each page template. Focus should follow the visual order, and open dialogs should receive focus.', 'corea11y' ) ),
			'headings-labels'    => array( '2.4.6', 'AA', __( 'Headings and labels are descriptive', 'corea11y' ), __( 'Each heading should tell you what the section is about, and each form label should say what to enter.', 'corea11y' ) ),
			'focus-not-obscured' => array( '2.4.11', 'AA', __( 'Focused items aren\'t hidden behind sticky headers or banners', 'corea11y' ), __( 'Tab through pages with a sticky header, cookie banner or chat button visible. The focused element must stay at least partly visible.', 'corea11y' ) ),
			'dragging'           => array( '2.5.7', 'AA', __( 'Anything you drag can also be done without dragging', 'corea11y' ), __( 'Sliders, sortable lists and maps need a click or tap alternative, such as buttons.', 'corea11y' ) ),
			'consistent-nav'     => array( '3.2.3', 'AA', __( 'Navigation is consistent across pages', 'corea11y' ), __( 'Menus and repeated links should appear in the same order on every page.', 'corea11y' ) ),
			'error-identify'     => array( '3.3.1', 'A', __( 'Form errors are clearly described in text', 'corea11y' ), __( 'Submit each form with mistakes. Every error should be explained in text next to the field, not only with a red border.', 'corea11y' ) ),
			'error-suggest'      => array( '3.3.3', 'AA', __( 'Form errors suggest how to fix them', 'corea11y' ), __( 'Error messages should say what is expected, for example "Enter a date as DD/MM/YYYY".', 'corea11y' ) ),
			'redundant-entry'    => array( '3.3.7', 'A', __( 'Users don\'t have to re-enter the same information', 'corea11y' ), __( 'In multi-step forms or checkout, information entered earlier should be filled in or selectable.', 'corea11y' ) ),
			'accessible-auth'    => array( '3.3.8', 'AA', __( 'Login doesn\'t require solving a puzzle or memorizing', 'corea11y' ), __( 'Login and account forms should allow password managers and paste, and avoid puzzle CAPTCHAs without an alternative.', 'corea11y' ) ),
			'status-messages'    => array( '4.1.3', 'AA', __( 'Status messages are announced to screen readers', 'corea11y' ), __( 'Messages like "Added to cart" or "Form sent" should be announced without moving focus. Test with a screen reader.', 'corea11y' ) ),
			'tables-make-sense' => array( '1.3.1', 'A', __( 'Data tables make sense when read aloud', 'corea11y' ), __( 'Check each data table: column and row headings should be marked as header cells, and the table should have a caption or heading that says what it shows. Avoid tables for layout.', 'corea11y' ) ),
			'resize-text' => array( '1.4.4', 'AA', __( 'Text can be zoomed to 200%', 'corea11y' ), __( 'Zoom your browser to 200% (Ctrl or Cmd and +). All text should get bigger, and nothing should be cut off or overlap.', 'corea11y' ) ),
			'reflow' => array( '1.4.10', 'AA', __( 'Pages work on a narrow screen without sideways scrolling', 'corea11y' ), __( 'Set your browser window to about 320px wide, or zoom to 400%. Content should fit the width, with no need to scroll left and right (except maps, data tables and similar).', 'corea11y' ) ),
			'text-spacing' => array( '1.4.12', 'AA', __( 'Text still works with extra spacing', 'corea11y' ), __( 'Use a text spacing bookmarklet to increase line, letter and word spacing. No text should be cut off or overlap.', 'corea11y' ) ),
			'timing' => array( '2.2.1', 'A', __( 'Time limits can be turned off or extended', 'corea11y' ), __( 'If anything times out (sessions, forms, quizzes), visitors must be warned and able to extend the time, unless the limit is essential.', 'corea11y' ) ),
			'pause-moving' => array( '2.2.2', 'A', __( 'Moving content can be paused', 'corea11y' ), __( 'Carousels, sliders, animations and auto-playing video that last more than five seconds need a pause or stop button.', 'corea11y' ) ),
			'flashing' => array( '2.3.1', 'A', __( 'Nothing flashes more than three times a second', 'corea11y' ), __( 'Check videos, animations and GIFs. Content should not flash more than three times in one second, as it can trigger seizures.', 'corea11y' ) ),
			'page-titles' => array( '2.4.2', 'A', __( 'Page titles describe each page', 'corea11y' ), __( 'Look at the browser tab for each template. Every page should have a unique title that says what it is, for example "Contact us - Your Site".', 'corea11y' ) ),
			'link-purpose' => array( '2.4.4', 'A', __( 'Link text makes sense', 'corea11y' ), __( 'Read the links on a page. Each should say where it goes, on its own or with the sentence around it. Replace "click here" with descriptive text.', 'corea11y' ) ),
			'lang-parts' => array( '3.1.2', 'AA', __( 'Text in another language is marked', 'corea11y' ), __( 'If a page contains a passage in another language, mark it with that language (in the block editor, select the text and use the Language option where available, or add a lang attribute) so screen readers pronounce it correctly.', 'corea11y' ) ),
			'no-surprises' => array( '3.2.1, 3.2.2', 'A', __( 'Nothing changes unexpectedly when you focus or select something', 'corea11y' ), __( 'Tab through forms and change dropdowns and options. The page should not jump, submit or open new windows unless you press a button.', 'corea11y' ) ),
			'plain-language' => array( '', '', __( 'Content uses plain language and explains abbreviations', 'corea11y' ), __( 'Read key pages as a newcomer. Use short sentences and common words, and spell out abbreviations the first time they appear. (A recommended practice, not a WCAG AA requirement.)', 'corea11y' ) ),
			'screen-reader'      => array( '', '', __( 'One page tested with a screen reader', 'corea11y' ), __( 'Use NVDA (Windows, free) or VoiceOver (Mac/iPhone, built in) to navigate your home page and one form. Note anything confusing.', 'corea11y' ) ),
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
