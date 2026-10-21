<?php
/**
 * Reusable UI pieces: "?" tips, score ring, bar charts, rule cards.
 *
 * All charts are plain HTML/SVG with visible numbers, so they work with screen readers,
 * print to PDF cleanly and need no JavaScript library.
 *
 * @package CoreA11y
 */

defined( 'ABSPATH' ) || exit;

/**
 * UI helpers. Methods return escaped HTML strings.
 */
class CoreA11y_UI {

	/**
	 * Counter for unique ids.
	 *
	 * @var int
	 */
	private static $uid = 0;

	/**
	 * Unique id with a prefix.
	 *
	 * @param string $prefix Prefix.
	 * @return string
	 */
	public static function uid( $prefix = 'corea11y' ) {
		++self::$uid;
		return $prefix . '-' . self::$uid;
	}

	/**
	 * A small "?" button that reveals an explanation.
	 *
	 * Works without JavaScript (the panel is visible until the script hides it).
	 *
	 * @param string $label   What is being explained, for screen readers ("Score").
	 * @param string $content Explanation HTML (already escaped by the caller) or plain text.
	 * @param bool   $is_html Whether $content is pre-escaped HTML.
	 * @return string
	 */
	public static function tip( $label, $content, $is_html = false ) {
		$id = self::uid( 'corea11y-tip' );
		return sprintf(
			'<span class="corea11y-tip"><button type="button" class="corea11y-tip-btn" aria-expanded="false" aria-controls="%1$s"><span aria-hidden="true">?</span><span class="screen-reader-text">%2$s</span></button><span class="corea11y-tip-panel" id="%1$s" role="note">%3$s</span></span>',
			esc_attr( $id ),
			/* translators: %s: the thing being explained, e.g. "Score". */
			esc_html( sprintf( __( 'What is "%s"?', 'corea11y' ), $label ) ),
			$is_html ? $content : esc_html( $content )
		);
	}

	/**
	 * Tip for a known concept (score, severity, tier...).
	 *
	 * @param string $key   Concept key from CoreA11y_Explain::concepts().
	 * @param string $label Visible label.
	 * @return string
	 */
	public static function concept_tip( $key, $label ) {
		$c = CoreA11y_Explain::concepts();
		return isset( $c[ $key ] ) ? self::tip( $label, $c[ $key ] ) : '';
	}

	/**
	 * Explanation tip for a rule: what, who, how to fix.
	 *
	 * @param array $explain From CoreA11y_Explain::get().
	 * @return string
	 */
	public static function rule_tip( array $explain ) {
		$html = sprintf(
			'<strong>%1$s</strong> %2$s<br><strong>%3$s</strong> %4$s<br><strong>%5$s</strong> %6$s',
			esc_html__( 'Why it matters:', 'corea11y' ),
			esc_html( $explain['why'] ),
			esc_html__( 'Who it affects:', 'corea11y' ),
			esc_html( $explain['who'] ),
			esc_html__( 'How to fix:', 'corea11y' ),
			esc_html( $explain['fix'] )
		);
		return self::tip( $explain['title'], $html, true );
	}

	/**
	 * Score band for a 0-100 score.
	 *
	 * @param int|null $score Score.
	 * @return array{key:string,label:string}
	 */
	public static function band( $score ) {
		if ( null === $score ) {
			return array(
				'key'   => 'none',
				'label' => __( 'Not scored yet', 'corea11y' ),
			);
		}
		if ( $score >= 90 ) {
			return array(
				'key'   => 'great',
				'label' => __( 'Great', 'corea11y' ),
			);
		}
		if ( $score >= 75 ) {
			return array(
				'key'   => 'good',
				'label' => __( 'Good', 'corea11y' ),
			);
		}
		if ( $score >= 50 ) {
			return array(
				'key'   => 'fair',
				'label' => __( 'Needs work', 'corea11y' ),
			);
		}
		return array(
			'key'   => 'poor',
			'label' => __( 'Needs attention', 'corea11y' ),
		);
	}

