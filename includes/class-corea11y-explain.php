<?php
/**
 * Plain-language explanations for every rule: what it means, who it affects, how to fix it.
 *
 * @package CoreA11y
 */

defined( 'ABSPATH' ) || exit;

/**
 * Friendly rule explanations shown behind the "?" buttons and in exported reports.
 */
class CoreA11y_Explain {

	/**
	 * Explanation for a rule, with a sensible fallback for rules not listed.
	 *
	 * @param string $rule_id  Rule id.
	 * @param string $fallback Fallback title (the engine's own help text).
	 * @return array{title:string,why:string,who:string,fix:string}
	 */
	public static function get( $rule_id, $fallback = '' ) {
		$all = self::all();
		if ( isset( $all[ $rule_id ] ) ) {
			return $all[ $rule_id ];
		}
		if ( 0 === strpos( $rule_id, 'aria-' ) ) {
			return array(
				'title' => $fallback ? $fallback : __( 'A screen reader instruction (ARIA) is used incorrectly', 'msacalab-accessibility-checker' ),
				'who'   => __( 'Screen reader users', 'msacalab-accessibility-checker' ),
				'why'   => __( 'ARIA attributes tell assistive technology what an element is and what state it is in. When they are wrong or incomplete, screen readers announce confusing or false information, which can be worse than having no ARIA at all.', 'msacalab-accessibility-checker' ),
				'fix'   => __( 'Check the element\'s role and aria-* attributes against the expected pattern. Often the simplest fix is to use a native HTML element (button, link, input) instead of ARIA. This usually comes from a theme or plugin, so you may need to contact its developer.', 'msacalab-accessibility-checker' ),
			);
		}
		return array(
			'title' => $fallback ? $fallback : __( 'Accessibility problem', 'msacalab-accessibility-checker' ),
			'who'   => __( 'People using assistive technology', 'msacalab-accessibility-checker' ),
			'why'   => __( 'This element does not meet a WCAG requirement, which can make it hard or impossible for some visitors to perceive or use.', 'msacalab-accessibility-checker' ),
			'fix'   => __( 'Open "How to fix this" for detailed guidance on this rule, then update the element in your content, theme or plugin settings.', 'msacalab-accessibility-checker' ),
		);
	}

