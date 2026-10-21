<?php
/**
 * Colour contrast approval queue screen.
 *
 * @package CoreA11y
 * @var array $data     Suggestions: audit, groups, skipped.
 * @var bool  $licensed Pro license active.
 * @var array $applied  Approved colour changes.
 * @var array $log      Change history.
 */

defined( 'ABSPATH' ) || exit;

// phpcs:disable WordPress.Security.NonceVerification.Recommended -- Display-only flags after redirect.
$corea11y_c_applied  = isset( $_GET['applied'] ) ? absint( $_GET['applied'] ) : 0;
$corea11y_c_pages    = isset( $_GET['pages'] ) ? absint( $_GET['pages'] ) : 0;
$corea11y_c_adjusted = isset( $_GET['adjusted'] ) ? absint( $_GET['adjusted'] ) : 0;
$corea11y_c_batch    = isset( $_GET['batch'] ) ? sanitize_text_field( wp_unslash( $_GET['batch'] ) ) : '';
$corea11y_c_undone   = isset( $_GET['undone'] ) ? absint( $_GET['undone'] ) : -1;
$corea11y_c_none     = isset( $_GET['none'] );
$corea11y_c_locked   = isset( $_GET['locked'] );
// phpcs:enable
$corea11y_c_groups = $data['groups'];
?>
<div class="wrap corea11y">
	<?php CoreA11y_UI::brand_bar(); ?>
	<div class="corea11y-page-head">
		<div>
			<h1><?php esc_html_e( 'Colour contrast', 'corea11y' ); ?></h1>
			<p class="corea11y-meta"><?php esc_html_e( 'Text that is hard to read against its background. CoreA11y suggests the closest colour that passes WCAG 2.2 AA. Nothing changes until you approve it, and every change can be undone.', 'corea11y' ); ?></p>
		</div>
	</div>

	<div id="corea11y-notice" tabindex="-1">
		<?php if ( $corea11y_c_applied ) : ?>
			<div class="notice notice-success inline corea11y-verify" role="status">
				<p>
					<strong>
						<?php
						/* translators: 1: number of elements, 2: number of pages. */
						echo esc_html( sprintf( _n( 'New colour applied to %1$s element on %2$s page.', 'New colours applied to %1$s elements on %2$s pages.', $corea11y_c_applied, 'corea11y' ), number_format_i18n( $corea11y_c_applied ), number_format_i18n( $corea11y_c_pages ) ) );
						?>
					</strong>
					<?php esc_html_e( 'Run a new audit to see your updated score.', 'corea11y' ); ?>
				</p>
				<?php if ( $corea11y_c_adjusted ) : ?>
					<p><?php esc_html_e( 'A colour you typed did not reach the required contrast, so the suggested colour was used instead.', 'corea11y' ); ?></p>
				<?php endif; ?>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<input type="hidden" name="action" value="corea11y_contrast_undo">
					<input type="hidden" name="batch" value="<?php echo esc_attr( $corea11y_c_batch ); ?>">
					<?php wp_nonce_field( 'corea11y_contrast_undo' ); ?>
					<p>
						<a class="button button-primary" href="<?php echo esc_url( admin_url( 'admin.php?page=corea11y' ) ); ?>"><?php esc_html_e( 'Run a new audit', 'corea11y' ); ?></a>
						<button type="submit" class="button"><?php esc_html_e( 'Undo', 'corea11y' ); ?></button>
					</p>
				</form>
			</div>
		<?php elseif ( $corea11y_c_undone >= 0 ) : ?>
			<div class="notice notice-info inline" role="status"><p>
				<?php
				/* translators: %s: number of elements. */
				echo esc_html( sprintf( _n( 'Undone. %s element is back to its original colour.', 'Undone. %s elements are back to their original colours.', $corea11y_c_undone, 'corea11y' ), number_format_i18n( $corea11y_c_undone ) ) );
				?>
			</p></div>
		<?php elseif ( $corea11y_c_none ) : ?>
			<div class="notice notice-warning inline" role="status"><p><?php esc_html_e( 'Nothing was selected. Tick "Use this colour" on the suggestions you want, then apply.', 'corea11y' ); ?></p></div>
		<?php elseif ( $corea11y_c_locked ) : ?>
			<div class="notice notice-warning inline" role="status"><p><?php esc_html_e( 'Activate your CoreA11y Pro license to apply colours.', 'corea11y' ); ?></p></div>
		<?php endif; ?>
	</div>

	<details class="corea11y-tips">
		<summary><strong><?php esc_html_e( 'How this works', 'corea11y' ); ?></strong></summary>
		<ul>
			<li><?php esc_html_e( 'Each suggestion keeps the same colour family and only makes it lighter or darker, just enough to pass.', 'corea11y' ); ?></li>
			<li><?php esc_html_e( 'An approved colour is applied only to that element, on the page where it was found. Your theme and content are not edited.', 'corea11y' ); ?></li>
			<li><?php esc_html_e( 'You can type your own colour. CoreA11y only accepts it if it passes; otherwise it uses the suggestion.', 'corea11y' ); ?></li>
			<li><?php esc_html_e( 'Text over images or gradients is not listed here. Check those by eye: they are in the report under "Needs a human check".', 'corea11y' ); ?></li>
			<li><?php esc_html_e( 'For a permanent fix across your whole site, change the colour in your theme settings too.', 'corea11y' ); ?></li>
		</ul>
	</details>

	<?php if ( ! $licensed ) : ?>
		<p class="corea11y-fix-lock">
			<?php esc_html_e( 'Activate your CoreA11y Pro license to apply these colours.', 'corea11y' ); ?>
			<?php $corea11y_lic = apply_filters( 'corea11y_license_url', '' ); ?>
			<?php if ( $corea11y_lic ) : ?>
				<a href="<?php echo esc_url( $corea11y_lic ); ?>"><?php esc_html_e( 'Activate license', 'corea11y' ); ?></a>
			<?php endif; ?>
		</p>
	<?php endif; ?>

	<?php if ( ! $data['audit'] ) : ?>
		<div class="corea11y-card">
			<h2><?php esc_html_e( 'Run an audit first', 'corea11y' ); ?></h2>
			<p><?php esc_html_e( 'Suggestions come from your latest audit.', 'corea11y' ); ?></p>
			<p><a class="button button-primary" href="<?php echo esc_url( admin_url( 'admin.php?page=corea11y' ) ); ?>"><?php esc_html_e( 'Go to the dashboard', 'corea11y' ); ?></a></p>
		</div>
	<?php elseif ( ! $corea11y_c_groups ) : ?>
		<div class="corea11y-card">
			<h2><?php esc_html_e( 'No colour problems to approve', 'corea11y' ); ?></h2>
			<p>
				<?php
				if ( $applied ) {
					esc_html_e( 'Every contrast problem in your latest audit has an approved colour. Run a new audit to confirm.', 'corea11y' );
				} else {
					esc_html_e( 'Your latest audit found no text with a known colour that is too hard to read.', 'corea11y' );
				}
				?>
			</p>
		</div>
	<?php else : ?>
		<p>
			<?php
			$corea11y_c_total = 0;
			foreach ( $corea11y_c_groups as $corea11y_g ) {
				$corea11y_c_total += count( $corea11y_g['items'] );
			}
			/* translators: 1: number of elements, 2: number of colour suggestions. */
			echo esc_html( sprintf( _n( '%1$s element needs a readable colour (%2$s suggestion).', '%1$s elements need a readable colour (%2$s suggestions).', $corea11y_c_total, 'corea11y' ), number_format_i18n( $corea11y_c_total ), number_format_i18n( count( $corea11y_c_groups ) ) ) );
			?>
		</p>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="corea11y-contrast-form">
			<input type="hidden" name="action" value="corea11y_contrast_apply">
			<?php wp_nonce_field( 'corea11y_contrast_apply' ); ?>
			<p><label><input type="checkbox" id="corea11y-c-all" <?php disabled( ! $licensed ); ?>> <?php esc_html_e( 'Select all suggestions', 'corea11y' ); ?></label></p>
			<ul class="corea11y-contrast-list">
				<?php
				foreach ( $corea11y_c_groups as $corea11y_g ) :
					$corea11y_k = $corea11y_g['key'];
					$corea11y_n = count( $corea11y_g['items'] );
					?>
					<li class="corea11y-contrast-item">
						<fieldset>
							<legend class="screen-reader-text">
								<?php
								/* translators: 1: current colour, 2: background colour. */
								echo esc_html( sprintf( __( 'Text colour %1$s on %2$s', 'corea11y' ), $corea11y_g['fg'], $corea11y_g['bg'] ) );
								?>
							</legend>
							<div class="corea11y-contrast-previews">
								<div class="corea11y-contrast-preview" style="background: <?php echo esc_attr( $corea11y_g['bg'] ); ?>; color: <?php echo esc_attr( $corea11y_g['fg'] ); ?>">
									<span class="corea11y-contrast-tag"><?php esc_html_e( 'Now', 'corea11y' ); ?></span>
									<?php
									// A picture of the problem: the current, hard-to-read colours on purpose.
									$corea11y_sample = $corea11y_g['items'][0]['text'] ? $corea11y_g['items'][0]['text'] : __( 'Sample text', 'corea11y' );
									$corea11y_short  = function_exists( 'mb_strimwidth' ) ? mb_strimwidth( $corea11y_sample, 0, 34, '…' ) : substr( $corea11y_sample, 0, 34 );
									?>
									<svg class="corea11y-contrast-sample" role="img" width="100%" height="28" aria-label="<?php echo esc_attr( sprintf( /* translators: %s: sample text. */ __( 'Example of the current colours: %s', 'corea11y' ), $corea11y_sample ) ); ?>"><text x="0" y="21" fill="<?php echo esc_attr( $corea11y_g['fg'] ); ?>" font-size="18"><?php echo esc_html( $corea11y_short ); ?></text></svg>
									<span class="corea11y-contrast-ratio">
										<?php
										/* translators: %s: contrast ratio. */
										echo esc_html( sprintf( __( 'Contrast %s:1', 'corea11y' ), number_format_i18n( $corea11y_g['before'], 2 ) ) );
										?>
									</span>
								</div>
								<span class="corea11y-arrow" aria-hidden="true">&rarr;</span>
								<div class="corea11y-contrast-preview" data-corea11y-preview="<?php echo esc_attr( $corea11y_k ); ?>" style="background: <?php echo esc_attr( $corea11y_g['bg'] ); ?>; color: <?php echo esc_attr( $corea11y_g['to'] ); ?>">
									<span class="corea11y-contrast-tag"><?php esc_html_e( 'Suggested', 'corea11y' ); ?></span>
									<span class="corea11y-contrast-sample"><?php echo esc_html( $corea11y_g['items'][0]['text'] ? $corea11y_g['items'][0]['text'] : __( 'Sample text', 'corea11y' ) ); ?></span>
									<span class="corea11y-contrast-ratio">
										<?php
										/* translators: 1: contrast ratio, 2: required ratio. */
										echo esc_html( sprintf( __( 'Contrast %1$s:1 (needs %2$s:1)', 'corea11y' ), number_format_i18n( $corea11y_g['after'], 2 ), number_format_i18n( $corea11y_g['need'], 1 ) ) );
										?>
									</span>
								</div>
							</div>
							<div class="corea11y-contrast-controls">
								<label class="corea11y-contrast-approve">
									<input type="checkbox" name="approve[<?php echo esc_attr( $corea11y_k ); ?>]" value="1" <?php disabled( ! $licensed ); ?>>
									<?php esc_html_e( 'Use this colour', 'corea11y' ); ?>
								</label>
								<label for="corea11y-c-<?php echo esc_attr( $corea11y_k ); ?>"><?php esc_html_e( 'Colour', 'corea11y' ); ?></label>
								<input type="text" id="corea11y-c-<?php echo esc_attr( $corea11y_k ); ?>" name="color[<?php echo esc_attr( $corea11y_k ); ?>]" value="<?php echo esc_attr( $corea11y_g['to'] ); ?>" class="small-text corea11y-contrast-input" data-corea11y-key="<?php echo esc_attr( $corea11y_k ); ?>" pattern="#[0-9a-fA-F]{6}" maxlength="7" spellcheck="false" aria-describedby="corea11y-c-<?php echo esc_attr( $corea11y_k ); ?>-was">
								<span id="corea11y-c-<?php echo esc_attr( $corea11y_k ); ?>-was" class="corea11y-meta">
									<?php
									/* translators: 1: current text colour, 2: background colour. */
									echo esc_html( sprintf( __( 'Now %1$s on %2$s', 'corea11y' ), $corea11y_g['fg'], $corea11y_g['bg'] ) );
									?>
								</span>
							</div>
							<details class="corea11y-contrast-where">
								<summary>
									<?php
									/* translators: %s: number of elements. */
									echo esc_html( sprintf( _n( 'Where: %s element', 'Where: %s elements', $corea11y_n, 'corea11y' ), number_format_i18n( $corea11y_n ) ) );
									?>
								</summary>
								<ul>
									<?php foreach ( $corea11y_g['items'] as $corea11y_item ) : ?>
										<li><a href="<?php echo esc_url( $corea11y_item['url'] ); ?>"><?php echo esc_html( $corea11y_item['label'] ); ?></a>: <?php echo esc_html( $corea11y_item['text'] ? '"' . $corea11y_item['text'] . '"' : $corea11y_item['selector'] ); ?></li>
									<?php endforeach; ?>
								</ul>
							</details>
						</fieldset>
					</li>
				<?php endforeach; ?>
			</ul>
			<div class="corea11y-alt-bar">
				<button type="submit" class="button button-primary" <?php disabled( ! $licensed ); ?>><?php esc_html_e( 'Apply selected colours', 'corea11y' ); ?></button>
				<span class="corea11y-meta"><?php esc_html_e( 'Only ticked suggestions are applied.', 'corea11y' ); ?></span>
			</div>
		</form>
	<?php endif; ?>

	<?php
	$corea11y_c_live = array_values(
		array_filter(
			$log,
			static function ( $e ) {
				return empty( $e['undone'] );
			}
		)
	);
	if ( $applied ) :
		?>
		<section class="corea11y-section" aria-labelledby="corea11y-c-applied">
			<h2 id="corea11y-c-applied">
				<?php
				/* translators: %s: number of elements. */
				echo esc_html( sprintf( _n( 'Approved colours (%s element)', 'Approved colours (%s elements)', count( $applied ), 'corea11y' ), number_format_i18n( count( $applied ) ) ) );
				?>
			</h2>
			<table class="widefat striped corea11y-table">
				<thead><tr><th scope="col"><?php esc_html_e( 'When', 'corea11y' ); ?></th><th scope="col"><?php esc_html_e( 'Change', 'corea11y' ); ?></th><th scope="col"><span class="screen-reader-text"><?php esc_html_e( 'Undo', 'corea11y' ); ?></span></th></tr></thead>
				<tbody>
					<?php foreach ( $corea11y_c_live as $corea11y_e ) : ?>
						<tr>
							<td><?php echo esc_html( wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), (int) $corea11y_e['time'] ) ); ?></td>
							<td>
								<?php
								$corea11y_cnt = count( $corea11y_e['fps'] );
								/* translators: 1: number of elements, 2: number of pages. */
								echo esc_html( sprintf( _n( '%1$s element on %2$s page', '%1$s elements on %2$s pages', $corea11y_cnt, 'corea11y' ), number_format_i18n( $corea11y_cnt ), number_format_i18n( (int) $corea11y_e['pages'] ) ) );
								?>
							</td>
							<td>
								<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
									<input type="hidden" name="action" value="corea11y_contrast_undo">
									<input type="hidden" name="batch" value="<?php echo esc_attr( $corea11y_e['batch'] ); ?>">
									<?php wp_nonce_field( 'corea11y_contrast_undo' ); ?>
									<button type="submit" class="button"><?php esc_html_e( 'Undo', 'corea11y' ); ?></button>
								</form>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="corea11y_contrast_undo">
				<input type="hidden" name="batch" value="all">
				<?php wp_nonce_field( 'corea11y_contrast_undo' ); ?>
				<p><button type="submit" class="button"><?php esc_html_e( 'Undo all colour changes', 'corea11y' ); ?></button></p>
			</form>
		</section>
	<?php endif; ?>
</div>
<?php
// Live preview of a typed colour (progressive enhancement).
wp_print_inline_script_tag(
	'(function(){var a=document.getElementById("corea11y-c-all");if(a){a.addEventListener("change",function(){document.querySelectorAll(".corea11y-contrast-approve input").forEach(function(c){c.checked=a.checked;});});}})();document.querySelectorAll(".corea11y-contrast-input").forEach(function(i){i.addEventListener("input",function(){if(/^#[0-9a-fA-F]{6}$/.test(i.value)){var p=document.querySelector("[data-corea11y-preview=\""+i.dataset.corea11yKey+"\"]");if(p){p.style.color=i.value;}}});});'
);