	/**
	 * Circular score gauge (SVG).
	 *
	 * @param int|null $score   Score 0-100.
	 * @param string   $caption Visible caption under the ring.
	 * @param int      $size    Pixel size.
	 * @return string
	 */
	public static function score_ring( $score, $caption = '', $size = 140 ) {
		$band  = self::band( $score );
		$r     = 52;
		$circ  = 2 * M_PI * $r;
		$value = null === $score ? 0 : max( 0, min( 100, (int) $score ) );
		$dash  = $circ * $value / 100;
		/* translators: 1: score, 2: score band such as "Good". */
		$label = null === $score ? $band['label'] : sprintf( __( 'Scan score %1$d out of 100: %2$s', 'corea11y' ), $value, $band['label'] );

		$svg = sprintf(
			'<svg class="corea11y-ring corea11y-band-%1$s" width="%2$d" height="%2$d" viewBox="0 0 120 120" role="img" aria-label="%3$s">'
			. '<circle class="corea11y-ring-track" cx="60" cy="60" r="%4$d" />'
			. '<circle class="corea11y-ring-value" cx="60" cy="60" r="%4$d" stroke-dasharray="%5$.2f %6$.2f" transform="rotate(-90 60 60)" />'
			. '<text x="60" y="58" text-anchor="middle" class="corea11y-ring-number">%7$s</text>'
			. '<text x="60" y="80" text-anchor="middle" class="corea11y-ring-label">%8$s</text>'
			. '</svg>',
			esc_attr( $band['key'] ),
			(int) $size,
			esc_attr( $label ),
			$r,
			$dash,
			$circ,
			null === $score ? '—' : esc_html( $value ),
			esc_html( $band['label'] )
		);

		return '<figure class="corea11y-ring-wrap">' . $svg . ( $caption ? '<figcaption>' . esc_html( $caption ) . '</figcaption>' : '' ) . '</figure>';
	}

	/**
	 * Horizontal bar chart as an accessible list with visible values.
	 *
	 * @param array  $rows  Rows: [ 'label' => string, 'value' => int, 'class' => string, 'tip' => html ].
	 * @param string $title Chart title.
	 * @return string
	 */
	public static function bars( array $rows, $title ) {
		$max = 1;
		foreach ( $rows as $row ) {
			$max = max( $max, (int) $row['value'] );
		}
		$out = '<div class="corea11y-chart"><h3 class="corea11y-chart-title">' . esc_html( $title ) . '</h3><ul class="corea11y-bars">';
		foreach ( $rows as $row ) {
			$pct  = round( 100 * (int) $row['value'] / $max, 1 );
			$out .= sprintf(
				'<li><span class="corea11y-bar-label">%1$s%2$s</span><span class="corea11y-bar-track" aria-hidden="true"><span class="corea11y-bar-fill %3$s" style="width:%4$s%%"></span></span><span class="corea11y-bar-value">%5$s</span></li>',
				esc_html( $row['label'] ),
				isset( $row['tip'] ) ? $row['tip'] : '',
				esc_attr( isset( $row['class'] ) ? $row['class'] : '' ),
				esc_attr( (string) $pct ),
				esc_html( number_format_i18n( (int) $row['value'] ) )
			);
		}
		return $out . '</ul></div>';
	}

	/**
	 * Before/after paired bars.
	 *
	 * @param array  $rows  Rows: [ 'label' => string, 'before' => int, 'after' => int, 'tip' => html ].
	 * @param string $title Chart title.
	 * @return string
	 */
	public static function paired_bars( array $rows, $title ) {
		$max = 1;
		foreach ( $rows as $row ) {
			$max = max( $max, (int) $row['before'], (int) $row['after'] );
		}
		$out  = '<div class="corea11y-chart"><h3 class="corea11y-chart-title">' . esc_html( $title ) . '</h3>';
		$out .= '<p class="corea11y-legend"><span class="corea11y-key corea11y-key-before" aria-hidden="true"></span>' . esc_html__( 'Before', 'corea11y' ) . ' <span class="corea11y-key corea11y-key-after" aria-hidden="true"></span>' . esc_html__( 'After', 'corea11y' ) . '</p><ul class="corea11y-bars corea11y-bars-paired">';
		foreach ( $rows as $row ) {
			$out .= sprintf(
				'<li><span class="corea11y-bar-label">%1$s%2$s</span><span class="corea11y-bar-pair"><span class="corea11y-bar-track" aria-hidden="true"><span class="corea11y-bar-fill corea11y-fill-before" style="width:%3$s%%"></span></span><span class="corea11y-bar-value"><span class="screen-reader-text">%4$s </span>%5$s</span><span class="corea11y-bar-track" aria-hidden="true"><span class="corea11y-bar-fill corea11y-fill-after" style="width:%6$s%%"></span></span><span class="corea11y-bar-value"><span class="screen-reader-text">%7$s </span>%8$s</span></span></li>',
				esc_html( $row['label'] ),
				isset( $row['tip'] ) ? $row['tip'] : '',
				esc_attr( (string) round( 100 * (int) $row['before'] / $max, 1 ) ),
				esc_html__( 'Before:', 'corea11y' ),
				esc_html( number_format_i18n( (int) $row['before'] ) ),
				esc_attr( (string) round( 100 * (int) $row['after'] / $max, 1 ) ),
				esc_html__( 'After:', 'corea11y' ),
				esc_html( number_format_i18n( (int) $row['after'] ) )
			);
		}
		return $out . '</ul></div>';
	}

