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
}
