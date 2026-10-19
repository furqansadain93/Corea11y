<?php
/**
 * Report body shared by the admin screen and the PDF export.
 *
 * @package CoreA11y
 * @var object $audit      Audit row (summary decoded).
 * @var array  $counts     Counts from CoreA11y_Audit::counts().
 * @var array  $groups     Issues grouped by rule.
 * @var array  $principles Counts per WCAG principle.
 * @var array  $top_pages  Pages with most issues.
 * @var bool   $corea11y_is_print   True when rendering for PDF.
 */

defined( 'ABSPATH' ) || exit;

$corea11y_impacts = CoreA11y_Rules::impacts();
$corea11y_tiers   = CoreA11y_Rules::tiers();
$corea11y_band    = CoreA11y_UI::band( null === $audit->score ? null : (int) $audit->score );
$corea11y_pages   = isset( $audit->summary['pages'] ) ? (array) $audit->summary['pages'] : array();
$corea11y_failed  = array_filter(
	$corea11y_pages,
	static function ( $p ) {
		return 'error' === $p['status'];
	}
);
$corea11y_axe_ver = implode( '.', array_slice( explode( '.', CoreA11y_Audit::axe_version() ), 0, 2 ) );
$corea11y_max_el  = $corea11y_is_print ? 5 : PHP_INT_MAX;