	/**
	 * A stat tile.
	 *
	 * @param string $value   Display value.
	 * @param string $label   Label.
	 * @param string $tip     Optional tip HTML.
	 * @param string $variant Optional CSS modifier.
	 * @return string
	 */
	public static function stat( $value, $label, $tip = '', $variant = '' ) {
		return sprintf(
			'<div class="corea11y-stat-tile %4$s"><p class="corea11y-stat">%1$s</p><p class="corea11y-stat-label">%2$s%3$s</p></div>',
			esc_html( $value ),
			esc_html( $label ),
			$tip,
			esc_attr( $variant )
		);
	}

	/**
	 * Severity rows for a bar chart from impact counts.
	 *
	 * @param array $impact Counts keyed by impact.
	 * @return array
	 */
	public static function severity_rows( array $impact ) {
		$rows = array();
		foreach ( CoreA11y_Rules::impacts() as $key => $label ) {
			$rows[] = array(
				'label' => $label,
				'value' => isset( $impact[ $key ] ) ? (int) $impact[ $key ] : 0,
				'class' => 'corea11y-fill-' . $key,
				'tip'   => self::concept_tip( $key, $label ),
			);
		}
		return $rows;
	}

	/**
	 * Fix-type rows for a bar chart.
	 *
	 * @param array $tier Counts keyed by tier.
	 * @return array
	 */
	public static function tier_rows( array $tier ) {
		$rows = array();
		foreach ( CoreA11y_Rules::tiers() as $key => $label ) {
			$rows[] = array(
				'label' => $label,
				'value' => isset( $tier[ $key ] ) ? (int) $tier[ $key ] : 0,
				'class' => 'corea11y-fill-brand',
				'tip'   => self::concept_tip( $key, $label ),
			);
		}
		return $rows;
	}

	/**
	 * WCAG principle rows for a bar chart.
	 *
	 * @param array $principles Counts keyed by principle number 1-4.
	 * @return array
	 */
	public static function principle_rows( array $principles ) {
		$rows = array();
		foreach ( CoreA11y_Rules::principles() as $key => $p ) {
			$rows[] = array(
				'label' => $p['label'],
				'value' => isset( $principles[ $key ] ) ? (int) $principles[ $key ] : 0,
				'class' => 'corea11y-fill-brand',
				'tip'   => self::tip( $p['label'], $p['desc'] ),
			);
		}
		return $rows;
	}

	/**
	 * Plain-language result of re-checking a quick fix.
	 *
	 * @param array $v Verification record.
	 * @return string
	 */
	public static function verify_text( array $v ) {
		$before = number_format_i18n( (int) $v['before'] );
		$after  = number_format_i18n( (int) $v['after'] );
		$pages  = (int) $v['pages'];
		/* translators: %d: number of pages checked. */
		$checked = sprintf( _n( '%d page checked', '%d pages checked', $pages, 'corea11y' ), $pages );
		if ( 'verified' === $v['status'] ) {
			/* translators: 1: number of problems, 2: "3 pages checked". */
			return sprintf( _n( 'Fixed and verified: %1$s problem gone (%2$s)', 'Fixed and verified: %1$s problems gone (%2$s)', (int) $v['before'], 'corea11y' ), $before, $checked );
		}
		if ( 'partial' === $v['status'] ) {
			/* translators: 1: problems before, 2: problems still found, 3: "3 pages checked". */
			return sprintf( __( 'Partly fixed: %1$s problems before, %2$s still found (%3$s)', 'corea11y' ), $before, $after, $checked );
		}
		/* translators: 1: problems still found. */
		return sprintf( _n( '%1$s problem needs more than this fix. See the report.', '%1$s problems need more than this fix. See the report.', (int) $v['after'], 'corea11y' ), $after );
	}

	/**
	 * The CoreA11y dot mark (inline SVG, decorative).
	 *
	 * @param int $height Height in pixels.
	 * @return string
	 */
	public static function logo_mark( $height = 28 ) {
		$h = (int) $height;
		$w = (int) round( $h * 30 / 44 );
		return '<svg class="corea11y-mark" width="' . $w . '" height="' . $h . '" viewBox="0 0 30 44" aria-hidden="true" focusable="false">'
			. '<circle cx="7" cy="7" r="6" fill="#fff"/><circle cx="23" cy="7" r="6" fill="#dce85d"/>'
			. '<g fill="none" stroke="#8a8a8a" stroke-width="1.6"><circle cx="7" cy="22" r="5.2"/><circle cx="23" cy="22" r="5.2"/><circle cx="7" cy="37" r="5.2"/><circle cx="23" cy="37" r="5.2"/></g>'
			. '</svg>';
	}