	/**
	 * All known explanations.
	 *
	 * @return array<string,array{title:string,why:string,who:string,fix:string}>
	 */
	public static function all() {
		$e = array();

		$e['image-alt'] = array(
			'title' => __( 'Images are missing a text description', 'msacalab-accessibility-checker' ),
			'who'   => __( 'Blind and low-vision visitors using screen readers; also search engines', 'msacalab-accessibility-checker' ),
			'why'   => __( 'Screen readers read an image\'s alt text out loud. Without it, visitors hear only "image" or the file name, so they miss whatever the picture shows or does.', 'msacalab-accessibility-checker' ),
			'fix'   => __( 'Add alt text that describes what the image shows or does, in a short sentence. If the image is purely decorative, give it empty alt text so screen readers skip it. In WordPress, set this in the Media Library or the image block\'s settings.', 'msacalab-accessibility-checker' ),
		);
		$e['corea11y-content-img-alt'] = array(
			'title' => __( 'Images in your posts are missing a text description', 'msacalab-accessibility-checker' ),
			'who'   => __( 'Blind and low-vision visitors using screen readers', 'msacalab-accessibility-checker' ),
			'why'   => __( 'This image was found in post content without any alt attribute, so screen readers have nothing to announce.', 'msacalab-accessibility-checker' ),
			'fix'   => __( 'Edit the post, select the image and fill in "Alternative text" in the block settings. Use empty alt text only for decorative images.', 'msacalab-accessibility-checker' ),
		);
		$e['corea11y-media-alt'] = array(
			'title' => __( 'Media Library images have no alt text', 'msacalab-accessibility-checker' ),
			'who'   => __( 'Blind and low-vision visitors using screen readers', 'msacalab-accessibility-checker' ),
			'why'   => __( 'Alt text set in the Media Library is used automatically wherever the image is inserted later. Leaving it empty means every future use starts without a description.', 'msacalab-accessibility-checker' ),
			'fix'   => __( 'Go to Media > Library, open the image and fill in "Alternative Text". Leave it empty only if the image is purely decorative.', 'msacalab-accessibility-checker' ),
		);
		$e['role-img-alt'] = array(
			'title' => __( 'Graphics marked as images have no description', 'msacalab-accessibility-checker' ),
			'who'   => __( 'Screen reader users', 'msacalab-accessibility-checker' ),
			'why'   => __( 'This element is announced as an image but has no text alternative, so its meaning is lost.', 'msacalab-accessibility-checker' ),
			'fix'   => __( 'Add an aria-label or aria-labelledby that describes the graphic, or remove role="img" if it is decorative.', 'msacalab-accessibility-checker' ),
		);
		$e['svg-img-alt'] = array(
			'title' => __( 'SVG images have no description', 'msacalab-accessibility-checker' ),
			'who'   => __( 'Screen reader users', 'msacalab-accessibility-checker' ),
			'why'   => __( 'Icons and illustrations drawn as SVG need a text alternative just like regular images.', 'msacalab-accessibility-checker' ),
			'fix'   => __( 'Add a <title> inside the SVG or an aria-label on it. If the icon is decorative, add aria-hidden="true" instead.', 'msacalab-accessibility-checker' ),
		);
		$e['input-image-alt'] = array(
			'title' => __( 'Image buttons have no text alternative', 'msacalab-accessibility-checker' ),
			'who'   => __( 'Screen reader users', 'msacalab-accessibility-checker' ),
			'why'   => __( 'A button made from an image needs alt text that says what the button does, or users won\'t know what will happen when they press it.', 'msacalab-accessibility-checker' ),
			'fix'   => __( 'Add alt text describing the action, for example "Search" or "Submit order".', 'msacalab-accessibility-checker' ),
		);
		$e['area-alt'] = array(
			'title' => __( 'Clickable areas of an image map have no text', 'msacalab-accessibility-checker' ),
			'who'   => __( 'Screen reader users', 'msacalab-accessibility-checker' ),
			'why'   => __( 'Each clickable area works like a link and needs text saying where it goes.', 'msacalab-accessibility-checker' ),
			'fix'   => __( 'Add alt text to every <area> element describing its destination.', 'msacalab-accessibility-checker' ),
		);
		$e['object-alt'] = array(
			'title' => __( 'Embedded objects have no text alternative', 'msacalab-accessibility-checker' ),
			'who'   => __( 'Screen reader users', 'msacalab-accessibility-checker' ),
			'why'   => __( 'Embedded content such as old plugins or documents needs a description so everyone knows what it contains.', 'msacalab-accessibility-checker' ),
			'fix'   => __( 'Add an aria-label or title to the <object>, or provide the content another way.', 'msacalab-accessibility-checker' ),
		);
		$e['color-contrast'] = array(
			'title' => __( 'Text is hard to read against its background', 'msacalab-accessibility-checker' ),
			'who'   => __( 'People with low vision or color blindness, older visitors, anyone reading on a phone in sunlight', 'msacalab-accessibility-checker' ),
			'why'   => __( 'When text and background colors are too similar, the text fades out. WCAG asks for a contrast ratio of at least 4.5:1 for normal text and 3:1 for large text.', 'msacalab-accessibility-checker' ),
			'fix'   => __( 'Make the text darker or the background lighter (or the opposite on dark designs) until the ratio passes. You can usually change these colors in your theme\'s style settings or the block\'s color settings.', 'msacalab-accessibility-checker' ),
		);
		$e['link-in-text-block'] = array(
			'title' => __( 'Links in text can only be spotted by color', 'msacalab-accessibility-checker' ),
			'who'   => __( 'People with color blindness or low vision', 'msacalab-accessibility-checker' ),
			'why'   => __( 'If a link looks just like the surrounding text except for its color, some visitors can\'t tell it\'s a link.', 'msacalab-accessibility-checker' ),
			'fix'   => __( 'Underline links in body text, or give them enough contrast with the surrounding text plus a visible change on hover and focus.', 'msacalab-accessibility-checker' ),
		);
		$e['link-name'] = array(
			'title' => __( 'Links don\'t say where they go', 'msacalab-accessibility-checker' ),
			'who'   => __( 'Screen reader and voice control users', 'msacalab-accessibility-checker' ),
			'why'   => __( 'This link has no readable text, often because it only contains an icon or an image without alt text. Screen readers announce it as just "link", and voice users can\'t say its name to click it.', 'msacalab-accessibility-checker' ),
			'fix'   => __( 'Add visible link text, or alt text to the image inside the link, or an aria-label such as "Read more about our services".', 'msacalab-accessibility-checker' ),
		);
		$e['button-name'] = array(
			'title' => __( 'Buttons have no name', 'msacalab-accessibility-checker' ),
			'who'   => __( 'Screen reader and voice control users', 'msacalab-accessibility-checker' ),
			'why'   => __( 'A button with only an icon, or nothing at all, is announced as just "button". Visitors can\'t tell what it does.', 'msacalab-accessibility-checker' ),
			'fix'   => __( 'Give the button visible text, or an aria-label that describes its action, such as "Open menu" or "Search".', 'msacalab-accessibility-checker' ),
		);
		$e['input-button-name'] = $e['button-name'];
		$e['label'] = array(
			'title' => __( 'Form fields have no label', 'msacalab-accessibility-checker' ),
			'who'   => __( 'Screen reader users, people with cognitive disabilities, everyone filling in forms', 'msacalab-accessibility-checker' ),
			'why'   => __( 'Without a label, screen readers can\'t say what a field is for. Placeholder text alone disappears as soon as you type.', 'msacalab-accessibility-checker' ),
			'fix'   => __( 'Add a visible <label> connected to each field. Most form plugins have a "label" setting; make sure it isn\'t hidden or empty.', 'msacalab-accessibility-checker' ),
		);
		$e['select-name'] = array(
			'title' => __( 'Dropdown menus have no label', 'msacalab-accessibility-checker' ),
			'who'   => __( 'Screen reader users', 'msacalab-accessibility-checker' ),
			'why'   => __( 'Screen readers can\'t say what a dropdown is for when it has no label.', 'msacalab-accessibility-checker' ),
			'fix'   => __( 'Add a visible label connected to the dropdown.', 'msacalab-accessibility-checker' ),
		);
		$e['form-field-multiple-labels'] = array(
			'title' => __( 'Form fields have more than one label', 'msacalab-accessibility-checker' ),
			'who'   => __( 'Screen reader users', 'msacalab-accessibility-checker' ),
			'why'   => __( 'Different screen readers pick different labels when a field has several, so some users hear the wrong one.', 'msacalab-accessibility-checker' ),
			'fix'   => __( 'Keep one label per field and remove the extra ones.', 'msacalab-accessibility-checker' ),
		);
		$e['autocomplete-valid'] = array(
			'title' => __( 'Form fields use an invalid autofill hint', 'msacalab-accessibility-checker' ),
			'who'   => __( 'People with motor or memory difficulties who rely on autofill', 'msacalab-accessibility-checker' ),
			'why'   => __( 'Correct autocomplete values let browsers fill in names, emails and addresses automatically. A wrong value breaks that help.', 'msacalab-accessibility-checker' ),
			'fix'   => __( 'Use a standard autocomplete value such as "email", "name" or "postal-code", or remove the attribute.', 'msacalab-accessibility-checker' ),
		);
		$e['html-has-lang'] = array(
			'title' => __( 'The page doesn\'t say what language it is in', 'msacalab-accessibility-checker' ),
			'who'   => __( 'Screen reader users; translation tools', 'msacalab-accessibility-checker' ),
			'why'   => __( 'Screen readers use the page language to pick the right voice and pronunciation. Without it, text may be read with the wrong accent and become hard to understand.', 'msacalab-accessibility-checker' ),
			'fix'   => __( 'Make sure your theme prints the language on the <html> tag. In WordPress this comes from Settings > General > Site Language and the theme\'s language_attributes() call.', 'msacalab-accessibility-checker' ),
		);
		$e['html-lang-valid'] = array(
			'title' => __( 'The page language code is not valid', 'msacalab-accessibility-checker' ),
			'who'   => __( 'Screen reader users', 'msacalab-accessibility-checker' ),
			'why'   => __( 'An unrecognized language code means screen readers can\'t choose the right pronunciation.', 'msacalab-accessibility-checker' ),
			'fix'   => __( 'Use a valid code like "en", "en-US" or "ur". Check Settings > General > Site Language.', 'msacalab-accessibility-checker' ),
		);
		$e['valid-lang'] = array(
			'title' => __( 'A section uses an invalid language code', 'msacalab-accessibility-checker' ),
			'who'   => __( 'Screen reader users', 'msacalab-accessibility-checker' ),
			'why'   => __( 'Parts of a page can be marked as another language, but the code must be valid for screen readers to switch voices.', 'msacalab-accessibility-checker' ),
			'fix'   => __( 'Correct the lang attribute on that element to a valid language code.', 'msacalab-accessibility-checker' ),
		);
		$e['html-xml-lang-mismatch'] = array(
			'title' => __( 'The page declares two different languages', 'msacalab-accessibility-checker' ),
			'who'   => __( 'Screen reader users', 'msacalab-accessibility-checker' ),
			'why'   => __( 'The lang and xml:lang attributes disagree, so assistive technology may pick the wrong one.', 'msacalab-accessibility-checker' ),
			'fix'   => __( 'Make both attributes match, or remove xml:lang.', 'msacalab-accessibility-checker' ),
		);
		$e['document-title'] = array(
			'title' => __( 'The page has no title', 'msacalab-accessibility-checker' ),
			'who'   => __( 'Screen reader users, people with many tabs open, search engines', 'msacalab-accessibility-checker' ),
			'why'   => __( 'The page title is the first thing a screen reader announces and what appears in the browser tab. Without it, visitors can\'t tell pages apart.', 'msacalab-accessibility-checker' ),
			'fix'   => __( 'Make sure your theme supports "title-tag" and every page has a descriptive title.', 'msacalab-accessibility-checker' ),
		);
		$e['bypass'] = array(
			'title' => __( 'There is no way to skip straight to the main content', 'msacalab-accessibility-checker' ),
			'who'   => __( 'Keyboard and screen reader users', 'msacalab-accessibility-checker' ),
			'why'   => __( 'Without a "Skip to content" link or proper page landmarks, keyboard users must tab through the whole menu on every single page.', 'msacalab-accessibility-checker' ),
			'fix'   => __( 'Add a "Skip to content" link as the first focusable item, and wrap the main content in a <main> element. Many themes include this option.', 'msacalab-accessibility-checker' ),
		);
		$e['meta-viewport'] = array(
			'title' => __( 'Zooming is blocked on mobile', 'msacalab-accessibility-checker' ),
			'who'   => __( 'People with low vision on phones and tablets', 'msacalab-accessibility-checker' ),
			'why'   => __( 'The page stops visitors from pinch-zooming, so people who need larger text can\'t enlarge it.', 'msacalab-accessibility-checker' ),
			'fix'   => __( 'Remove "user-scalable=no" and any "maximum-scale" below 2 from the viewport meta tag in your theme.', 'msacalab-accessibility-checker' ),
		);
		$e['meta-refresh'] = array(
			'title' => __( 'The page refreshes or redirects on a timer', 'msacalab-accessibility-checker' ),
			'who'   => __( 'Screen reader users, people who read slowly', 'msacalab-accessibility-checker' ),
			'why'   => __( 'Automatic refreshes can move people away before they finish reading and reset their place on the page.', 'msacalab-accessibility-checker' ),
			'fix'   => __( 'Remove the timed refresh, or use a server redirect instead.', 'msacalab-accessibility-checker' ),
		);
		$e['list'] = array(
			'title' => __( 'Lists contain items that aren\'t list items', 'msacalab-accessibility-checker' ),
			'who'   => __( 'Screen reader users', 'msacalab-accessibility-checker' ),
			'why'   => __( 'Screen readers announce how many items a list has and let users jump between them. Other elements placed directly inside the list break that structure.', 'msacalab-accessibility-checker' ),
			'fix'   => __( 'Make sure <ul> and <ol> contain only <li> elements directly. This often comes from a theme or menu plugin, so you may need to ask its developer.', 'msacalab-accessibility-checker' ),
		);
		$e['listitem'] = array(
			'title' => __( 'List items are placed outside a list', 'msacalab-accessibility-checker' ),
			'who'   => __( 'Screen reader users', 'msacalab-accessibility-checker' ),
			'why'   => __( 'A list item outside a list isn\'t announced as part of a group, so the structure is lost.', 'msacalab-accessibility-checker' ),
			'fix'   => __( 'Wrap <li> elements in a <ul> or <ol>.', 'msacalab-accessibility-checker' ),
		);
		$e['definition-list'] = array(
			'title' => __( 'Definition lists are structured incorrectly', 'msacalab-accessibility-checker' ),
			'who'   => __( 'Screen reader users', 'msacalab-accessibility-checker' ),
			'why'   => __( 'Terms and definitions must be paired correctly to be announced together.', 'msacalab-accessibility-checker' ),
			'fix'   => __( 'Make sure <dl> contains only <dt> and <dd> groups.', 'msacalab-accessibility-checker' ),
		);
		$e['dlitem'] = $e['definition-list'];
		$e['frame-title'] = array(
			'title' => __( 'Embedded content (iframes) has no title', 'msacalab-accessibility-checker' ),
			'who'   => __( 'Screen reader users', 'msacalab-accessibility-checker' ),
			'why'   => __( 'Videos, maps and widgets embedded in frames need a title so visitors know what the embedded area contains before entering it.', 'msacalab-accessibility-checker' ),
			'fix'   => __( 'Add a title attribute to the iframe, for example title="Map of our office location".', 'msacalab-accessibility-checker' ),
		);
		$e['frame-title-unique'] = array(
			'title' => __( 'Several embedded frames share the same title', 'msacalab-accessibility-checker' ),
			'who'   => __( 'Screen reader users', 'msacalab-accessibility-checker' ),
			'why'   => __( 'Identical titles make it impossible to tell embedded areas apart.', 'msacalab-accessibility-checker' ),
			'fix'   => __( 'Give each iframe a unique, descriptive title.', 'msacalab-accessibility-checker' ),
		);
		$e['frame-focusable-content'] = array(
			'title' => __( 'Interactive content inside a frame can\'t be reached by keyboard', 'msacalab-accessibility-checker' ),
			'who'   => __( 'Keyboard users', 'msacalab-accessibility-checker' ),
			'why'   => __( 'The frame is removed from the tab order but contains things you can click, so keyboard users can\'t reach them.', 'msacalab-accessibility-checker' ),
			'fix'   => __( 'Remove tabindex="-1" from the iframe.', 'msacalab-accessibility-checker' ),
		);
		$e['video-caption'] = array(
			'title' => __( 'Videos have no captions', 'msacalab-accessibility-checker' ),
			'who'   => __( 'Deaf and hard-of-hearing visitors; anyone watching with the sound off', 'msacalab-accessibility-checker' ),
			'why'   => __( 'Without captions, everything said in the video is lost for people who can\'t hear it.', 'msacalab-accessibility-checker' ),
			'fix'   => __( 'Add a captions track to the video (a .vtt file), or use a video host such as YouTube that supports edited captions.', 'msacalab-accessibility-checker' ),
		);
		$e['no-autoplay-audio'] = array(
			'title' => __( 'Audio plays automatically', 'msacalab-accessibility-checker' ),
			'who'   => __( 'Screen reader users, people with attention or anxiety conditions', 'msacalab-accessibility-checker' ),
			'why'   => __( 'Sound that starts on its own can drown out a screen reader, and visitors may not find how to stop it.', 'msacalab-accessibility-checker' ),
			'fix'   => __( 'Turn off autoplay, or make sure it stops within 3 seconds and has a clear pause control.', 'msacalab-accessibility-checker' ),
		);
		$e['nested-interactive'] = array(
			'title' => __( 'Clickable elements are placed inside other clickable elements', 'msacalab-accessibility-checker' ),
			'who'   => __( 'Screen reader and keyboard users', 'msacalab-accessibility-checker' ),
			'why'   => __( 'A button inside a link (or similar) confuses assistive technology, and the inner control may never be announced or reachable.', 'msacalab-accessibility-checker' ),
			'fix'   => __( 'Restructure the element so links and buttons are not nested inside each other.', 'msacalab-accessibility-checker' ),
		);
		$e['scrollable-region-focusable'] = array(
			'title' => __( 'Scrollable areas can\'t be scrolled with a keyboard', 'msacalab-accessibility-checker' ),
			'who'   => __( 'Keyboard users', 'msacalab-accessibility-checker' ),
			'why'   => __( 'Content in a scrolling box is out of reach if keyboard users can\'t move focus into the box.', 'msacalab-accessibility-checker' ),
			'fix'   => __( 'Add tabindex="0" and an accessible name to the scrollable area, or make sure it contains a focusable element.', 'msacalab-accessibility-checker' ),
		);
		$e['target-size'] = array(
			'title' => __( 'Buttons or links are too small to tap easily', 'msacalab-accessibility-checker' ),
			'who'   => __( 'People with tremors or limited dexterity, everyone on a phone', 'msacalab-accessibility-checker' ),
			'why'   => __( 'Tiny targets packed close together lead to mis-taps. WCAG 2.2 asks for at least 24 by 24 pixels, or enough space around smaller targets.', 'msacalab-accessibility-checker' ),
			'fix'   => __( 'Increase the size or padding of the link or button, or add more space between neighbouring targets.', 'msacalab-accessibility-checker' ),
		);
		$e['td-headers-attr'] = array(
			'title' => __( 'Table cells point to headers that don\'t exist', 'msacalab-accessibility-checker' ),
			'who'   => __( 'Screen reader users', 'msacalab-accessibility-checker' ),
			'why'   => __( 'Screen readers announce each cell\'s headers. Broken references mean cells are read without context.', 'msacalab-accessibility-checker' ),
			'fix'   => __( 'Make sure every id in a cell\'s headers attribute matches a header cell in the same table.', 'msacalab-accessibility-checker' ),
		);
		$e['th-has-data-cells'] = array(
			'title' => __( 'Table headers don\'t describe any data', 'msacalab-accessibility-checker' ),
			'who'   => __( 'Screen reader users', 'msacalab-accessibility-checker' ),
			'why'   => __( 'Header cells that don\'t relate to any data cells make the table structure confusing.', 'msacalab-accessibility-checker' ),
			'fix'   => __( 'Use header cells only for real row or column headings.', 'msacalab-accessibility-checker' ),
		);
		$e['duplicate-id-aria'] = array(
			'title' => __( 'Elements referenced by labels share the same id', 'msacalab-accessibility-checker' ),
			'who'   => __( 'Screen reader users', 'msacalab-accessibility-checker' ),
			'why'   => __( 'When two elements have the same id, labels and descriptions may point to the wrong one.', 'msacalab-accessibility-checker' ),
			'fix'   => __( 'Give every element a unique id.', 'msacalab-accessibility-checker' ),
		);
		$e['aria-hidden-focus'] = array(
			'title' => __( 'Hidden elements can still receive keyboard focus', 'msacalab-accessibility-checker' ),
			'who'   => __( 'Screen reader and keyboard users', 'msacalab-accessibility-checker' ),
			'why'   => __( 'The element is hidden from screen readers but can still be tabbed to, so users land on something that is announced as nothing.', 'msacalab-accessibility-checker' ),
			'fix'   => __( 'Remove aria-hidden, or make the focusable items inside it unfocusable (for example with the inert attribute).', 'msacalab-accessibility-checker' ),
		);
		$e['aria-hidden-body'] = array(
			'title' => __( 'The whole page is hidden from screen readers', 'msacalab-accessibility-checker' ),
			'who'   => __( 'Screen reader users', 'msacalab-accessibility-checker' ),
			'why'   => __( 'aria-hidden on the page body makes the entire site invisible to assistive technology.', 'msacalab-accessibility-checker' ),
			'fix'   => __( 'Remove aria-hidden="true" from the <body> element. This is often left behind by a pop-up or menu script.', 'msacalab-accessibility-checker' ),
		);
		$e['server-side-image-map'] = array(
			'title' => __( 'Server-side image maps are used', 'msacalab-accessibility-checker' ),
			'who'   => __( 'Keyboard and screen reader users', 'msacalab-accessibility-checker' ),
			'why'   => __( 'Server-side image maps only work with a mouse.', 'msacalab-accessibility-checker' ),
			'fix'   => __( 'Replace it with regular links or a client-side image map with alt text.', 'msacalab-accessibility-checker' ),
		);
		$e['blink'] = array(
			'title' => __( 'Blinking content is used', 'msacalab-accessibility-checker' ),
			'who'   => __( 'People with attention or vision conditions', 'msacalab-accessibility-checker' ),
			'why'   => __( 'Blinking text is distracting and hard to read, and can\'t be paused.', 'msacalab-accessibility-checker' ),
			'fix'   => __( 'Remove the <blink> element.', 'msacalab-accessibility-checker' ),
		);
		$e['marquee'] = array(
			'title' => __( 'Scrolling text (marquee) is used', 'msacalab-accessibility-checker' ),
			'who'   => __( 'People with attention, reading or vision conditions', 'msacalab-accessibility-checker' ),
			'why'   => __( 'Moving text is hard to read and can\'t be paused.', 'msacalab-accessibility-checker' ),
			'fix'   => __( 'Replace the <marquee> with static text.', 'msacalab-accessibility-checker' ),
		);
		$e['summary-name'] = array(
			'title' => __( 'Expandable sections have no name', 'msacalab-accessibility-checker' ),
			'who'   => __( 'Screen reader users', 'msacalab-accessibility-checker' ),
			'why'   => __( 'The toggle for an expandable section has no text, so visitors don\'t know what it opens.', 'msacalab-accessibility-checker' ),
			'fix'   => __( 'Add text inside the <summary> element.', 'msacalab-accessibility-checker' ),
		);

		// Best-practice suggestions: not strict WCAG failures, never counted in the score.
		$e['empty-heading']        = array(
			'title' => __( 'Headings with no text', 'msacalab-accessibility-checker' ),
			'who'   => __( 'Screen reader users, who often move around a page by its headings', 'msacalab-accessibility-checker' ),
			'why'   => __( 'An empty heading is announced as just "heading", which is confusing and wastes time.', 'msacalab-accessibility-checker' ),
			'fix'   => __( 'Add text to the heading, or remove it if it is only used for spacing. In the block editor, look for empty Heading blocks.', 'msacalab-accessibility-checker' ),
		);
		$e['heading-order']        = array(
			'title' => __( 'Heading levels are skipped', 'msacalab-accessibility-checker' ),
			'who'   => __( 'Screen reader users and anyone scanning the page', 'msacalab-accessibility-checker' ),
			'why'   => __( 'Headings form an outline of the page. Jumping from a level 2 straight to a level 4 suggests content is missing.', 'msacalab-accessibility-checker' ),
			'fix'   => __( 'Use heading levels in order (H2, then H3 inside it). Choose the level for its place in the outline, and change the size with styles instead.', 'msacalab-accessibility-checker' ),
		);
		$e['empty-table-header']   = array(
			'title' => __( 'Table headers with no text', 'msacalab-accessibility-checker' ),
			'who'   => __( 'Screen reader users', 'msacalab-accessibility-checker' ),
			'why'   => __( 'Screen readers read the column or row header before each cell. An empty header leaves those cells without context.', 'msacalab-accessibility-checker' ),
			'fix'   => __( 'Add a short label to every header cell, or make the cell a normal data cell if it is not a header.', 'msacalab-accessibility-checker' ),
		);
		$e['image-redundant-alt']  = array(
			'title' => __( 'Image alt text repeats the text next to it', 'msacalab-accessibility-checker' ),
			'who'   => __( 'Screen reader users', 'msacalab-accessibility-checker' ),
			'why'   => __( 'When an image\'s alt text repeats the link or caption text beside it, screen readers say the same thing twice.', 'msacalab-accessibility-checker' ),
			'fix'   => __( 'Leave the alt text empty if the nearby text already says it, or describe something the text doesn\'t.', 'msacalab-accessibility-checker' ),
		);
		$e['tabindex']             = array(
			'title' => __( 'Keyboard order is forced with tabindex', 'msacalab-accessibility-checker' ),
			'who'   => __( 'Keyboard and screen reader users', 'msacalab-accessibility-checker' ),
			'why'   => __( 'A tabindex above 0 makes those elements jump to the front of the keyboard order, so focus moves around the page unpredictably.', 'msacalab-accessibility-checker' ),
			'fix'   => __( 'Remove tabindex values above 0 and arrange the content in a logical order instead. This often comes from a theme or plugin.', 'msacalab-accessibility-checker' ),
		);
		$e['aria-allowed-role']    = array(
			'title' => __( 'An element has a role that doesn\'t suit it', 'msacalab-accessibility-checker' ),
			'who'   => __( 'Screen reader users', 'msacalab-accessibility-checker' ),
			'why'   => __( 'A role tells screen readers what something is. A role that conflicts with the element, such as an image marked decorative that still has a description, sends mixed signals.', 'msacalab-accessibility-checker' ),
			'fix'   => __( 'Remove the role attribute, or use an element that matches what it does. For decorative images, use empty alt text.', 'msacalab-accessibility-checker' ),
		);
		$e['page-has-heading-one'] = array(
			'title' => __( 'Page has no main heading (H1)', 'msacalab-accessibility-checker' ),
			'who'   => __( 'Screen reader users and search engines', 'msacalab-accessibility-checker' ),
			'why'   => __( 'The main heading tells visitors what the page is about, and many screen reader users jump to it first.', 'msacalab-accessibility-checker' ),
			'fix'   => __( 'Give each page one H1 heading, usually the page title. Some themes hide it on the home page; check your theme settings.', 'msacalab-accessibility-checker' ),
		);

		// MSACAlab Accessibility Checker's own checks and curated IBM Equal Access rules.
		$e['corea11y-link-symbol-only'] = array(
			'title' => __( 'Links with only a symbol as text', 'msacalab-accessibility-checker' ),
			'who'   => __( 'Screen reader users', 'msacalab-accessibility-checker' ),
			'why'   => __( 'A link whose text is just a symbol such as "»" or "." is announced as that symbol, so people can\'t tell where it goes.', 'msacalab-accessibility-checker' ),
			'fix'   => __( 'Add words that describe the destination, for example "Next page". If you keep the symbol, add visually hidden text or an aria-label.', 'msacalab-accessibility-checker' ),
		);
		$e['corea11y-link-generic'] = array(
			'title' => __( 'Link text like "Click here" or "Read more"', 'msacalab-accessibility-checker' ),
			'who'   => __( 'Screen reader users, who often list all links on a page', 'msacalab-accessibility-checker' ),
			'why'   => __( 'Out of context, "Read more" or "Click here" doesn\'t say where a link goes. Screen reader users often browse a list of links on their own.', 'msacalab-accessibility-checker' ),
			'fix'   => __( 'Make the link text describe the destination, for example "Read more about our opening hours". You can hide the extra words visually if needed.', 'msacalab-accessibility-checker' ),
		);
		$e['corea11y-link-no-destination'] = array(
			'title' => __( 'Links that don\'t go anywhere', 'msacalab-accessibility-checker' ),
			'who'   => __( 'Keyboard and screen reader users', 'msacalab-accessibility-checker' ),
			'why'   => __( 'A link with href="#" or a javascript: address usually acts as a button. Screen readers announce it as a link, and it may not work with the Space key.', 'msacalab-accessibility-checker' ),
			'fix'   => __( 'If it opens or controls something on the page, use a <button>. If it should go somewhere, give it a real address.', 'msacalab-accessibility-checker' ),
		);
		$e['corea11y-link-file-type'] = array(
			'title' => __( 'File links don\'t say the file type', 'msacalab-accessibility-checker' ),
			'who'   => __( 'Everyone, especially screen reader and mobile users', 'msacalab-accessibility-checker' ),
			'why'   => __( 'People expect a link to open a web page. A PDF or Word file can be slow to download and hard to use on some devices.', 'msacalab-accessibility-checker' ),
			'fix'   => __( 'Add the file type (and ideally the size) to the link text, for example "Annual report (PDF, 2 MB)".', 'msacalab-accessibility-checker' ),
		);
		$e['corea11y-media-link'] = array(
			'title' => __( 'Audio or video files may need a transcript', 'msacalab-accessibility-checker' ),
			'who'   => __( 'People who are deaf or hard of hearing', 'msacalab-accessibility-checker' ),
			'why'   => __( 'Audio and video content needs a text alternative so everyone can get the information.', 'msacalab-accessibility-checker' ),
			'fix'   => __( 'Provide a transcript next to the link, or captions for video.', 'msacalab-accessibility-checker' ),
		);
		$e['corea11y-link-new-window'] = array(
			'title' => __( 'Links open a new tab without warning', 'msacalab-accessibility-checker' ),
			'who'   => __( 'Screen reader, keyboard and older users', 'msacalab-accessibility-checker' ),
			'why'   => __( 'A new tab or window opening unexpectedly can be disorienting, and the Back button no longer works.', 'msacalab-accessibility-checker' ),
			'fix'   => __( 'Avoid opening new tabs where possible. If you must, add "(opens in a new tab)" to the link text or as visually hidden text.', 'msacalab-accessibility-checker' ),
		);
		$e['corea11y-link-title-repeat'] = array(
			'title' => __( 'Link title repeats the link text', 'msacalab-accessibility-checker' ),
			'who'   => __( 'Screen reader users', 'msacalab-accessibility-checker' ),
			'why'   => __( 'A title attribute that repeats the link text adds nothing and may be read twice.', 'msacalab-accessibility-checker' ),
			'fix'   => __( 'Remove the title attribute, or use it only for extra information that is also available another way.', 'msacalab-accessibility-checker' ),
		);
		$e['corea11y-link-adjacent-duplicate'] = array(
			'title' => __( 'Two links side by side go to the same place', 'msacalab-accessibility-checker' ),
			'who'   => __( 'Keyboard and screen reader users', 'msacalab-accessibility-checker' ),
			'why'   => __( 'An image link and a text link next to each other that go to the same page mean two tab stops and the same link announced twice.', 'msacalab-accessibility-checker' ),
			'fix'   => __( 'Wrap the image and the text in one link.', 'msacalab-accessibility-checker' ),
		);
		$e['corea11y-link-same-text'] = array(
			'title' => __( 'Links with the same text go to different pages', 'msacalab-accessibility-checker' ),
			'who'   => __( 'Screen reader users', 'msacalab-accessibility-checker' ),
			'why'   => __( 'When several links have identical text but different destinations, people can\'t tell them apart in a list of links.', 'msacalab-accessibility-checker' ),
			'fix'   => __( 'Make each link\'s text unique, for example by adding the item name.', 'msacalab-accessibility-checker' ),
		);
		$e['corea11y-placeholder-label'] = array(
			'title' => __( 'Fields use placeholder text instead of a label', 'msacalab-accessibility-checker' ),
			'who'   => __( 'People with memory or cognitive disabilities, and screen reader users', 'msacalab-accessibility-checker' ),
			'why'   => __( 'Placeholder text disappears when someone starts typing, and it is often low contrast. People then forget what the field was for.', 'msacalab-accessibility-checker' ),
			'fix'   => __( 'Add a visible label to the field. Keep the placeholder only for an example, if at all.', 'msacalab-accessibility-checker' ),
		);
		$e['corea11y-hidden-label'] = array(
			'title' => __( 'Form labels are hidden from everyone', 'msacalab-accessibility-checker' ),
			'who'   => __( 'Screen reader and voice control users', 'msacalab-accessibility-checker' ),
			'why'   => __( 'The field has a label, but the theme hides it completely (display: none). Some screen readers then announce just "edit text", and people can\'t tell what to type.', 'msacalab-accessibility-checker' ),
			'fix'   => __( 'Show the label, or hide it only visually with a "screen reader text" class instead of display: none. MSACAlab Accessibility Checker Pro can do this in one click.', 'msacalab-accessibility-checker' ),
		);
		$e['corea11y-skip-link-broken'] = array(
			'title' => __( 'The "Skip to content" link goes nowhere', 'msacalab-accessibility-checker' ),
			'who'   => __( 'Keyboard and screen reader users', 'msacalab-accessibility-checker' ),
			'why'   => __( 'A skip link lets keyboard users jump past the menu. When its target is missing, pressing it does nothing and they have to tab through the whole menu.', 'msacalab-accessibility-checker' ),
			'fix'   => __( 'Point the link at the id of your main content area (for example #main), or give that area the id the link uses.', 'msacalab-accessibility-checker' ),
		);
		$e['corea11y-link-anchor-missing'] = array(
			'title' => __( 'A link to a spot on the page goes nowhere', 'msacalab-accessibility-checker' ),
			'who'   => __( 'Keyboard and screen reader users', 'msacalab-accessibility-checker' ),
			'why'   => __( 'The link points to a part of the page that doesn\'t exist, so following it does nothing.', 'msacalab-accessibility-checker' ),
			'fix'   => __( 'Fix the link\'s #target, or add the matching id to the section it should jump to.', 'msacalab-accessibility-checker' ),
		);
		$e['corea11y-label-orphaned'] = array(
			'title' => __( 'A form label isn\'t connected to any field', 'msacalab-accessibility-checker' ),
			'who'   => __( 'Screen reader and voice control users', 'msacalab-accessibility-checker' ),
			'why'   => __( 'The label\'s "for" value doesn\'t match any field\'s id, so the label isn\'t read with the field and clicking it doesn\'t focus the field.', 'msacalab-accessibility-checker' ),
			'fix'   => __( 'Make the label\'s "for" match the field\'s id.', 'msacalab-accessibility-checker' ),
		);
		$e['corea11y-label-empty'] = array(
			'title' => __( 'A form label is empty', 'msacalab-accessibility-checker' ),
			'who'   => __( 'Screen reader users', 'msacalab-accessibility-checker' ),
			'why'   => __( 'The field has a name from somewhere else, but an empty label element can still be announced as blank by some assistive technology.', 'msacalab-accessibility-checker' ),
			'fix'   => __( 'Put the field\'s name in the label, or remove the empty label.', 'msacalab-accessibility-checker' ),
		);
		$e['corea11y-alt-long'] = array(
			'title' => __( 'Very long alt text', 'msacalab-accessibility-checker' ),
			'who'   => __( 'Screen reader users', 'msacalab-accessibility-checker' ),
			'why'   => __( 'Long alt text is read in one go and can\'t be paused or skimmed, which makes the page tiring to listen to.', 'msacalab-accessibility-checker' ),
			'fix'   => __( 'Keep alt text short, usually under 125 characters. Put detailed descriptions in the page text or a caption.', 'msacalab-accessibility-checker' ),
		);
		$e['corea11y-image-title-only'] = array(
			'title' => __( 'An image is described only by a tooltip', 'msacalab-accessibility-checker' ),
			'who'   => __( 'Screen reader, voice control and keyboard users', 'msacalab-accessibility-checker' ),
			'why'   => __( 'A title attribute shows as a tooltip on mouse hover only, and many screen readers don\'t announce it.', 'msacalab-accessibility-checker' ),
			'fix'   => __( 'Give the image alt text. You can do this from the Alt text screen.', 'msacalab-accessibility-checker' ),
		);
		$e['corea11y-image-title-repeat'] = array(
			'title' => __( 'Image title repeats the alt text', 'msacalab-accessibility-checker' ),
			'who'   => __( 'Screen reader users', 'msacalab-accessibility-checker' ),
			'why'   => __( 'When the title and alt text are the same, some screen readers read the description twice.', 'msacalab-accessibility-checker' ),
			'fix'   => __( 'Remove the title attribute.', 'msacalab-accessibility-checker' ),
		);
		$e['corea11y-alt-duplicate'] = array(
			'title' => __( 'Images next to each other have the same alt text', 'msacalab-accessibility-checker' ),
			'who'   => __( 'Screen reader users', 'msacalab-accessibility-checker' ),
			'why'   => __( 'Hearing the same description twice in a row is confusing: people can\'t tell whether the images differ.', 'msacalab-accessibility-checker' ),
			'fix'   => __( 'Describe each image by what it shows. If one is purely decorative, mark it as decorative on the Alt text screen.', 'msacalab-accessibility-checker' ),
		);
		$e['corea11y-underline-text'] = array(
			'title' => __( 'Underlined text looks like a link', 'msacalab-accessibility-checker' ),
			'who'   => __( 'Everyone, especially people with cognitive disabilities', 'msacalab-accessibility-checker' ),
			'why'   => __( 'On the web, underlined text means "link". People click it and nothing happens.', 'msacalab-accessibility-checker' ),
			'fix'   => __( 'Use bold or italics for emphasis, and keep underlines for links.', 'msacalab-accessibility-checker' ),
		);
		$e['corea11y-no-landmarks'] = array(
			'title' => __( 'The page has no regions', 'msacalab-accessibility-checker' ),
			'who'   => __( 'Screen reader users', 'msacalab-accessibility-checker' ),
			'why'   => __( 'Regions (header, navigation, main content, footer) let screen reader users jump straight to the part they want.', 'msacalab-accessibility-checker' ),
			'fix'   => __( 'Use your theme\'s header, main and footer areas, or add HTML landmarks. MSACAlab Accessibility Checker Pro\'s "Mark the main content area" fix can add the main region when your theme has a recognisable content area.', 'msacalab-accessibility-checker' ),
		);
		$e['corea11y-title-uninformative'] = array(
			'title' => __( 'The page title doesn\'t describe the page', 'msacalab-accessibility-checker' ),
			'who'   => __( 'Screen reader users, and everyone using tabs or bookmarks', 'msacalab-accessibility-checker' ),
			'why'   => __( 'The page title is read first by screen readers and shown in browser tabs. A generic title like "Untitled" doesn\'t say where you are.', 'msacalab-accessibility-checker' ),
			'fix'   => __( 'Give the page a title that describes it, for example "Contact us | Harbour Bakery".', 'msacalab-accessibility-checker' ),
		);
		$e['corea11y-longdesc-invalid'] = array(
			'title' => __( 'An image\'s long description link is broken', 'msacalab-accessibility-checker' ),
			'who'   => __( 'Screen reader users', 'msacalab-accessibility-checker' ),
			'why'   => __( 'The longdesc attribute should point to a page that describes a complex image, but its value isn\'t an address.', 'msacalab-accessibility-checker' ),
			'fix'   => __( 'Change longdesc to the address of the description page, or remove it and describe the image in the page text.', 'msacalab-accessibility-checker' ),
		);
		$e['corea11y-video-captions'] = array(
			'title' => __( 'Check that this video has captions', 'msacalab-accessibility-checker' ),
			'who'   => __( 'Deaf and hard of hearing visitors, and anyone watching without sound', 'msacalab-accessibility-checker' ),
			'why'   => __( 'Speech in a video needs accurate captions. Software can\'t tell whether captions exist or are correct, so this needs a person to check.', 'msacalab-accessibility-checker' ),
			'fix'   => __( 'Play the video with captions on. For YouTube, upload a caption file or correct the automatic captions in YouTube Studio.', 'msacalab-accessibility-checker' ),
		);
		$e['label-title-only'] = array(
			'title' => __( 'A field is labelled only by a tooltip', 'msacalab-accessibility-checker' ),
			'who'   => __( 'Screen reader, voice control and keyboard users', 'msacalab-accessibility-checker' ),
			'why'   => __( 'The field\'s only name is its title attribute, which appears as a tooltip on mouse hover and isn\'t shown to most visitors.', 'msacalab-accessibility-checker' ),
			'fix'   => __( 'Add a visible label to the field.', 'msacalab-accessibility-checker' ),
		);
		$e['corea11y-icon-contrast'] = array(
			'title' => __( 'Icons are hard to see against their background', 'msacalab-accessibility-checker' ),
			'who'   => __( 'People with low vision or color blindness, and anyone in bright light', 'msacalab-accessibility-checker' ),
			'why'   => __( 'Icons that carry meaning, such as a phone or email symbol, need a contrast of at least 3:1 with their background so people can make them out.', 'msacalab-accessibility-checker' ),
			'fix'   => __( 'Make the icon darker or its background lighter (or the reverse). MSACAlab Accessibility Checker Pro suggests a colour on the Colour contrast screen. If the icon is purely decorative and its meaning is also given in text, it can stay as it is.', 'msacalab-accessibility-checker' ),
		);
		$e['corea11y-table-no-headers'] = array(
			'title' => __( 'Tables without header cells', 'msacalab-accessibility-checker' ),
			'who'   => __( 'Screen reader users', 'msacalab-accessibility-checker' ),
			'why'   => __( 'Without header cells, screen readers can\'t tell people which column or row a value belongs to.', 'msacalab-accessibility-checker' ),
			'fix'   => __( 'Mark the first row or column as headers (th). In the block editor, turn on "Header section" for the Table block. If the table is only for layout, add role="presentation".', 'msacalab-accessibility-checker' ),
		);
		$e['corea11y-small-text'] = array(
			'title' => __( 'Very small text', 'msacalab-accessibility-checker' ),
			'who'   => __( 'People with low vision and older visitors', 'msacalab-accessibility-checker' ),
			'why'   => __( 'Text smaller than 12px is hard to read for many people, even if they can zoom.', 'msacalab-accessibility-checker' ),
			'fix'   => __( 'Use at least 14 to 16px for body text and at least 12px for small print.', 'msacalab-accessibility-checker' ),
		);
		$e['corea11y-justified-text'] = array(
			'title' => __( 'Long text is justified', 'msacalab-accessibility-checker' ),
			'who'   => __( 'People with dyslexia or low vision', 'msacalab-accessibility-checker' ),
			'why'   => __( 'Justified text stretches the gaps between words unevenly, creating "rivers" of white space that make reading harder.', 'msacalab-accessibility-checker' ),
			'fix'   => __( 'Align body text to the left (or right, for right-to-left languages).', 'msacalab-accessibility-checker' ),
		);
		$e['corea11y-all-caps'] = array(
			'title' => __( 'Long text in capital letters', 'msacalab-accessibility-checker' ),
			'who'   => __( 'People with dyslexia or low vision, and screen reader users', 'msacalab-accessibility-checker' ),
			'why'   => __( 'Text in capitals is harder to read in long passages, and some screen readers spell it out letter by letter.', 'msacalab-accessibility-checker' ),
			'fix'   => __( 'Write the text in normal case. Use capitals only for short labels, preferably with CSS text-transform.', 'msacalab-accessibility-checker' ),
		);
		$e['corea11y-empty-paragraph'] = array(
			'title' => __( 'Empty paragraphs used for spacing', 'msacalab-accessibility-checker' ),
			'who'   => __( 'Screen reader users', 'msacalab-accessibility-checker' ),
			'why'   => __( 'Empty paragraphs are sometimes announced as "blank", which is confusing.', 'msacalab-accessibility-checker' ),
			'fix'   => __( 'Remove the empty paragraphs and use block spacing or a Spacer block instead.', 'msacalab-accessibility-checker' ),
		);
		$e['corea11y-accesskey'] = array(
			'title' => __( 'Access keys are used', 'msacalab-accessibility-checker' ),
			'who'   => __( 'Keyboard and screen reader users', 'msacalab-accessibility-checker' ),
			'why'   => __( 'Access keys can clash with shortcuts in browsers and screen readers, and users rarely know they exist.', 'msacalab-accessibility-checker' ),
			'fix'   => __( 'Remove the accesskey attribute.', 'msacalab-accessibility-checker' ),
		);
		$e['corea11y-animated-gif'] = array(
			'title' => __( 'Animated images may need a pause option', 'msacalab-accessibility-checker' ),
			'who'   => __( 'People with attention disorders or vestibular conditions', 'msacalab-accessibility-checker' ),
			'why'   => __( 'Moving images that play for more than five seconds can make it impossible to concentrate on the rest of the page.', 'msacalab-accessibility-checker' ),
			'fix'   => __( 'Use a still image, or a video with a pause button, if the animation lasts more than five seconds.', 'msacalab-accessibility-checker' ),
		);
		$e['ibm-fieldset-legend-valid'] = array(
			'title' => __( 'Groups of form fields need a clear legend', 'msacalab-accessibility-checker' ),
			'who'   => __( 'Screen reader users', 'msacalab-accessibility-checker' ),
			'why'   => __( 'A fieldset groups related fields, and its legend tells people what the group is about. Without a legend, the group has no name.', 'msacalab-accessibility-checker' ),
			'fix'   => __( 'Add a <legend> with text as the first element inside the <fieldset>.', 'msacalab-accessibility-checker' ),
		);
		$e['ibm-input-checkboxes-grouped'] = array(
			'title' => __( 'Related radio buttons and checkboxes aren\'t grouped', 'msacalab-accessibility-checker' ),
			'who'   => __( 'Screen reader users', 'msacalab-accessibility-checker' ),
			'why'   => __( 'When related options aren\'t grouped, screen reader users hear each option without the question it answers.', 'msacalab-accessibility-checker' ),
			'fix'   => __( 'Put the options in a <fieldset> with a <legend> that asks the question.', 'msacalab-accessibility-checker' ),
		);
		$e['ibm-img-alt-null'] = array(
			'title' => __( 'Images marked decorative still have a title', 'msacalab-accessibility-checker' ),
			'who'   => __( 'Screen reader users', 'msacalab-accessibility-checker' ),
			'why'   => __( 'An image with empty alt text is hidden from screen readers, but a title attribute suggests it has information. The two signals conflict.', 'msacalab-accessibility-checker' ),
			'fix'   => __( 'If the image is decorative, remove the title. If it has information, put it in the alt text.', 'msacalab-accessibility-checker' ),
		);
		$e['ibm-element-tabbable-role-valid'] = array(
			'title' => __( 'Keyboard focus lands on something that isn\'t interactive', 'msacalab-accessibility-checker' ),
			'who'   => __( 'Keyboard and screen reader users', 'msacalab-accessibility-checker' ),
			'why'   => __( 'An element with tabindex="0" receives keyboard focus, but if it isn\'t a button, link or control, people land on it and nothing happens.', 'msacalab-accessibility-checker' ),
			'fix'   => __( 'Remove tabindex from non-interactive elements, or give the element a proper role and keyboard behavior.', 'msacalab-accessibility-checker' ),
		);
		$e['ibm-table-headers-related'] = array(
			'title' => __( 'Table cells aren\'t linked to their headers', 'msacalab-accessibility-checker' ),
			'who'   => __( 'Screen reader users', 'msacalab-accessibility-checker' ),
			'why'   => __( 'In complex tables, screen readers rely on scope or headers attributes to say which headers belong to each cell.', 'msacalab-accessibility-checker' ),
			'fix'   => __( 'Add scope="col" or scope="row" to header cells. For complex tables, split them into simpler ones or use headers and id attributes.', 'msacalab-accessibility-checker' ),
		);
		$e['ibm-element-attribute-deprecated'] = array(
			'title' => __( 'Outdated HTML is used', 'msacalab-accessibility-checker' ),
			'who'   => __( 'Everyone using assistive technology', 'msacalab-accessibility-checker' ),
			'why'   => __( 'Old elements such as <center> or <font> are no longer part of HTML and may not be supported in the future.', 'msacalab-accessibility-checker' ),
			'fix'   => __( 'Replace them with modern HTML and CSS. This often comes from older content or a theme.', 'msacalab-accessibility-checker' ),
		);
		$e['ibm-img-alt-misuse'] = array(
			'title' => __( 'Alt text may not describe the image', 'msacalab-accessibility-checker' ),
			'who'   => __( 'Screen reader users', 'msacalab-accessibility-checker' ),
			'why'   => __( 'The alt text looks like a file name or placeholder, so it may not tell people what the image shows.', 'msacalab-accessibility-checker' ),
			'fix'   => __( 'Write alt text that describes what the image shows or does in context.', 'msacalab-accessibility-checker' ),
		);
		$e['ibm-text-block-heading'] = array(
			'title' => __( 'Text may look like a heading without being one', 'msacalab-accessibility-checker' ),
			'who'   => __( 'Screen reader users', 'msacalab-accessibility-checker' ),
			'why'   => __( 'Bold or large text styled as a heading isn\'t announced as a heading, so people can\'t jump to it.', 'msacalab-accessibility-checker' ),
			'fix'   => __( 'If the text is a heading, use a real Heading block (H2, H3...).', 'msacalab-accessibility-checker' ),
		);
		$e['ibm-list-markup-review'] = array(
			'title' => __( 'Content may be a list without list markup', 'msacalab-accessibility-checker' ),
			'who'   => __( 'Screen reader users', 'msacalab-accessibility-checker' ),
			'why'   => __( 'Items written as lines of text (for example with dashes) aren\'t announced as a list, so people don\'t know how many items there are.', 'msacalab-accessibility-checker' ),
			'fix'   => __( 'Use a List block (bulleted or numbered) for lists.', 'msacalab-accessibility-checker' ),
		);
		$e['ibm-media-audio-transcribed'] = array(
			'title' => __( 'Audio may need a transcript', 'msacalab-accessibility-checker' ),
			'who'   => __( 'People who are deaf or hard of hearing', 'msacalab-accessibility-checker' ),
			'why'   => __( 'Audio content needs a text transcript so everyone can get the information.', 'msacalab-accessibility-checker' ),
			'fix'   => __( 'Add a transcript below the audio player or link to one.', 'msacalab-accessibility-checker' ),
		);
		$e['ibm-media-track-available'] = array(
			'title' => __( 'Video may need captions', 'msacalab-accessibility-checker' ),
			'who'   => __( 'People who are deaf or hard of hearing', 'msacalab-accessibility-checker' ),
			'why'   => __( 'Videos with speech need captions that match what is said.', 'msacalab-accessibility-checker' ),
			'fix'   => __( 'Add a captions track to the video. Check auto-generated captions for accuracy.', 'msacalab-accessibility-checker' ),
		);

		$e['p-as-heading'] = array(
			'title' => __( 'Text is styled to look like a heading but isn\'t one', 'msacalab-accessibility-checker' ),
			'who'   => __( 'Screen reader users, who jump between headings to find their way around a page', 'msacalab-accessibility-checker' ),
			'why'   => __( 'This text is bold or large so it looks like a heading, but it is a normal paragraph. Screen reader users who move from heading to heading skip it, so they miss a section of the page.', 'msacalab-accessibility-checker' ),
			'fix'   => __( 'In the block editor, select the text and change the block to a Heading (choose the right level, for example H2 or H3). In a page builder, use its heading widget instead of a text widget with bigger font.', 'msacalab-accessibility-checker' ),
		);
		$e['label-content-name-mismatch'] = array(
			'title' => __( 'A button or link is announced differently from what it shows', 'msacalab-accessibility-checker' ),
			'who'   => __( 'People using voice control, and screen reader users', 'msacalab-accessibility-checker' ),
			'why'   => __( 'Voice control users say the words they see, for example "click Subscribe". If the hidden name of the button is different (say "Sign up for news"), the command doesn\'t work.', 'msacalab-accessibility-checker' ),
			'fix'   => __( 'Make the accessible name (aria-label or similar) start with, or include, the visible text exactly. Often the simplest fix is to remove the aria-label so the visible text is used.', 'msacalab-accessibility-checker' ),
		);
		$e['td-has-header'] = array(
			'title' => __( 'Cells in a large table are not linked to their headers', 'msacalab-accessibility-checker' ),
			'who'   => __( 'Screen reader users', 'msacalab-accessibility-checker' ),
			'why'   => __( 'In a big table, screen readers announce each cell\'s column and row header so the number makes sense. Without header cells, visitors hear a stream of values with no context.', 'msacalab-accessibility-checker' ),
			'fix'   => __( 'Mark the first row (and first column, if it labels rows) as header cells. In the WordPress Table block, turn on "Header section" in the block settings.', 'msacalab-accessibility-checker' ),
		);
		$e['table-fake-caption'] = array(
			'title' => __( 'A table cell is used as the table\'s title', 'msacalab-accessibility-checker' ),
			'who'   => __( 'Screen reader users', 'msacalab-accessibility-checker' ),
			'why'   => __( 'The table\'s title sits inside a cell spanning the whole table, so screen readers treat it as data and announce it with every column.', 'msacalab-accessibility-checker' ),
			'fix'   => __( 'Remove that row and give the table a real caption. In the WordPress Table block, use the caption field below the table.', 'msacalab-accessibility-checker' ),
		);
		$e['audio-caption'] = array(
			'title' => __( 'Audio is missing captions or a transcript', 'msacalab-accessibility-checker' ),
			'who'   => __( 'Deaf and hard-of-hearing visitors', 'msacalab-accessibility-checker' ),
			'why'   => __( 'Visitors who can\'t hear the audio miss everything said in it.', 'msacalab-accessibility-checker' ),
			'fix'   => __( 'Add a captions track to the audio, or put a full text transcript next to it on the page.', 'msacalab-accessibility-checker' ),
		);
		$e['css-orientation-lock'] = array(
			'title' => __( 'The page only works in one screen orientation', 'msacalab-accessibility-checker' ),
			'who'   => __( 'People whose phone or tablet is fixed in place, for example on a wheelchair mount', 'msacalab-accessibility-checker' ),
			'why'   => __( 'Some visitors can\'t turn their device. If the site only works in portrait or landscape, part of it becomes unusable for them.', 'msacalab-accessibility-checker' ),
			'fix'   => __( 'Remove the CSS that forces one orientation, usually in the theme or a custom CSS snippet, so the layout adapts to both.', 'msacalab-accessibility-checker' ),
		);
		$e['avoid-inline-spacing'] = array(
			'title' => __( 'Text spacing can\'t be adjusted', 'msacalab-accessibility-checker' ),
			'who'   => __( 'People with dyslexia or low vision who use their own text spacing', 'msacalab-accessibility-checker' ),
			'why'   => __( 'Some visitors use a browser extension to add space between lines, words and letters. Spacing set with !important inline styles blocks those changes.', 'msacalab-accessibility-checker' ),
			'fix'   => __( 'Remove the inline style (often added by a page builder) or the !important from the line-height, letter-spacing or word-spacing setting.', 'msacalab-accessibility-checker' ),
		);
		/**
		 * Filter the plain-language rule explanations.
		 *
		 * @param array $e Explanations keyed by rule id.
		 */
		return apply_filters( 'corea11y_rule_explanations', $e );
	}