if ( 0 === (int) $counts['total'] ) {
	$corea11y_headline = __( 'No problems found by the automated checks. Wonderful work!', 'corea11y' );
	$corea11y_sub      = __( 'Finish the job with the manual checklist: it covers what software can\'t judge.', 'corea11y' );
} elseif ( 'great' === $corea11y_band['key'] ) {
	$corea11y_headline = __( 'Your site is in great shape.', 'corea11y' );
	$corea11y_sub      = __( 'A handful of fixes below and you\'re there.', 'corea11y' );
} elseif ( 'good' === $corea11y_band['key'] ) {
	$corea11y_headline = __( 'A solid foundation to build on.', 'corea11y' );
	$corea11y_sub      = __( 'Start with the critical problems: they make the biggest difference for your visitors.', 'corea11y' );
} else {
	$corea11y_headline = __( 'There\'s work to do, and you\'re in the right place.', 'corea11y' );
	$corea11y_sub      = __( 'Start with the critical problems. Fixing one problem type often clears many issues at once.', 'corea11y' );
}
?>
<section class="corea11y-hero" aria-labelledby="corea11y-hero-title">
	<?php echo CoreA11y_UI::score_ring( null === $audit->score ? null : (int) $audit->score ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped in CoreA11y_UI. ?>
	<div class="corea11y-hero-text">
		<h2 id="corea11y-hero-title"><?php echo esc_html( $corea11y_headline ); ?></h2>
		<p><?php echo esc_html( $corea11y_sub ); ?></p>
		<div class="corea11y-tiles">
			<?php
			// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped in CoreA11y_UI.
			echo CoreA11y_UI::stat( number_format_i18n( (int) $counts['total'] ), __( 'issues found', 'corea11y' ), CoreA11y_UI::concept_tip( 'issues', __( 'issues found', 'corea11y' ) ) );
			echo CoreA11y_UI::stat( number_format_i18n( (int) $counts['rules'] ), __( 'types of problem', 'corea11y' ), CoreA11y_UI::concept_tip( 'problems', __( 'types of problem', 'corea11y' ) ) );
			echo CoreA11y_UI::stat( number_format_i18n( (int) $audit->pages_scanned ) . ' / ' . number_format_i18n( (int) $audit->pages_total ), __( 'pages scanned', 'corea11y' ), CoreA11y_UI::concept_tip( 'pages', __( 'pages scanned', 'corea11y' ) ) );
			echo CoreA11y_UI::stat( number_format_i18n( (int) $counts['impact']['critical'] ), __( 'critical', 'corea11y' ), CoreA11y_UI::concept_tip( 'critical', __( 'critical', 'corea11y' ) ), 'corea11y-tile-critical' );
			// phpcs:enable
			?>
		</div>
		<p class="corea11y-score-note"><?php esc_html_e( 'About the score', 'corea11y' ); ?><?php echo CoreA11y_UI::concept_tip( 'score', __( 'Scan score', 'corea11y' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></p>
	</div>
</section>

<?php if ( (int) $counts['total'] > 0 ) : ?>
	<section class="corea11y-charts" aria-label="<?php esc_attr_e( 'Charts', 'corea11y' ); ?>">
		<?php
		// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped in CoreA11y_UI.
		echo CoreA11y_UI::bars( CoreA11y_UI::severity_rows( $counts['impact'] ), __( 'Issues by severity', 'corea11y' ) );
		echo CoreA11y_UI::bars( CoreA11y_UI::principle_rows( $principles ), __( 'Issues by WCAG principle', 'corea11y' ) );
		echo CoreA11y_UI::bars( CoreA11y_UI::tier_rows( $counts['tier'] ), __( 'Issues by type of fix', 'corea11y' ) );
		// phpcs:enable
		?>
	</section>

	<?php if ( $top_pages ) : ?>
		<section class="corea11y-section">
			<h2><?php esc_html_e( 'Pages with the most issues', 'corea11y' ); ?></h2>
			<table class="widefat striped corea11y-table">
				<thead><tr><th scope="col"><?php esc_html_e( 'Page', 'corea11y' ); ?></th><th scope="col" class="num"><?php esc_html_e( 'Issues', 'corea11y' ); ?></th></tr></thead>
				<tbody>
					<?php foreach ( $top_pages as $corea11y_tp ) : ?>
						<tr>
							<td><a href="<?php echo esc_url( $corea11y_tp->page_url ); ?>"><?php echo esc_html( $corea11y_tp->page_label ? $corea11y_tp->page_label : $corea11y_tp->page_url ); ?></a><?php if ( $corea11y_is_print ) : ?><br><span class="corea11y-url"><?php echo esc_html( $corea11y_tp->page_url ); ?></span><?php endif; ?></td>
							<td class="num"><?php echo esc_html( number_format_i18n( (int) $corea11y_tp->n ) ); ?></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</section>
	<?php endif; ?>
<?php endif; ?>

<section class="corea11y-section">
	<h2><?php esc_html_e( 'What to fix', 'corea11y' ); ?></h2>
	<?php if ( empty( $groups ) ) : ?>
		<p class="corea11y-celebrate"><?php esc_html_e( 'Nothing to fix from the automated checks. Head to the manual checklist next.', 'corea11y' ); ?></p>
	<?php else : ?>
		<p><?php esc_html_e( 'Problems are listed most severe first. Select "?" next to any problem to learn why it matters and how to fix it.', 'corea11y' ); ?></p>
	<?php endif; ?>

	<?php foreach ( $groups as $corea11y_group ) : ?>
		<?php
		$corea11y_ex      = CoreA11y_Explain::get( $corea11y_group['rule'], $corea11y_group['help'] );
		$corea11y_n       = count( $corea11y_group['items'] );
		$corea11y_pages_n = count( $corea11y_group['pages'] );
		?>
		<article class="corea11y-rule corea11y-impact-<?php echo esc_attr( $corea11y_group['impact'] ); ?>">
			<h3>
				<?php echo esc_html( $corea11y_ex['title'] ); ?>
				<?php
				if ( ! $corea11y_is_print ) {
					echo CoreA11y_UI::rule_tip( $corea11y_ex ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped in CoreA11y_UI.
				}
				?>
			</h3>
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
				<span class="corea11y-badge corea11y-badge-count">
					<?php
					/* translators: 1: number of elements, 2: number of pages. */
					echo esc_html( sprintf( _n( '%s element', '%s elements', $corea11y_n, 'corea11y' ), number_format_i18n( $corea11y_n ) ) . ' · ' . sprintf( _n( 'on %s page', 'on %s pages', $corea11y_pages_n, 'corea11y' ), number_format_i18n( $corea11y_pages_n ) ) );
					?>
				</span>
			</p>

			<?php
			$corea11y_fix_id = CoreA11y_Fixes::for_rule( $corea11y_group['rule'] );
			if ( $corea11y_fix_id && ! $corea11y_is_print ) :
				$corea11y_fix = CoreA11y_Fixes::all()[ $corea11y_fix_id ];
				?>
				<p class="corea11y-quickfix">
					<?php if ( CoreA11y_Fixes::is_on( $corea11y_fix_id ) ) : ?>
						<span class="corea11y-badge corea11y-badge-good"><?php esc_html_e( 'Quick fix is on', 'corea11y' ); ?></span>
						<?php esc_html_e( 'Run a new audit to confirm it resolved these issues.', 'corea11y' ); ?>
					<?php else : ?>
						<strong><?php esc_html_e( 'One-click fix available:', 'corea11y' ); ?></strong>
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=corea11y-fixes#corea11y-fix-' . $corea11y_fix_id ) ); ?>"><?php echo esc_html( $corea11y_fix['title'] ); ?></a>
					<?php endif; ?>
				</p>
			<?php endif; ?>

			<?php if ( $corea11y_is_print ) : ?>
				<dl class="corea11y-explain">
					<dt><?php esc_html_e( 'Why it matters', 'corea11y' ); ?></dt><dd><?php echo esc_html( $corea11y_ex['why'] ); ?></dd>
					<dt><?php esc_html_e( 'Who it affects', 'corea11y' ); ?></dt><dd><?php echo esc_html( $corea11y_ex['who'] ); ?></dd>
					<dt><?php esc_html_e( 'How to fix', 'corea11y' ); ?></dt><dd><?php echo esc_html( $corea11y_ex['fix'] ); ?></dd>
				</dl>
			<?php else : ?>
				<p class="corea11y-fix-line"><strong><?php esc_html_e( 'How to fix:', 'corea11y' ); ?></strong> <?php echo esc_html( $corea11y_ex['fix'] ); ?></p>
			<?php endif; ?>

			<?php if ( $corea11y_group['help'] && $corea11y_group['help'] !== $corea11y_ex['title'] ) : ?>
				<p class="corea11y-technical">
					<?php
					/* translators: %s: the technical rule description from the testing engine. */
					echo esc_html( sprintf( __( 'Technical check: %s', 'corea11y' ), $corea11y_group['help'] ) );
					?>
					<?php if ( 'axe' === $corea11y_group['source'] && ! $corea11y_is_print ) : ?>
						· <a href="<?php echo esc_url( 'https://dequeuniversity.com/rules/axe/' . $corea11y_axe_ver . '/' . $corea11y_group['rule'] ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Detailed guide', 'corea11y' ); ?><span class="screen-reader-text"> <?php esc_html_e( '(opens in a new tab)', 'corea11y' ); ?></span></a>
					<?php endif; ?>
				</p>
			<?php endif; ?>

			<?php if ( $corea11y_is_print ) : ?>
				<p class="corea11y-where-title"><?php esc_html_e( 'Where it was found', 'corea11y' ); ?></p>
			<?php else : ?>
				<details>
				<summary>
					<?php
					/* translators: %s: number of elements. */
					echo esc_html( sprintf( _n( 'Show the %s element', 'Show all %s elements', $corea11y_n, 'corea11y' ), number_format_i18n( $corea11y_n ) ) );
					?>
				</summary>
			<?php endif; ?>
				<ul class="corea11y-instances">
					<?php foreach ( array_slice( $corea11y_group['items'], 0, $corea11y_max_el ) as $corea11y_item ) : ?>
						<li>
							<p class="corea11y-instance-page">
								<a href="<?php echo esc_url( $corea11y_item->page_url ); ?>"><?php echo esc_html( $corea11y_item->page_label ? $corea11y_item->page_label : $corea11y_item->page_url ); ?></a>
								<?php if ( ! $corea11y_is_print && 'axe' === $corea11y_item->source && $corea11y_item->selector ) : ?>
									· <a href="<?php echo esc_url( CoreA11y_Highlighter::url( $audit->id, $corea11y_item->page_url, $corea11y_item->id ) ); ?>"><?php esc_html_e( 'Show on page', 'corea11y' ); ?><span class="screen-reader-text"> <?php echo esc_html( $corea11y_item->page_label ); ?></span></a>
								<?php endif; ?>
								<?php if ( ! $corea11y_is_print && $corea11y_item->object_id && current_user_can( 'edit_post', (int) $corea11y_item->object_id ) ) : ?>
									· <a href="<?php echo esc_url( get_edit_post_link( (int) $corea11y_item->object_id ) ); ?>"><?php esc_html_e( 'Edit', 'corea11y' ); ?><span class="screen-reader-text"> <?php echo esc_html( $corea11y_item->page_label ); ?></span></a>
								<?php endif; ?>
							</p>
							<?php if ( $corea11y_item->snippet ) : ?>
								<pre class="corea11y-snippet"><code><?php echo esc_html( $corea11y_item->snippet ); ?></code></pre>
							<?php endif; ?>
							<?php if ( $corea11y_item->detail && ! $corea11y_is_print ) : ?>
								<p class="corea11y-detail"><?php echo nl2br( esc_html( $corea11y_item->detail ) ); ?></p>
							<?php endif; ?>
							<?php if ( ! $corea11y_is_print ) : ?>
								<details class="corea11y-dismiss">
									<summary>
										<?php esc_html_e( 'Dismiss', 'corea11y' ); ?><span class="screen-reader-text"> <?php echo esc_html( $corea11y_ex['title'] . ', ' . $corea11y_item->page_label ); ?></span>
									</summary>
									<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
										<input type="hidden" name="action" value="corea11y_dismiss">
										<input type="hidden" name="issue" value="<?php echo esc_attr( $corea11y_item->id ); ?>">
										<input type="hidden" name="audit" value="<?php echo esc_attr( $audit->id ); ?>">
										<?php wp_nonce_field( 'corea11y_dismiss' ); ?>
										<fieldset>
											<legend><?php esc_html_e( 'Why are you dismissing this?', 'corea11y' ); ?></legend>
											<?php foreach ( CoreA11y_Dismiss::reasons() as $corea11y_rk => $corea11y_rl ) : ?>
												<label><input type="radio" name="reason" value="<?php echo esc_attr( $corea11y_rk ); ?>" <?php checked( 'false_positive', $corea11y_rk ); ?>> <?php echo esc_html( $corea11y_rl ); ?></label><br>
											<?php endforeach; ?>
										</fieldset>
										<p>
											<label for="<?php echo esc_attr( 'corea11y-note-' . $corea11y_item->id ); ?>"><?php esc_html_e( 'Note (optional)', 'corea11y' ); ?></label><br>
											<textarea id="<?php echo esc_attr( 'corea11y-note-' . $corea11y_item->id ); ?>" name="note" rows="2" class="large-text"></textarea>
										</p>
										<p><button type="submit" class="button"><?php esc_html_e( 'Dismiss issue', 'corea11y' ); ?></button></p>
									</form>
								</details>
							<?php endif; ?>
						</li>
					<?php endforeach; ?>
				</ul>
				<?php if ( $corea11y_n > $corea11y_max_el ) : ?>
					<p class="corea11y-more">
						<?php
						/* translators: %s: number of further elements. */
						echo esc_html( sprintf( __( '…and %s more. See the full list in CoreA11y.', 'corea11y' ), number_format_i18n( $corea11y_n - $corea11y_max_el ) ) );
						?>
					</p>
				<?php endif; ?>
			<?php if ( ! $corea11y_is_print ) : ?>
				</details>
			<?php endif; ?>
		</article>
	<?php endforeach; ?>
</section>

<?php if ( ! empty( $dismissed ) ) : ?>
	<section class="corea11y-section">
		<h2>
			<?php
			/* translators: %s: number of dismissed issues. */
			echo esc_html( sprintf( __( 'Dismissed issues (%s)', 'corea11y' ), number_format_i18n( count( $dismissed ) ) ) );
			?>
		</h2>
		<p><?php esc_html_e( 'These issues are not counted in the score or totals. Restore any of them if circumstances change.', 'corea11y' ); ?></p>
		<table class="widefat striped corea11y-table">
			<thead><tr><th scope="col"><?php esc_html_e( 'Problem', 'corea11y' ); ?></th><th scope="col"><?php esc_html_e( 'Page', 'corea11y' ); ?></th><th scope="col"><?php esc_html_e( 'Reason', 'corea11y' ); ?></th><th scope="col"><?php esc_html_e( 'By', 'corea11y' ); ?></th><?php if ( ! $corea11y_is_print ) : ?><th scope="col"><?php esc_html_e( 'Restore', 'corea11y' ); ?></th><?php endif; ?></tr></thead>
			<tbody>
				<?php foreach ( $dismissed as $corea11y_d ) : ?>
					<?php
					$corea11y_dex  = CoreA11y_Explain::get( $corea11y_d->rule_id, $corea11y_d->help );
					$corea11y_rsn  = CoreA11y_Dismiss::reasons();
					$corea11y_user = get_userdata( (int) $corea11y_d->dismissed_by );
					?>
					<tr>
						<td><?php echo esc_html( $corea11y_dex['title'] ); ?></td>
						<td><?php echo esc_html( $corea11y_d->page_label ? $corea11y_d->page_label : $corea11y_d->page_url ); ?></td>
						<td><?php echo esc_html( isset( $corea11y_rsn[ $corea11y_d->reason ] ) ? $corea11y_rsn[ $corea11y_d->reason ] : $corea11y_d->reason ); ?><?php echo $corea11y_d->note ? '<br><span class="corea11y-muted">' . esc_html( $corea11y_d->note ) . '</span>' : ''; ?></td>
						<td><?php echo esc_html( $corea11y_user ? $corea11y_user->display_name : '—' ); ?></td>
						<?php if ( ! $corea11y_is_print ) : ?>
							<td>
								<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
									<input type="hidden" name="action" value="corea11y_restore">
									<input type="hidden" name="fp" value="<?php echo esc_attr( $corea11y_d->fingerprint ); ?>">
									<input type="hidden" name="audit" value="<?php echo esc_attr( $audit->id ); ?>">
									<?php wp_nonce_field( 'corea11y_restore' ); ?>
									<button type="submit" class="button button-small"><?php esc_html_e( 'Restore', 'corea11y' ); ?><span class="screen-reader-text"> <?php echo esc_html( $corea11y_dex['title'] . ', ' . $corea11y_d->page_label ); ?></span></button>
								</form>
							</td>
						<?php endif; ?>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	</section>
<?php endif; ?>

<?php if ( $corea11y_failed ) : ?>
	<section class="corea11y-section">
		<h2><?php esc_html_e( 'Pages that could not be scanned', 'corea11y' ); ?></h2>
		<ul>
			<?php foreach ( $corea11y_failed as $corea11y_page ) : ?>
				<li><a href="<?php echo esc_url( $corea11y_page['url'] ); ?>"><?php echo esc_html( $corea11y_page['label'] ); ?></a>: <?php echo esc_html( isset( $corea11y_page['error'] ) ? $corea11y_page['error'] : '' ); ?></li>
			<?php endforeach; ?>
		</ul>
	</section>
<?php endif; ?>

<?php if ( ! empty( $audit->summary['truncated'] ) ) : ?>
	<p class="corea11y-note">
		<?php
		/* translators: %s: number of additional elements not listed. */
		echo esc_html( sprintf( __( 'To keep reports readable, up to 25 elements are listed per problem per page. %s more matching elements were found but not listed.', 'corea11y' ), number_format_i18n( (int) $audit->summary['truncated'] ) ) );
		?>
	</p>
<?php endif; ?>

<p class="corea11y-note">
	<?php
	/* translators: %s: axe-core version. */
	echo esc_html( sprintf( __( 'Scanned with axe-core %s against WCAG 2.0, 2.1 and 2.2 Level A and AA rules, plus CoreA11y content checks. Automated checks find only part of all accessibility issues; the manual checklist covers the rest. This report does not certify WCAG conformance.', 'corea11y' ), CoreA11y_Audit::axe_version() ) );
	?>
</p>