	/**
	 * The "corea11y" wordmark, readable as "CoreA11y" by screen readers.
	 *
	 * @return string
	 */
	public static function wordmark() {
		return '<span class="corea11y-wordmark"><span aria-hidden="true">corea<span class="corea11y-wordmark-11">11</span>y</span><span class="screen-reader-text">CoreA11y</span></span>';
	}

	/**
	 * Branded bar shown at the top of every CoreA11y admin screen.
	 */
	public static function brand_bar() {
		echo '<div class="corea11y-brandbar">';
		echo self::logo_mark( 30 ) . self::wordmark(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Static markup.
		/* translators: %s: site name. */
		echo '<span class="corea11y-brandbar-site">' . esc_html( sprintf( __( 'Accessibility for %s', 'corea11y' ), get_bloginfo( 'name' ) ) ) . '</span>';
		/**
		 * Filter the User guide link in the CoreA11y top bar. Return an empty string to hide it.
		 *
		 * @param string $url Guide URL.
		 */
		$guide = (string) apply_filters( 'corea11y_guide_url', 'https://corea11y.com/docs/wordpress/' );
		if ( '' !== $guide ) {
			echo '<a class="corea11y-brandbar-guide" href="' . esc_url( $guide ) . '" target="_blank" rel="noopener">' . esc_html__( 'User guide', 'corea11y' ) . '<span class="screen-reader-text"> ' . esc_html__( '(opens in a new tab)', 'corea11y' ) . '</span></a>';
		}
		echo '</div>';
	}

	/**
	 * Relative luminance of a hex color (WCAG formula).
	 *
	 * @param string $hex Color like #1a7f5a.
	 * @return float
	 */
	public static function luminance( $hex ) {
		$hex = ltrim( (string) $hex, '#' );
		if ( 3 === strlen( $hex ) ) {
			$hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
		}
		$l = array();
		foreach ( array( 0, 2, 4 ) as $i ) {
			$c   = hexdec( substr( $hex, $i, 2 ) ) / 255;
			$l[] = $c <= 0.03928 ? $c / 12.92 : pow( ( $c + 0.055 ) / 1.055, 2.4 );
		}
		return 0.2126 * $l[0] + 0.7152 * $l[1] + 0.0722 * $l[2];
	}

	/**
	 * Contrast ratio between two hex colors.
	 *
	 * @param string $a Color.
	 * @param string $b Color.
	 * @return float
	 */
	public static function contrast( $a, $b ) {
		$x = self::luminance( $a );
		$y = self::luminance( $b );
		return ( max( $x, $y ) + 0.05 ) / ( min( $x, $y ) + 0.05 );
	}

	/**
	 * Darken a color step by step until it reads on white (4.5:1).
	 *
	 * @param string $hex Color.
	 * @return string
	 */
	public static function readable_on_white( $hex ) {
		$hex = '#' . ltrim( $hex, '#' );
		for ( $i = 0; $i < 20 && self::contrast( $hex, '#ffffff' ) < 4.5; $i++ ) {
			$rgb = sscanf( strlen( $hex ) === 4 ? '#' . $hex[1] . $hex[1] . $hex[2] . $hex[2] . $hex[3] . $hex[3] : $hex, '#%02x%02x%02x' );
			$hex = sprintf( '#%02x%02x%02x', $rgb[0] * 0.88, $rgb[1] * 0.88, $rgb[2] * 0.88 );
		}
		return $hex;
	}

	/**
	 * CSS custom properties for a white-label accent color, with readable text picked automatically.
	 *
	 * @param string $hex Brand color.
	 * @return string CSS declarations.
	 */
	public static function brand_css_vars( $hex ) {
		$hex  = sanitize_hex_color( $hex );
		if ( ! $hex ) {
			return '';
		}
		// Dark text on light colors when it reads at 4.5:1, otherwise white text on the color (darkened if needed).
		$dark = self::contrast( $hex, '#222222' ) < 4.5;
		$fg   = $dark ? '#ffffff' : '#222222';
		$text = self::readable_on_white( $hex );
		return '--corea11y-brand:' . $text . ';'
			. '--corea11y-brand-dark:' . $text . ';'
			. '--corea11y-stripe:' . $hex . ';'
			. '--corea11y-hero-bg:' . ( $dark ? $text : $hex ) . ';'
			. '--corea11y-hero-fg:' . $fg . ';'
			. '--corea11y-hero-muted:' . $fg . ';'
			. '--corea11y-ring:' . $fg . ';'
			. '--corea11y-ring-track:' . ( $dark ? 'rgba(255,255,255,.28)' : 'rgba(0,0,0,.16)' ) . ';';
	}
}