	/**
	 * Visitor-facing wording for a rule, used on the public accessibility statement.
	 *
	 * @param string $rule_id  Rule id.
	 * @param string $fallback Fallback text.
	 * @return string
	 */
	public static function public_title( $rule_id, $fallback = '' ) {
		$public = array(
			'image-alt'                => __( 'Some images are missing a text description', 'msacalab-accessibility-checker' ),
			'corea11y-content-img-alt' => __( 'Some images are missing a text description', 'msacalab-accessibility-checker' ),
			'corea11y-media-alt'       => __( 'Some images are missing a text description', 'msacalab-accessibility-checker' ),
			'role-img-alt'             => __( 'Some images are missing a text description', 'msacalab-accessibility-checker' ),
			'svg-img-alt'              => __( 'Some images are missing a text description', 'msacalab-accessibility-checker' ),
			'color-contrast'           => __( 'Some text has low color contrast', 'msacalab-accessibility-checker' ),
			'link-name'                => __( 'Some links don\'t describe where they go', 'msacalab-accessibility-checker' ),
			'button-name'              => __( 'Some buttons have no accessible name', 'msacalab-accessibility-checker' ),
			'label'                    => __( 'Some form fields have no label', 'msacalab-accessibility-checker' ),
			'list'                     => __( 'Some lists are not structured correctly', 'msacalab-accessibility-checker' ),
			'listitem'                 => __( 'Some lists are not structured correctly', 'msacalab-accessibility-checker' ),
			'target-size'              => __( 'Some buttons or links are small to tap', 'msacalab-accessibility-checker' ),
			'video-caption'            => __( 'Some videos have no captions', 'msacalab-accessibility-checker' ),
		);
		if ( isset( $public[ $rule_id ] ) ) {
			return $public[ $rule_id ];
		}
		return self::get( $rule_id, $fallback )['title'];
	}

