<?php
/**
 * "More fixes in Pro" list on the Quick fixes screen of the free version.
 * Shows names and descriptions only; the Pro fixes themselves are not part of the free plugin.
 *
 * @package CoreA11y
 * Uses the Quick fixes view's $latest.
 */

defined( 'ABSPATH' ) || exit;

$corea11y_catalog = CoreA11y_Pro_Catalog::fixes();
$corea11y_pfound  = $latest ? CoreA11y_Pro_Catalog::found( (int) $latest->id ) : array();
$corea11y_upgrade = (string) apply_filters( 'corea11y_upgrade_url', 'https://corea11y.com/pricing/' );

// Fixes that would solve problems on this site first (most problems first), then the rest in catalog order.
$corea11y_order = array_keys( $corea11y_catalog );
usort(
	$corea11y_order,
	static function ( $a, $b ) use ( $corea11y_pfound, $corea11y_order ) {
		$na = isset( $corea11y_pfound[ $a ] ) ? $corea11y_pfound[ $a ] : 0;
		$nb = isset( $corea11y_pfound[ $b ] ) ? $corea11y_pfound[ $b ] : 0;
		if ( $na !== $nb ) {
			return $nb - $na;
		}
		return array_search( $a, $corea11y_order, true ) - array_search( $b, $corea11y_order, true );
	}
);
$corea11y_top  = array_slice( $corea11y_order, 0, 6 );
$corea11y_rest = array_diff( $corea11y_order, $corea11y_top );

$corea11y_pro_card = static function ( $id, $fix, $n, $level ) {
	?>
	<li class="corea11y-fix corea11y-fix-pro">
		<div class="corea11y-fix-text">
			<?php echo '<h' . (int) $level . ' class="corea11y-fix-title">' . esc_html( $fix['title'] ) . '</h' . (int) $level . '>'; ?>
			<p><?php echo esc_html( $fix['desc'] ); ?></p>
			<p class="corea11y-badges">
				<span class="corea11y-badge corea11y-badge-pro"><?php esc_html_e( 'Pro', 'msacalab-accessibility-checker' ); ?></span>
				<?php if ( '' !== $fix['wcag'] ) : ?>
					<span class="corea11y-badge">
						<?php
						/* translators: %s: WCAG success criterion numbers. */
						echo esc_html( sprintf( __( 'WCAG %s', 'msacalab-accessibility-checker' ), $fix['wcag'] ) );
						?>
					</span>
				<?php else : ?>
					<span class="corea11y-badge"><?php esc_html_e( 'Best practice', 'msacalab-accessibility-checker' ); ?></span>
				<?php endif; ?>
				<?php if ( $n ) : ?>
					<span class="corea11y-badge corea11y-badge-serious">
						<?php
						/* translators: %s: number of problems. */
						echo esc_html( sprintf( _n( 'Would fix %s problem on your site', 'Would fix %s problems on your site', $n, 'msacalab-accessibility-checker' ), number_format_i18n( $n ) ) );
						?>
					</span>
				<?php endif; ?>
			</p>
		</div>
		<div class="corea11y-fix-toggle corea11y-fix-lockmark" aria-hidden="true">
			<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" focusable="false"><rect x="4" y="11" width="16" height="10" rx="2"/><path d="M8 11V7a4 4 0 0 1 8 0v4"/></svg>
			<span><?php esc_html_e( 'Pro', 'msacalab-accessibility-checker' ); ?></span>
		</div>
	</li>
	<?php
};
?>
<section class="corea11y-section corea11y-pro-catalog" id="corea11y-pro-fixes" aria-labelledby="corea11y-pro-fixes-title">
	<h2 id="corea11y-pro-fixes-title">
		<?php
		/* translators: %s: number of Pro quick fixes. */
		echo esc_html( sprintf( __( '%s more fixes in MSACAlab Accessibility Checker Pro', 'msacalab-accessibility-checker' ), number_format_i18n( count( $corea11y_catalog ) ) ) );
		?>
	</h2>
	<p>
		<?php
		$corea11y_ptotal = array_sum( $corea11y_pfound );
		if ( $corea11y_ptotal ) {
			/* translators: %s: number of problems. */
			echo esc_html( sprintf( _n( 'Pro fixes would solve %s more problem found in your latest audit. Like the fixes above, each one is a single click and can be undone.', 'Pro fixes would solve %s more problems found in your latest audit. Like the fixes above, each one is a single click and can be undone.', $corea11y_ptotal, 'msacalab-accessibility-checker' ), number_format_i18n( $corea11y_ptotal ) ) );
		} else {
			esc_html_e( 'Like the fixes above, each one is a single click and can be undone.', 'msacalab-accessibility-checker' );
		}
		?>
	</p>

	<ul class="corea11y-fixes">
		<?php
		foreach ( $corea11y_top as $corea11y_pid ) {
			$corea11y_pro_card( $corea11y_pid, $corea11y_catalog[ $corea11y_pid ], isset( $corea11y_pfound[ $corea11y_pid ] ) ? $corea11y_pfound[ $corea11y_pid ] : 0, 3 );
		}
		?>
	</ul>

	<?php if ( $corea11y_rest ) : ?>
		<details class="corea11y-pro-more">
			<summary>
				<?php
				/* translators: %s: number of Pro quick fixes. */
				echo esc_html( sprintf( __( 'Show all %s Pro fixes', 'msacalab-accessibility-checker' ), number_format_i18n( count( $corea11y_catalog ) ) ) );
				?>
			</summary>
			<?php foreach ( CoreA11y_Pro_Catalog::groups() as $corea11y_group ) : ?>
				<?php
				$corea11y_gids = array_values( array_intersect( $corea11y_group['ids'], $corea11y_rest ) );
				if ( ! $corea11y_gids ) {
					continue;
				}
				?>
				<h3 class="corea11y-pro-group"><?php echo esc_html( $corea11y_group['title'] ); ?></h3>
				<ul class="corea11y-fixes">
					<?php
					foreach ( $corea11y_gids as $corea11y_pid ) {
						$corea11y_pro_card( $corea11y_pid, $corea11y_catalog[ $corea11y_pid ], isset( $corea11y_pfound[ $corea11y_pid ] ) ? $corea11y_pfound[ $corea11y_pid ] : 0, 4 );
					}
					?>
				</ul>
			<?php endforeach; ?>
		</details>
	<?php endif; ?>

	<p class="corea11y-actions">
		<a class="button button-primary" href="<?php echo esc_url( $corea11y_upgrade ); ?>"><?php esc_html_e( 'Upgrade to Pro', 'msacalab-accessibility-checker' ); ?><span class="screen-reader-text"> <?php esc_html_e( '(opens corea11y.com)', 'msacalab-accessibility-checker' ); ?></span></a>
		<span class="corea11y-muted"><?php esc_html_e( 'From $19 a month or $179 a year for one site, with a 30-day money-back guarantee.', 'msacalab-accessibility-checker' ); ?></span>
	</p>
</section>
