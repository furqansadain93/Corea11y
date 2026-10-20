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
				'title' => $fallback ? $fallback : __( 'A screen reader instruction (ARIA) is used incorrectly', 'corea11y' ),
				'who'   => __( 'Screen reader users', 'corea11y' ),
				'why'   => __( 'ARIA attributes tell assistive technology what an element is and what state it is in. When they are wrong or incomplete, screen readers announce confusing or false information, which can be worse than having no ARIA at all.', 'corea11y' ),
				'fix'   => __( 'Check the element\'s role and aria-* attributes against the expected pattern. Often the simplest fix is to use a native HTML element (button, link, input) instead of ARIA. This usually comes from a theme or plugin, so you may need to contact its developer.', 'corea11y' ),
			);
		}
		return array(
			'title' => $fallback ? $fallback : __( 'Accessibility problem', 'corea11y' ),
			'who'   => __( 'People using assistive technology', 'corea11y' ),
			'why'   => __( 'This element does not meet a WCAG requirement, which can make it hard or impossible for some visitors to perceive or use.', 'corea11y' ),
			'fix'   => __( 'Open "How to fix this" for detailed guidance on this rule, then update the element in your content, theme or plugin settings.', 'corea11y' ),
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
			'title' => __( 'Images are missing a text description', 'corea11y' ),
			'who'   => __( 'Blind and low-vision visitors using screen readers; also search engines', 'corea11y' ),
			'why'   => __( 'Screen readers read an image\'s alt text out loud. Without it, visitors hear only "image" or the file name, so they miss whatever the picture shows or does.', 'corea11y' ),
			'fix'   => __( 'Add alt text that describes what the image shows or does, in a short sentence. If the image is purely decorative, give it empty alt text so screen readers skip it. In WordPress, set this in the Media Library or the image block\'s settings.', 'corea11y' ),
		);
		$e['corea11y-content-img-alt'] = array(
			'title' => __( 'Images in your posts are missing a text description', 'corea11y' ),
			'who'   => __( 'Blind and low-vision visitors using screen readers', 'corea11y' ),
			'why'   => __( 'This image was found in post content without any alt attribute, so screen readers have nothing to announce.', 'corea11y' ),
			'fix'   => __( 'Edit the post, select the image and fill in "Alternative text" in the block settings. Use empty alt text only for decorative images.', 'corea11y' ),
		);
		$e['corea11y-media-alt'] = array(
			'title' => __( 'Media Library images have no alt text', 'corea11y' ),
			'who'   => __( 'Blind and low-vision visitors using screen readers', 'corea11y' ),
			'why'   => __( 'Alt text set in the Media Library is used automatically wherever the image is inserted later. Leaving it empty means every future use starts without a description.', 'corea11y' ),
			'fix'   => __( 'Go to Media > Library, open the image and fill in "Alternative Text". Leave it empty only if the image is purely decorative.', 'corea11y' ),
		);
		$e['role-img-alt'] = array(
			'title' => __( 'Graphics marked as images have no description', 'corea11y' ),
			'who'   => __( 'Screen reader users', 'corea11y' ),
			'why'   => __( 'This element is announced as an image but has no text alternative, so its meaning is lost.', 'corea11y' ),
			'fix'   => __( 'Add an aria-label or aria-labelledby that describes the graphic, or remove role="img" if it is decorative.', 'corea11y' ),
		);
		$e['svg-img-alt'] = array(
			'title' => __( 'SVG images have no description', 'corea11y' ),
			'who'   => __( 'Screen reader users', 'corea11y' ),
			'why'   => __( 'Icons and illustrations drawn as SVG need a text alternative just like regular images.', 'corea11y' ),
			'fix'   => __( 'Add a <title> inside the SVG or an aria-label on it. If the icon is decorative, add aria-hidden="true" instead.', 'corea11y' ),
		);
		$e['input-image-alt'] = array(
			'title' => __( 'Image buttons have no text alternative', 'corea11y' ),
			'who'   => __( 'Screen reader users', 'corea11y' ),
			'why'   => __( 'A button made from an image needs alt text that says what the button does, or users won\'t know what will happen when they press it.', 'corea11y' ),
			'fix'   => __( 'Add alt text describing the action, for example "Search" or "Submit order".', 'corea11y' ),
		);
		$e['area-alt'] = array(
			'title' => __( 'Clickable areas of an image map have no text', 'corea11y' ),
			'who'   => __( 'Screen reader users', 'corea11y' ),
			'why'   => __( 'Each clickable area works like a link and needs text saying where it goes.', 'corea11y' ),
			'fix'   => __( 'Add alt text to every <area> element describing its destination.', 'corea11y' ),
		);
		$e['object-alt'] = array(
			'title' => __( 'Embedded objects have no text alternative', 'corea11y' ),
			'who'   => __( 'Screen reader users', 'corea11y' ),
			'why'   => __( 'Embedded content such as old plugins or documents needs a description so everyone knows what it contains.', 'corea11y' ),
			'fix'   => __( 'Add an aria-label or title to the <object>, or provide the content another way.', 'corea11y' ),
		);
		$e['color-contrast'] = array(
			'title' => __( 'Text is hard to read against its background', 'corea11y' ),
			'who'   => __( 'People with low vision or color blindness, older visitors, anyone reading on a phone in sunlight', 'corea11y' ),
			'why'   => __( 'When text and background colors are too similar, the text fades out. WCAG asks for a contrast ratio of at least 4.5:1 for normal text and 3:1 for large text.', 'corea11y' ),
			'fix'   => __( 'Make the text darker or the background lighter (or the opposite on dark designs) until the ratio passes. You can usually change these colors in your theme\'s style settings or the block\'s color settings.', 'corea11y' ),
		);
		$e['link-in-text-block'] = array(
			'title' => __( 'Links in text can only be spotted by color', 'corea11y' ),
			'who'   => __( 'People with color blindness or low vision', 'corea11y' ),
			'why'   => __( 'If a link looks just like the surrounding text except for its color, some visitors can\'t tell it\'s a link.', 'corea11y' ),
			'fix'   => __( 'Underline links in body text, or give them enough contrast with the surrounding text plus a visible change on hover and focus.', 'corea11y' ),
		);
		$e['link-name'] = array(
			'title' => __( 'Links don\'t say where they go', 'corea11y' ),
			'who'   => __( 'Screen reader and voice control users', 'corea11y' ),
			'why'   => __( 'This link has no readable text, often because it only contains an icon or an image without alt text. Screen readers announce it as just "link", and voice users can\'t say its name to click it.', 'corea11y' ),
			'fix'   => __( 'Add visible link text, or alt text to the image inside the link, or an aria-label such as "Read more about our services".', 'corea11y' ),
		);
		$e['button-name'] = array(
			'title' => __( 'Buttons have no name', 'corea11y' ),
			'who'   => __( 'Screen reader and voice control users', 'corea11y' ),
			'why'   => __( 'A button with only an icon, or nothing at all, is announced as just "button". Visitors can\'t tell what it does.', 'corea11y' ),
			'fix'   => __( 'Give the button visible text, or an aria-label that describes its action, such as "Open menu" or "Search".', 'corea11y' ),
		);
		$e['input-button-name'] = $e['button-name'];
		$e['label'] = array(
			'title' => __( 'Form fields have no label', 'corea11y' ),
			'who'   => __( 'Screen reader users, people with cognitive disabilities, everyone filling in forms', 'corea11y' ),
			'why'   => __( 'Without a label, screen readers can\'t say what a field is for. Placeholder text alone disappears as soon as you type.', 'corea11y' ),
			'fix'   => __( 'Add a visible <label> connected to each field. Most form plugins have a "label" setting; make sure it isn\'t hidden or empty.', 'corea11y' ),
		);
		$e['select-name'] = array(
			'title' => __( 'Dropdown menus have no label', 'corea11y' ),
			'who'   => __( 'Screen reader users', 'corea11y' ),
			'why'   => __( 'Screen readers can\'t say what a dropdown is for when it has no label.', 'corea11y' ),
			'fix'   => __( 'Add a visible label connected to the dropdown.', 'corea11y' ),
		);
		$e['form-field-multiple-labels'] = array(
			'title' => __( 'Form fields have more than one label', 'corea11y' ),
			'who'   => __( 'Screen reader users', 'corea11y' ),
			'why'   => __( 'Different screen readers pick different labels when a field has several, so some users hear the wrong one.', 'corea11y' ),
			'fix'   => __( 'Keep one label per field and remove the extra ones.', 'corea11y' ),
		);
		$e['autocomplete-valid'] = array(
			'title' => __( 'Form fields use an invalid autofill hint', 'corea11y' ),
			'who'   => __( 'People with motor or memory difficulties who rely on autofill', 'corea11y' ),
			'why'   => __( 'Correct autocomplete values let browsers fill in names, emails and addresses automatically. A wrong value breaks that help.', 'corea11y' ),
			'fix'   => __( 'Use a standard autocomplete value such as "email", "name" or "postal-code", or remove the attribute.', 'corea11y' ),
		);
		$e['html-has-lang'] = array(
			'title' => __( 'The page doesn\'t say what language it is in', 'corea11y' ),
			'who'   => __( 'Screen reader users; translation tools', 'corea11y' ),
			'why'   => __( 'Screen readers use the page language to pick the right voice and pronunciation. Without it, text may be read with the wrong accent and become hard to understand.', 'corea11y' ),
			'fix'   => __( 'Make sure your theme prints the language on the <html> tag. In WordPress this comes from Settings > General > Site Language and the theme\'s language_attributes() call.', 'corea11y' ),
		);
		$e['html-lang-valid'] = array(
			'title' => __( 'The page language code is not valid', 'corea11y' ),
			'who'   => __( 'Screen reader users', 'corea11y' ),
			'why'   => __( 'An unrecognized language code means screen readers can\'t choose the right pronunciation.', 'corea11y' ),
			'fix'   => __( 'Use a valid code like "en", "en-US" or "ur". Check Settings > General > Site Language.', 'corea11y' ),
		);
		$e['valid-lang'] = array(
			'title' => __( 'A section uses an invalid language code', 'corea11y' ),
			'who'   => __( 'Screen reader users', 'corea11y' ),
			'why'   => __( 'Parts of a page can be marked as another language, but the code must be valid for screen readers to switch voices.', 'corea11y' ),
			'fix'   => __( 'Correct the lang attribute on that element to a valid language code.', 'corea11y' ),
		);
		$e['html-xml-lang-mismatch'] = array(
			'title' => __( 'The page declares two different languages', 'corea11y' ),
			'who'   => __( 'Screen reader users', 'corea11y' ),
			'why'   => __( 'The lang and xml:lang attributes disagree, so assistive technology may pick the wrong one.', 'corea11y' ),
			'fix'   => __( 'Make both attributes match, or remove xml:lang.', 'corea11y' ),
		);
		$e['document-title'] = array(
			'title' => __( 'The page has no title', 'corea11y' ),
			'who'   => __( 'Screen reader users, people with many tabs open, search engines', 'corea11y' ),
			'why'   => __( 'The page title is the first thing a screen reader announces and what appears in the browser tab. Without it, visitors can\'t tell pages apart.', 'corea11y' ),
			'fix'   => __( 'Make sure your theme supports "title-tag" and every page has a descriptive title.', 'corea11y' ),
		);
		$e['bypass'] = array(
			'title' => __( 'There is no way to skip straight to the main content', 'corea11y' ),
			'who'   => __( 'Keyboard and screen reader users', 'corea11y' ),
			'why'   => __( 'Without a "Skip to content" link or proper page landmarks, keyboard users must tab through the whole menu on every single page.', 'corea11y' ),
			'fix'   => __( 'Add a "Skip to content" link as the first focusable item, and wrap the main content in a <main> element. Many themes include this option.', 'corea11y' ),
		);
		$e['meta-viewport'] = array(
			'title' => __( 'Zooming is blocked on mobile', 'corea11y' ),
			'who'   => __( 'People with low vision on phones and tablets', 'corea11y' ),
			'why'   => __( 'The page stops visitors from pinch-zooming, so people who need larger text can\'t enlarge it.', 'corea11y' ),
			'fix'   => __( 'Remove "user-scalable=no" and any "maximum-scale" below 2 from the viewport meta tag in your theme.', 'corea11y' ),
		);
		$e['meta-refresh'] = array(
			'title' => __( 'The page refreshes or redirects on a timer', 'corea11y' ),
			'who'   => __( 'Screen reader users, people who read slowly', 'corea11y' ),
			'why'   => __( 'Automatic refreshes can move people away before they finish reading and reset their place on the page.', 'corea11y' ),
			'fix'   => __( 'Remove the timed refresh, or use a server redirect instead.', 'corea11y' ),
		);
		$e['list'] = array(
			'title' => __( 'Lists contain items that aren\'t list items', 'corea11y' ),
			'who'   => __( 'Screen reader users', 'corea11y' ),
			'why'   => __( 'Screen readers announce how many items a list has and let users jump between them. Other elements placed directly inside the list break that structure.', 'corea11y' ),
			'fix'   => __( 'Make sure <ul> and <ol> contain only <li> elements directly. This often comes from a theme or menu plugin, so you may need to ask its developer.', 'corea11y' ),
		);
		$e['listitem'] = array(
			'title' => __( 'List items are placed outside a list', 'corea11y' ),
			'who'   => __( 'Screen reader users', 'corea11y' ),
			'why'   => __( 'A list item outside a list isn\'t announced as part of a group, so the structure is lost.', 'corea11y' ),
			'fix'   => __( 'Wrap <li> elements in a <ul> or <ol>.', 'corea11y' ),
		);
		$e['definition-list'] = array(
			'title' => __( 'Definition lists are structured incorrectly', 'corea11y' ),
			'who'   => __( 'Screen reader users', 'corea11y' ),
			'why'   => __( 'Terms and definitions must be paired correctly to be announced together.', 'corea11y' ),
			'fix'   => __( 'Make sure <dl> contains only <dt> and <dd> groups.', 'corea11y' ),
		);
		$e['dlitem'] = $e['definition-list'];
		$e['frame-title'] = array(
			'title' => __( 'Embedded content (iframes) has no title', 'corea11y' ),
			'who'   => __( 'Screen reader users', 'corea11y' ),
			'why'   => __( 'Videos, maps and widgets embedded in frames need a title so visitors know what the embedded area contains before entering it.', 'corea11y' ),
			'fix'   => __( 'Add a title attribute to the iframe, for example title="Map of our office location".', 'corea11y' ),
		);
		$e['frame-title-unique'] = array(
			'title' => __( 'Several embedded frames share the same title', 'corea11y' ),
			'who'   => __( 'Screen reader users', 'corea11y' ),
			'why'   => __( 'Identical titles make it impossible to tell embedded areas apart.', 'corea11y' ),
			'fix'   => __( 'Give each iframe a unique, descriptive title.', 'corea11y' ),
		);
		$e['frame-focusable-content'] = array(
			'title' => __( 'Interactive content inside a frame can\'t be reached by keyboard', 'corea11y' ),
			'who'   => __( 'Keyboard users', 'corea11y' ),
			'why'   => __( 'The frame is removed from the tab order but contains things you can click, so keyboard users can\'t reach them.', 'corea11y' ),
			'fix'   => __( 'Remove tabindex="-1" from the iframe.', 'corea11y' ),
		);
		$e['video-caption'] = array(
			'title' => __( 'Videos have no captions', 'corea11y' ),
			'who'   => __( 'Deaf and hard-of-hearing visitors; anyone watching with the sound off', 'corea11y' ),
			'why'   => __( 'Without captions, everything said in the video is lost for people who can\'t hear it.', 'corea11y' ),
			'fix'   => __( 'Add a captions track to the video (a .vtt file), or use a video host such as YouTube that supports edited captions.', 'corea11y' ),
		);
		$e['no-autoplay-audio'] = array(
			'title' => __( 'Audio plays automatically', 'corea11y' ),
			'who'   => __( 'Screen reader users, people with attention or anxiety conditions', 'corea11y' ),
			'why'   => __( 'Sound that starts on its own can drown out a screen reader, and visitors may not find how to stop it.', 'corea11y' ),
			'fix'   => __( 'Turn off autoplay, or make sure it stops within 3 seconds and has a clear pause control.', 'corea11y' ),
		);
		$e['nested-interactive'] = array(
			'title' => __( 'Clickable elements are placed inside other clickable elements', 'corea11y' ),
			'who'   => __( 'Screen reader and keyboard users', 'corea11y' ),
			'why'   => __( 'A button inside a link (or similar) confuses assistive technology, and the inner control may never be announced or reachable.', 'corea11y' ),
			'fix'   => __( 'Restructure the element so links and buttons are not nested inside each other.', 'corea11y' ),
		);
		$e['scrollable-region-focusable'] = array(
			'title' => __( 'Scrollable areas can\'t be scrolled with a keyboard', 'corea11y' ),
			'who'   => __( 'Keyboard users', 'corea11y' ),
			'why'   => __( 'Content in a scrolling box is out of reach if keyboard users can\'t move focus into the box.', 'corea11y' ),
			'fix'   => __( 'Add tabindex="0" and an accessible name to the scrollable area, or make sure it contains a focusable element.', 'corea11y' ),
		);
		$e['target-size'] = array(
			'title' => __( 'Buttons or links are too small to tap easily', 'corea11y' ),
			'who'   => __( 'People with tremors or limited dexterity, everyone on a phone', 'corea11y' ),
			'why'   => __( 'Tiny targets packed close together lead to mis-taps. WCAG 2.2 asks for at least 24 by 24 pixels, or enough space around smaller targets.', 'corea11y' ),
			'fix'   => __( 'Increase the size or padding of the link or button, or add more space between neighbouring targets.', 'corea11y' ),
		);
		$e['td-headers-attr'] = array(
			'title' => __( 'Table cells point to headers that don\'t exist', 'corea11y' ),
			'who'   => __( 'Screen reader users', 'corea11y' ),
			'why'   => __( 'Screen readers announce each cell\'s headers. Broken references mean cells are read without context.', 'corea11y' ),
			'fix'   => __( 'Make sure every id in a cell\'s headers attribute matches a header cell in the same table.', 'corea11y' ),
		);
		$e['th-has-data-cells'] = array(
			'title' => __( 'Table headers don\'t describe any data', 'corea11y' ),
			'who'   => __( 'Screen reader users', 'corea11y' ),
			'why'   => __( 'Header cells that don\'t relate to any data cells make the table structure confusing.', 'corea11y' ),
			'fix'   => __( 'Use header cells only for real row or column headings.', 'corea11y' ),
		);
		$e['duplicate-id-aria'] = array(
			'title' => __( 'Elements referenced by labels share the same id', 'corea11y' ),
			'who'   => __( 'Screen reader users', 'corea11y' ),
			'why'   => __( 'When two elements have the same id, labels and descriptions may point to the wrong one.', 'corea11y' ),
			'fix'   => __( 'Give every element a unique id.', 'corea11y' ),
		);
		$e['aria-hidden-focus'] = array(
			'title' => __( 'Hidden elements can still receive keyboard focus', 'corea11y' ),
			'who'   => __( 'Screen reader and keyboard users', 'corea11y' ),
			'why'   => __( 'The element is hidden from screen readers but can still be tabbed to, so users land on something that is announced as nothing.', 'corea11y' ),
			'fix'   => __( 'Remove aria-hidden, or make the focusable items inside it unfocusable (for example with the inert attribute).', 'corea11y' ),
		);
		$e['aria-hidden-body'] = array(
			'title' => __( 'The whole page is hidden from screen readers', 'corea11y' ),
			'who'   => __( 'Screen reader users', 'corea11y' ),
			'why'   => __( 'aria-hidden on the page body makes the entire site invisible to assistive technology.', 'corea11y' ),
			'fix'   => __( 'Remove aria-hidden="true" from the <body> element. This is often left behind by a pop-up or menu script.', 'corea11y' ),
		);
		$e['server-side-image-map'] = array(
			'title' => __( 'Server-side image maps are used', 'corea11y' ),
			'who'   => __( 'Keyboard and screen reader users', 'corea11y' ),
			'why'   => __( 'Server-side image maps only work with a mouse.', 'corea11y' ),
			'fix'   => __( 'Replace it with regular links or a client-side image map with alt text.', 'corea11y' ),
		);
		$e['blink'] = array(
			'title' => __( 'Blinking content is used', 'corea11y' ),
			'who'   => __( 'People with attention or vision conditions', 'corea11y' ),
			'why'   => __( 'Blinking text is distracting and hard to read, and can\'t be paused.', 'corea11y' ),
			'fix'   => __( 'Remove the <blink> element.', 'corea11y' ),
		);
		$e['marquee'] = array(
			'title' => __( 'Scrolling text (marquee) is used', 'corea11y' ),
			'who'   => __( 'People with attention, reading or vision conditions', 'corea11y' ),
			'why'   => __( 'Moving text is hard to read and can\'t be paused.', 'corea11y' ),
			'fix'   => __( 'Replace the <marquee> with static text.', 'corea11y' ),
		);
		$e['summary-name'] = array(
			'title' => __( 'Expandable sections have no name', 'corea11y' ),
			'who'   => __( 'Screen reader users', 'corea11y' ),
			'why'   => __( 'The toggle for an expandable section has no text, so visitors don\'t know what it opens.', 'corea11y' ),
			'fix'   => __( 'Add text inside the <summary> element.', 'corea11y' ),
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
			'image-alt'                => __( 'Some images are missing a text description', 'corea11y' ),
			'corea11y-content-img-alt' => __( 'Some images are missing a text description', 'corea11y' ),
			'corea11y-media-alt'       => __( 'Some images are missing a text description', 'corea11y' ),
			'role-img-alt'             => __( 'Some images are missing a text description', 'corea11y' ),
			'svg-img-alt'              => __( 'Some images are missing a text description', 'corea11y' ),
			'color-contrast'           => __( 'Some text has low color contrast', 'corea11y' ),
			'link-name'                => __( 'Some links don\'t describe where they go', 'corea11y' ),
			'button-name'              => __( 'Some buttons have no accessible name', 'corea11y' ),
			'label'                    => __( 'Some form fields have no label', 'corea11y' ),
			'list'                     => __( 'Some lists are not structured correctly', 'corea11y' ),
			'listitem'                 => __( 'Some lists are not structured correctly', 'corea11y' ),
			'target-size'              => __( 'Some buttons or links are small to tap', 'corea11y' ),
			'video-caption'            => __( 'Some videos have no captions', 'corea11y' ),
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
			'score'     => __( 'The CoreA11y score is our own quick progress indicator from 0 to 100, based only on the automated checks. It goes down with each problem found, weighted by severity, and averaged per page. It is not an official WCAG score or a compliance grade, and other tools score differently: a site can score well and still have issues automated tools can\'t detect.', 'corea11y' ),
			'review'    => __( 'Elements the automated checks could not judge with certainty, such as text over an image or gradient, where a person needs to look. They are listed so nothing is hidden, but they are not counted as issues.', 'corea11y' ),
			'issues'    => __( 'The number of individual elements with a problem. One missing alt text on five images counts as five issues.', 'corea11y' ),
			'problems'  => __( 'The number of different kinds of problems found. Fixing one kind (for example, in your theme) often clears many issues at once.', 'corea11y' ),
			'pages'     => __( 'Pages successfully scanned out of the pages planned. Change which pages are scanned in Settings.', 'corea11y' ),
			'critical'  => __( 'Critical: blocks some visitors completely, for example a button a screen reader can\'t identify. Fix these first.', 'corea11y' ),
			'serious'   => __( 'Serious: causes major difficulty for some visitors, for example hard-to-read text.', 'corea11y' ),
			'moderate'  => __( 'Moderate: creates friction but visitors can usually work around it.', 'corea11y' ),
			'minor'     => __( 'Minor: small annoyances that are still worth fixing.', 'corea11y' ),
			'automatic' => __( 'Fixes that have one correct answer and can be applied safely and undone, like adding the page language. Turn them on under CoreA11y > Quick fixes.', 'corea11y' ),
			'approval'  => __( 'Fixes that change how something looks or reads, like a text color. CoreA11y Pro suggests a fix and applies it once you approve.', 'corea11y' ),
			'owner'     => __( 'Fixes only you can make well, like writing a meaningful description of a photo or adding captions to a video.', 'corea11y' ),
			'resolved'  => __( 'Issues found in the earlier audit that are no longer found in the later one.', 'corea11y' ),
			'remaining' => __( 'Issues found in both audits.', 'corea11y' ),
			'new'       => __( 'Issues found in the later audit that were not in the earlier one, for example on a new page or after a theme change.', 'corea11y' ),
		);
	}
}