	/**
	 * Short explanations for report concepts (score, severity, tiers).
	 *
	 * @return array<string,string>
	 */
	public static function concepts() {
		return array(
			'score'     => __( 'The accessibility score is our own quick progress indicator from 0 to 100, based only on the automated checks. It goes down with each problem found, weighted by severity, and averaged per page. It is not an official WCAG score or a compliance grade, and other tools score differently: a site can score well and still have issues automated tools can\'t detect.', 'msacalab-accessibility-checker' ),
			'bestpractice' => __( 'A widely recommended practice that makes pages easier to use, but isn\'t a strict WCAG requirement. Shown as minor and never counted in the score.', 'msacalab-accessibility-checker' ),
			'review'    => __( 'Elements the automated checks could not judge with certainty, such as text over an image or gradient, where a person needs to look. They are listed so nothing is hidden, but they are not counted as issues.', 'msacalab-accessibility-checker' ),
			'issues'    => __( 'The number of individual elements with a problem. One missing alt text on five images counts as five issues.', 'msacalab-accessibility-checker' ),
			'problems'  => __( 'The number of different kinds of problems found. Fixing one kind (for example, in your theme) often clears many issues at once.', 'msacalab-accessibility-checker' ),
			'pages'     => __( 'Pages successfully scanned out of the pages planned. Change which pages are scanned in Settings.', 'msacalab-accessibility-checker' ),
			'critical'  => __( 'Critical: blocks some visitors completely, for example a button a screen reader can\'t identify. Fix these first.', 'msacalab-accessibility-checker' ),
			'serious'   => __( 'Serious: causes major difficulty for some visitors, for example hard-to-read text.', 'msacalab-accessibility-checker' ),
			'moderate'  => __( 'Moderate: creates friction but visitors can usually work around it.', 'msacalab-accessibility-checker' ),
			'minor'     => __( 'Minor: small annoyances that are still worth fixing.', 'msacalab-accessibility-checker' ),
			'automatic' => __( 'Fixes that have one correct answer and can be applied safely and undone, like adding the page language. Turn them on under Accessibility > Quick fixes.', 'msacalab-accessibility-checker' ),
			'approval'  => __( 'Fixes that change how something looks or reads, like a text color. MSACAlab Accessibility Checker Pro suggests a fix and applies it once you approve.', 'msacalab-accessibility-checker' ),
			'owner'     => __( 'Fixes only you can make well, like writing a meaningful description of a photo or adding captions to a video.', 'msacalab-accessibility-checker' ),
			'resolved'  => __( 'Issues found in the earlier audit that are no longer found in the later one.', 'msacalab-accessibility-checker' ),
			'remaining' => __( 'Issues found in both audits.', 'msacalab-accessibility-checker' ),
			'new'       => __( 'Issues found in the later audit that were not in the earlier one, for example on a new page or after a theme change.', 'msacalab-accessibility-checker' ),
		);
	}
}
