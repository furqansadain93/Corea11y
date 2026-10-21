<?php
/**
 * CoreA11y Pro license and plan checks.
 *
 * Pro features need an active license. If a license expires, nothing breaks: fixes
 * that are already on keep working and can still be turned off or undone. Only
 * turning on new Pro fixes and running Pro tools waits for renewal.
 *
 * @package CoreA11y
 */

defined( 'ABSPATH' ) || exit;

/**
 * License helpers.
 */
class CoreA11y_Pro_License {

	const PERSONAL = 1;
	const BUSINESS = 2;
	const AGENCY   = 3;

	/**
	 * Hook into Lite.
	 */
	public static function register() {
		add_filter( 'corea11y_fix_locked', array( __CLASS__, 'lock_fix' ), 10, 2 );
		add_filter( 'corea11y_license_url', array( __CLASS__, 'license_url' ) );
	}

	/**
	 * Whether Pro features can be used (active license or trial).
	 *
	 * @return bool
	 */
	public static function licensed() {
		$ok = function_exists( 'corea11y_fs' ) ? corea11y_fs()->can_use_premium_code() : false;

		/**
		 * Filter whether CoreA11y Pro is licensed on this site.
		 *
		 * @param bool $ok Licensed.
		 */
		return (bool) apply_filters( 'corea11y_pro_licensed', $ok );
	}

	/**
	 * The plan this site is on: 0 (none), 1 Personal, 2 Business, 3 Agency.
	 *
	 * @return int
	 */
	public static function tier() {
		$tier = 0;
		if ( self::licensed() ) {
			$tier = self::PERSONAL;
			if ( function_exists( 'corea11y_fs' ) ) {
				$plan = strtolower( (string) corea11y_fs()->get_plan_name() );
				if ( false !== strpos( $plan, 'agency' ) ) {
					$tier = self::AGENCY;
				} elseif ( false !== strpos( $plan, 'business' ) ) {
					$tier = self::BUSINESS;
				}
			}
		}

		/**
		 * Filter the CoreA11y Pro plan tier.
		 *
		 * @param int $tier 0 none, 1 Personal, 2 Business, 3 Agency.
		 */
		return (int) apply_filters( 'corea11y_pro_tier', $tier );
	}

	/**
	 * Whether the site's plan includes a tier.
	 *
	 * @param int $tier Minimum tier.
	 * @return bool
	 */
	public static function has( $tier ) {
		return self::tier() >= $tier;
	}

	/**
	 * Lock Pro fixes without a license.
	 *
	 * @param bool   $locked Locked.
	 * @param string $id     Fix id.
	 * @return bool
	 */
	public static function lock_fix( $locked, $id ) {
		if ( $locked ) {
			return true;
		}
		$fixes = CoreA11y_Fixes::all();
		return ! empty( $fixes[ $id ]['pro'] ) && ! self::licensed();
	}

	/**
	 * Where to activate or renew the license.
	 *
	 * @return string
	 */
	public static function license_url() {
		if ( function_exists( 'corea11y_fs' ) ) {
			$fs = corea11y_fs();
			if ( $fs->is_registered() && $fs->has_any_license() ) {
				return $fs->get_account_url();
			}
			if ( method_exists( $fs, 'get_activation_url' ) ) {
				return $fs->get_activation_url();
			}
		}
		return admin_url( 'plugins.php' );
	}
}
