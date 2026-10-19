<?php
/**
 * Rule metadata: WCAG mapping, fix tiers, impact weights.
 *
 * @package CoreA11y
 */

defined( 'ABSPATH' ) || exit;

/**
 * Static helpers that classify axe-core and CoreA11y rules.
 */
class CoreA11y_Rules {

	/**
	 * Allowed impact levels, most severe first.
	 *
	 * @return array<string,string>
	 */
	public static function impacts() {
		return array(
			'critical' => __( 'Critical', 'corea11y' ),
			'serious'  => __( 'Serious', 'corea11y' ),
			'moderate' => __( 'Moderate', 'corea11y' ),
			'minor'    => __( 'Minor', 'corea11y' ),
		);
	}

	/**
	 * Points per affected element, used for the score.
	 *
	 * @param string $impact Impact level.
	 * @return int
	 */
	public static function weight( $impact ) {
		$weights = array(
			'critical' => 4,
			'serious'  => 3,
			'moderate' => 2,
			'minor'    => 1,
		);
		return isset( $weights[ $impact ] ) ? $weights[ $impact ] : 1;
	}

	/**
	 * Fix tiers and labels.
	 *
	 * @return array<string,string>
	 */
	public static function tiers() {
		return array(
			'automatic' => __( 'Automatic fix', 'corea11y' ),
			'approval'  => __( 'Fix with approval', 'corea11y' ),
			'owner'     => __( 'Needs your action', 'corea11y' ),
		);
	}

	/**
	 * Which tier a rule falls into. Unknown rules default to "owner".
	 *
	 * @param string $rule_id Rule id.
	 * @return string
	 */
	public static function tier( $rule_id ) {
		$automatic = array( 'html-has-lang', 'html-lang-valid', 'html-xml-lang-mismatch', 'meta-viewport', 'bypass', 'document-title' );
		$approval  = array(
			'color-contrast',
			'link-name',
			'button-name',
			'input-button-name',
			'label',
			'select-name',
			'frame-title',
			'svg-img-alt',
			'target-size',
			'aria-allowed-attr',
			'aria-command-name',
			'aria-hidden-focus',
			'aria-input-field-name',
			'aria-prohibited-attr',
			'aria-required-attr',
			'aria-required-children',
			'aria-required-parent',
			'aria-roles',
			'aria-toggle-field-name',
			'aria-valid-attr',
			'aria-valid-attr-value',
			'nested-interactive',
			'no-autoplay-audio',
			'scrollable-region-focusable',
		);
		if ( in_array( $rule_id, $automatic, true ) ) {
			return 'automatic';
		}
		if ( in_array( $rule_id, $approval, true ) ) {
			return 'approval';
		}
		return 'owner';
	}

	/**
	 * Convert axe tags (e.g. "wcag1410") to criterion numbers ("1.4.10").
	 *
	 * @param array $tags axe-core tags.
	 * @return string Comma-separated criteria.
	 */
	public static function wcag_from_tags( $tags ) {
		$out = array();
		foreach ( (array) $tags as $tag ) {
			if ( preg_match( '/^wcag(\d)(\d)(\d{1,2})$/', (string) $tag, $m ) ) {
				$out[] = $m[1] . '.' . $m[2] . '.' . $m[3];
			}
		}
		return implode( ', ', array_unique( $out ) );
	}

	/**
	 * Sanitize an impact value.
	 *
	 * @param mixed $impact Raw value.
	 * @return string
	 */
	public static function sanitize_impact( $impact ) {
		$impact = sanitize_key( (string) $impact );
		return array_key_exists( $impact, self::impacts() ) ? $impact : 'minor';
	}

	/**
	 * Sanitize a rule id (lowercase letters, digits, hyphens).
	 *
	 * @param mixed $rule_id Raw value.
	 * @return string
	 */
	public static function sanitize_rule_id( $rule_id ) {
		return substr( preg_replace( '/[^a-z0-9\-]/', '', strtolower( (string) $rule_id ) ), 0, 100 );
	}
}
