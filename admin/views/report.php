<?php
/**
 * Audit report view.
 *
 * @package CoreA11y
 * @var object $audit  Audit row (summary decoded).
 * @var array  $counts Counts from CoreA11y_Audit::counts().
 * @var array  $groups Issues grouped by rule.
 */

defined( 'ABSPATH' ) || exit;

$corea11y_impacts = CoreA11y_Rules::impacts();
$corea11y_tiers   = CoreA11y_Rules::tiers();
$corea11y_pages   = isset( $audit->summary['pages'] ) ? (array) $audit->summary['pages'] : array();
$corea11y_failed  = array_filter(
	$corea11y_pages,
	static function ( $p ) {
		return 'error' === $p['status'];
	}
);
$corea11y_date    = $audit->started_at ? get_date_from_gmt( $audit->started_at, get_option( 'date_format' ) . ' ' . get_option( 'time_format' ) ) : '';
$corea11y_axe_ver = implode( '.', array_slice( explode( '.', CoreA11y_Audit::axe_version() ), 0, 2 ) );
?>
<div class="wrap corea11y">
	<p><a href="<?php echo esc_url( admin_url( 'admin.php?page=corea11y-audits' ) ); ?>">&larr; <?php esc_html_e( 'All audits', 'corea11y' ); ?></a></p>
	<h1>
		<?php
		/* translators: %s: date and time the audit started. */
		echo esc_html( sprintf( __( 'Audit report: %s', 'corea11y' ), $corea11y_date ) );
		?>
	</h1>

	<?php if ( 'complete' !== $audit->status ) : ?>
		<div class="notice notice-warning"><p>
			<?php
			/* translators: %s: audit status, e.g. "running" or "abandoned". */
			echo esc_html( sprintf( __( 'This audit did not finish (status: %s). Results may be incomplete.', 'corea11y' ), $audit->status ) );
			?>
		</p></div>
	<?php endif; ?>

	<div class="corea11y-grid">
		<div class="corea11y-card">
			<p class="corea11y-stat"><?php echo esc_html( number_format_i18n( (int) $counts['total'] ) ); ?></p>
			<p><?php esc_html_e( 'issues found', 'corea11y' ); ?></p>
		</div>
		<div class="corea11y-card">
			<p class="corea11y-stat"><?php echo esc_html( number_format_i18n( (int) $counts['rules'] ) ); ?></p>
			<p><?php esc_html_e( 'different problems', 'corea11y' ); ?></p>
		</div>
		<div class="corea11y-card">
			<p class="corea11y-stat">
				<?php
				/* translators: 1: pages scanned, 2: pages in the audit. */
				echo esc_html( sprintf( __( '%1$s / %2$s', 'corea11y' ), number_format_i18n( (int) $audit->pages_scanned ), number_format_i18n( (int) $audit->pages_total ) ) );
				?>
			</p>
			<p><?php esc_html_e( 'pages scanned', 'corea11y' ); ?></p>
		</div>
		<div class="corea11y-card">
			<p class="corea11y-stat"><?php echo null === $audit->score ? '—' : esc_html( (int) $audit->score ); ?></p>
			<p><?php esc_html_e( 'scan score out of 100', 'corea11y' ); ?></p>
		</div>
	</div>

	<table class="widefat corea11y-counts">
		<caption><?php esc_html_e( 'Issues by severity and fix type', 'corea11y' ); ?></caption>
		<thead>
			<tr>
				<?php foreach ( $corea11y_impacts as $corea11y_label ) : ?>
					<th scope="col"><?php echo esc_html( $corea11y_label ); ?></th>
				<?php endforeach; ?>
				<?php foreach ( $corea11y_tiers as $corea11y_label ) : ?>
					<th scope="col"><?php echo esc_html( $corea11y_label ); ?></th>
				<?php endforeach; ?>
			</tr>
		</thead>
		<tbody>
			<tr>
				<?php foreach ( array_keys( $corea11y_impacts ) as $corea11y_key ) : ?>
					<td><?php echo esc_html( number_format_i18n( (int) $counts['impact'][ $corea11y_key ] ) ); ?></td>
				<?php endforeach; ?>
				<?php foreach ( array_keys( $corea11y_tiers ) as $corea11y_key ) : ?>
					<td><?php echo esc_html( number_format_i18n( (int) $counts['tier'][ $corea11y_key ] ) ); ?></td>
				<?php endforeach; ?>
			</tr>
		</tbody>
	</table>

	<?php if ( ! empty( $audit->summary['truncated'] ) ) : ?>
		<p class="description">
			<?php
			/* translators: %s: number of additional elements not listed. */
			echo esc_html( sprintf( __( 'To keep reports readable, up to 25 elements are listed per problem per page. %s more matching elements were found but not listed.', 'corea11y' ), number_format_i18n( (int) $audit->summary['truncated'] ) ) );
			?>
		</p>
	<?php endif; ?>

	<h2><?php esc_html_e( 'Problems found', 'corea11y' ); ?></h2>

	<?php if ( empty( $groups ) ) : ?>
		<p><?php esc_html_e( 'No issues were found by the automated checks. Remember to complete the manual checklist too.', 'corea11y' ); ?></p>
	<?php endif; ?>

	<?php foreach ( $groups as $corea11y_group ) : ?>
		<?php
		$corea11y_n     = count( $corea11y_group['items'] );
		$corea11y_pages_n = count( $corea11y_group['pages'] );
		?>
		<section class="corea11y-card corea11y-rule corea11y-impact-<?php echo esc_attr( $corea11y_group['impact'] ); ?>">
			<h3><?php echo esc_html( $corea11y_group['help'] ); ?></h3>
			<p class="corea11y-badges">
				<span class="corea11y-badge corea11y-badge-<?php echo esc_attr( $corea11y_group['impact'] ); ?>"><?php echo esc_html( $corea11y_impacts[ $corea11y_group['impact'] ] ); ?></span>
				<?php if ( $corea11y_group['wcag'] ) : ?>
					<span class="corea11y-badge">
						<?php
						/* translators: %s: WCAG success criterion numbers. */
						echo esc_html( sprintf( __( 'WCAG %s', 'corea11y' ), $corea11y_group['wcag'] ) );
						?>
					</span>
				<?php endif; ?>
				<span class="corea11y-badge"><?php echo esc_html( $corea11y_tiers[ $corea11y_group['tier'] ] ); ?></span>
				<span class="corea11y-badge">
					<?php
					/* translators: 1: number of elements, 2: number of pages. */
					echo esc_html( sprintf( _n( '%1$s element', '%1$s elements', $corea11y_n, 'corea11y' ), number_format_i18n( $corea11y_n ) ) . ' · ' . sprintf( _n( '%s page', '%s pages', $corea11y_pages_n, 'corea11y' ), number_format_i18n( $corea11y_pages_n ) ) );
					?>
				</span>
			</p>
			<?php if ( 'axe' === $corea11y_group['source'] ) : ?>
				<p>
					<a href="<?php echo esc_url( 'https://dequeuniversity.com/rules/axe/' . $corea11y_axe_ver . '/' . $corea11y_group['rule'] ); ?>" target="_blank" rel="noopener noreferrer">
						<?php esc_html_e( 'How to fix this', 'corea11y' ); ?><span class="screen-reader-text"> <?php esc_html_e( '(opens in a new tab)', 'corea11y' ); ?></span>
					</a>
				</p>
			<?php endif; ?>
			<details>
				<summary><?php esc_html_e( 'Show where', 'corea11y' ); ?></summary>
				<ul class="corea11y-instances">
					<?php foreach ( $corea11y_group['items'] as $corea11y_item ) : ?>
						<li>
							<p>
								<a href="<?php echo esc_url( $corea11y_item->page_url ); ?>"><?php echo esc_html( $corea11y_item->page_label ? $corea11y_item->page_label : $corea11y_item->page_url ); ?></a>
								<?php if ( $corea11y_item->object_id && current_user_can( 'edit_post', (int) $corea11y_item->object_id ) ) : ?>
									· <a href="<?php echo esc_url( get_edit_post_link( (int) $corea11y_item->object_id ) ); ?>"><?php esc_html_e( 'Edit', 'corea11y' ); ?></a>
								<?php endif; ?>
							</p>
							<?php if ( $corea11y_item->selector ) : ?>
								<p><?php esc_html_e( 'Element:', 'corea11y' ); ?> <code><?php echo esc_html( $corea11y_item->selector ); ?></code></p>
							<?php endif; ?>
							<?php if ( $corea11y_item->snippet ) : ?>
								<pre class="corea11y-snippet"><code><?php echo esc_html( $corea11y_item->snippet ); ?></code></pre>
							<?php endif; ?>
							<?php if ( $corea11y_item->detail ) : ?>
								<p class="corea11y-detail"><?php echo nl2br( esc_html( $corea11y_item->detail ) ); ?></p>
							<?php endif; ?>
						</li>
					<?php endforeach; ?>
				</ul>
			</details>
		</section>
	<?php endforeach; ?>

	<?php if ( $corea11y_failed ) : ?>
		<h2><?php esc_html_e( 'Pages that could not be scanned', 'corea11y' ); ?></h2>
		<ul>
			<?php foreach ( $corea11y_failed as $corea11y_page ) : ?>
				<li><a href="<?php echo esc_url( $corea11y_page['url'] ); ?>"><?php echo esc_html( $corea11y_page['label'] ); ?></a>: <?php echo esc_html( isset( $corea11y_page['error'] ) ? $corea11y_page['error'] : '' ); ?></li>
			<?php endforeach; ?>
		</ul>
	<?php endif; ?>

	<p class="corea11y-note">
		<?php
		/* translators: %s: axe-core version. */
		echo esc_html( sprintf( __( 'Scanned with axe-core %s against WCAG 2.0, 2.1 and 2.2 Level A and AA rules, plus CoreA11y content checks. Automated checks find only part of all accessibility issues; use the manual checklist for the rest.', 'corea11y' ), CoreA11y_Audit::axe_version() ) );
		?>
	</p>
</div>
