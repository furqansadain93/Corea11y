<?php
/**
 * Alt text screen.
 *
 * @package CoreA11y
 * @var array  $rows  Rows for this page.
 * @var int    $total Total rows.
 * @var int    $paged Current page.
 * @var string $show  'used' or 'all'.
 * @var array  $log   Save history.
 */

defined( 'ABSPATH' ) || exit;

// phpcs:disable WordPress.Security.NonceVerification.Recommended -- Display-only flags after redirect.
$corea11y_saved  = isset( $_GET['saved'] ) ? absint( $_GET['saved'] ) : null;
$corea11y_posts  = isset( $_GET['posts'] ) ? absint( $_GET['posts'] ) : 0;
$corea11y_batch  = isset( $_GET['batch'] ) ? sanitize_text_field( wp_unslash( $_GET['batch'] ) ) : '';
$corea11y_undone = isset( $_GET['undone'] ) ? absint( $_GET['undone'] ) : null;
// phpcs:enable
$corea11y_pages = (int) ceil( $total / CoreA11y_Alt::PER_PAGE );
$corea11y_base  = admin_url( 'admin.php?page=corea11y-alt' );
?>
<div class="wrap corea11y">
	<?php CoreA11y_UI::brand_bar(); ?>
	<div class="corea11y-page-head">
		<div>
			<h1><?php esc_html_e( 'Alt text', 'msacalab-accessibility-checker' ); ?></h1>
			<p class="corea11y-meta"><?php esc_html_e( 'Every image on your site that has no alt text, in one place. Type a short description and save: MSACAlab Accessibility Checker adds it to the Media Library and to the pages where the image is already used.', 'msacalab-accessibility-checker' ); ?></p>
		</div>
	</div>

	<?php if ( null !== $corea11y_saved ) : ?>
		<div class="notice notice-success corea11y-verify" role="status">
			<?php if ( $corea11y_saved ) : ?>
				<p><strong>
					<?php
					/* translators: 1: number of images, 2: number of pages and posts. */
					echo esc_html( sprintf( _n( 'Alt text saved for %1$d image and added to %2$d pages and posts.', 'Alt text saved for %1$d images and added to %2$d pages and posts.', $corea11y_saved, 'msacalab-accessibility-checker' ), $corea11y_saved, $corea11y_posts ) );
					?>
				</strong></p>
				<p class="corea11y-muted"><?php esc_html_e( 'This alt text is now part of your site and stays even if MSACAlab Accessibility Checker is removed. Changed pages also have a WordPress revision.', 'msacalab-accessibility-checker' ); ?></p>
				<div class="corea11y-actions">
					<a class="button button-primary" href="<?php echo esc_url( admin_url( 'admin.php?page=corea11y&corea11y_autorun=1' ) ); ?>"><?php esc_html_e( 'Verify with a new audit', 'msacalab-accessibility-checker' ); ?></a>
					<?php if ( $corea11y_batch ) : ?>
						<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
							<input type="hidden" name="action" value="corea11y_undo_alt">
							<input type="hidden" name="batch" value="<?php echo esc_attr( $corea11y_batch ); ?>">
							<?php wp_nonce_field( 'corea11y_undo_alt' ); ?>
							<button type="submit" class="button"><?php esc_html_e( 'Undo', 'msacalab-accessibility-checker' ); ?></button>
						</form>
					<?php endif; ?>
				</div>
			<?php else : ?>
				<p><?php esc_html_e( 'Nothing to save: type alt text or tick "Decorative" for at least one image.', 'msacalab-accessibility-checker' ); ?></p>
			<?php endif; ?>
		</div>
	<?php elseif ( null !== $corea11y_undone ) : ?>
		<div class="notice notice-info" role="status"><p><?php echo $corea11y_undone ? esc_html__( 'Undone. The images have their previous alt text again.', 'msacalab-accessibility-checker' ) : esc_html__( 'That change was already undone.', 'msacalab-accessibility-checker' ); ?></p></div>
	<?php endif; ?>

	<details class="corea11y-tips">
		<summary><?php esc_html_e( 'How to write good alt text', 'msacalab-accessibility-checker' ); ?></summary>
		<ul>
			<li><?php esc_html_e( 'Describe what matters about the image in this context, in a short sentence (usually under 125 characters).', 'msacalab-accessibility-checker' ); ?></li>
			<li><?php esc_html_e( 'Don\'t start with "Image of" or "Picture of": screen readers already say it\'s an image.', 'msacalab-accessibility-checker' ); ?></li>
			<li><?php esc_html_e( 'For a logo, write the company name. For a linked image, say where the link goes.', 'msacalab-accessibility-checker' ); ?></li>
			<li><?php esc_html_e( 'If the image is only decoration (a background, a divider), tick "Decorative" so screen readers skip it.', 'msacalab-accessibility-checker' ); ?></li>
			<li><?php esc_html_e( 'If the image contains text, include that text.', 'msacalab-accessibility-checker' ); ?></li>
		</ul>
	</details>

	<p class="corea11y-filter">
		<?php if ( 'all' === $show ) : ?>
			<a href="<?php echo esc_url( $corea11y_base ); ?>"><?php esc_html_e( 'Images used on your site', 'msacalab-accessibility-checker' ); ?></a> | <strong aria-current="page"><?php esc_html_e( 'All Media Library images without alt text', 'msacalab-accessibility-checker' ); ?></strong>
		<?php else : ?>
			<strong aria-current="page"><?php esc_html_e( 'Images used on your site', 'msacalab-accessibility-checker' ); ?></strong> | <a href="<?php echo esc_url( add_query_arg( 'show', 'all', $corea11y_base ) ); ?>"><?php esc_html_e( 'All Media Library images without alt text', 'msacalab-accessibility-checker' ); ?></a>
		<?php endif; ?>
	</p>

	<?php if ( ! $rows ) : ?>
		<div class="corea11y-empty">
			<p><strong><?php esc_html_e( 'Every image here has alt text.', 'msacalab-accessibility-checker' ); ?></strong></p>
			<p><?php esc_html_e( 'Images you add later will appear here if they have none.', 'msacalab-accessibility-checker' ); ?></p>
		</div>
	<?php else : ?>
		<p>
			<?php
			/* translators: %d: number of images. */
			echo esc_html( sprintf( _n( '%d image needs alt text.', '%d images need alt text.', $total, 'msacalab-accessibility-checker' ), $total ) );
			?>
		</p>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="corea11y-alt-form">
			<input type="hidden" name="action" value="corea11y_save_alt">
			<input type="hidden" name="show" value="<?php echo esc_attr( $show ); ?>">
			<?php wp_nonce_field( 'corea11y_save_alt' ); ?>
			<ul class="corea11y-alt-list">
				<?php foreach ( $rows as $corea11y_i => $corea11y_r ) : ?>
					<?php
					$corea11y_field = 'corea11y-alt-' . $corea11y_r['key'];
					$corea11y_used  = array_slice( $corea11y_r['used'], 0, 3 );
					$corea11y_more  = count( $corea11y_r['used'] ) - count( $corea11y_used );
					?>
					<li class="corea11y-alt-item">
						<div class="corea11y-alt-thumb">
							<?php if ( $corea11y_r['thumb'] ) : ?>
								<img src="<?php echo esc_url( $corea11y_r['thumb'] ); ?>" alt="" loading="lazy" width="96" height="96">
							<?php endif; ?>
						</div>
						<div class="corea11y-alt-body">
							<p class="corea11y-alt-name">
								<strong><?php echo esc_html( $corea11y_r['name'] ? $corea11y_r['name'] : __( 'Image', 'msacalab-accessibility-checker' ) ); ?></strong>
								<?php if ( $corea11y_r['thumb'] ) : ?>
									<a href="<?php echo esc_url( $corea11y_r['id'] ? wp_get_attachment_url( $corea11y_r['id'] ) : $corea11y_r['thumb'] ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'View full size', 'msacalab-accessibility-checker' ); ?><span class="screen-reader-text"> <?php echo esc_html( $corea11y_r['name'] ); ?> <?php esc_html_e( '(opens in a new tab)', 'msacalab-accessibility-checker' ); ?></span></a>
								<?php endif; ?>
							</p>
							<p class="corea11y-muted">
								<?php if ( $corea11y_r['used'] ) : ?>
									<?php esc_html_e( 'Used on:', 'msacalab-accessibility-checker' ); ?>
									<?php foreach ( $corea11y_used as $corea11y_n => $corea11y_pid ) : ?>
										<?php echo $corea11y_n ? ', ' : ' '; ?><a href="<?php echo esc_url( get_permalink( $corea11y_pid ) ); ?>" target="_blank" rel="noopener"><?php echo esc_html( get_the_title( $corea11y_pid ) ? get_the_title( $corea11y_pid ) : __( '(no title)', 'msacalab-accessibility-checker' ) ); ?><span class="screen-reader-text"> <?php esc_html_e( '(opens in a new tab)', 'msacalab-accessibility-checker' ); ?></span></a>
									<?php endforeach; ?>
									<?php
									if ( $corea11y_more > 0 ) {
										/* translators: %d: number of other pages. */
										echo esc_html( sprintf( __( ' and %d more', 'msacalab-accessibility-checker' ), $corea11y_more ) );
									}
									?>
								<?php else : ?>
									<?php esc_html_e( 'In the Media Library, not used in a published page yet.', 'msacalab-accessibility-checker' ); ?>
								<?php endif; ?>
							</p>
							<?php if ( '' !== $corea11y_r['media_alt'] && $corea11y_r['missing_in'] ) : ?>
								<p class="corea11y-muted" id="<?php echo esc_attr( $corea11y_field . '-note' ); ?>">
									<?php
									/* translators: %d: number of pages. */
									echo esc_html( sprintf( _n( 'This image has alt text in the Media Library, but not where it was placed on %d page. Save to add it there.', 'This image has alt text in the Media Library, but not where it was placed on %d pages. Save to add it there.', count( $corea11y_r['missing_in'] ), 'msacalab-accessibility-checker' ), count( $corea11y_r['missing_in'] ) ) );
									?>
								</p>
							<?php endif; ?>
							<p class="corea11y-alt-fields">
								<label for="<?php echo esc_attr( $corea11y_field ); ?>">
									<?php esc_html_e( 'Alt text', 'msacalab-accessibility-checker' ); ?><span class="screen-reader-text"> <?php echo esc_html( $corea11y_r['name'] ); ?></span>
								</label>
								<input type="text" class="large-text" id="<?php echo esc_attr( $corea11y_field ); ?>" name="alt[<?php echo esc_attr( $corea11y_r['key'] ); ?>]" value="<?php echo esc_attr( $corea11y_r['media_alt'] ); ?>" maxlength="250" autocomplete="off"<?php echo ( '' !== $corea11y_r['media_alt'] && $corea11y_r['missing_in'] ) ? ' aria-describedby="' . esc_attr( $corea11y_field . '-note' ) . '"' : ''; ?>>
								<label class="corea11y-alt-deco"><input type="checkbox" name="decorative[<?php echo esc_attr( $corea11y_r['key'] ); ?>]" value="1" data-corea11y-for="<?php echo esc_attr( $corea11y_field ); ?>"> <?php esc_html_e( 'Decorative', 'msacalab-accessibility-checker' ); ?><span class="screen-reader-text"> <?php echo esc_html( $corea11y_r['name'] ); ?></span></label>
							</p>
						</div>
					</li>
				<?php endforeach; ?>
			</ul>
			<div class="corea11y-alt-save">
				<?php submit_button( __( 'Save alt text', 'msacalab-accessibility-checker' ), 'primary', 'submit', false ); ?>
				<span class="corea11y-muted"><?php esc_html_e( 'Images you leave empty are not changed.', 'msacalab-accessibility-checker' ); ?></span>
			</div>
		</form>

		<?php if ( $corea11y_pages > 1 ) : ?>
			<nav class="corea11y-pages" aria-label="<?php esc_attr_e( 'Pages of images', 'msacalab-accessibility-checker' ); ?>">
				<?php for ( $corea11y_p = 1; $corea11y_p <= $corea11y_pages; $corea11y_p++ ) : ?>
					<?php if ( $corea11y_p === $paged ) : ?>
						<span class="button button-primary" aria-current="page"><?php echo esc_html( number_format_i18n( $corea11y_p ) ); ?></span>
					<?php else : ?>
						<a class="button" href="<?php echo esc_url( add_query_arg( array( 'paged' => $corea11y_p, 'show' => $show ), $corea11y_base ) ); ?>">
							<?php
							/* translators: %s: page number. */
							echo esc_html( sprintf( __( 'Page %s', 'msacalab-accessibility-checker' ), number_format_i18n( $corea11y_p ) ) );
							?>
						</a>
					<?php endif; ?>
				<?php endfor; ?>
			</nav>
		<?php endif; ?>
	<?php endif; ?>

	<?php
	$corea11y_recent = array_slice( $log, 0, 5 );
	if ( $corea11y_recent ) :
		?>
		<section class="corea11y-section">
			<h2><?php esc_html_e( 'Recent changes', 'msacalab-accessibility-checker' ); ?></h2>
			<table class="widefat striped corea11y-table">
				<caption class="screen-reader-text"><?php esc_html_e( 'Recent alt text changes, newest first', 'msacalab-accessibility-checker' ); ?></caption>
				<thead><tr><th scope="col"><?php esc_html_e( 'When', 'msacalab-accessibility-checker' ); ?></th><th scope="col"><?php esc_html_e( 'Images', 'msacalab-accessibility-checker' ); ?></th><th scope="col"><?php esc_html_e( 'Undo', 'msacalab-accessibility-checker' ); ?></th></tr></thead>
				<tbody>
					<?php foreach ( $corea11y_recent as $corea11y_item ) : ?>
						<tr>
							<td><?php echo esc_html( wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), (int) $corea11y_item['time'] ) ); ?></td>
							<td><?php echo esc_html( number_format_i18n( count( $corea11y_item['entries'] ) ) ); ?></td>
							<td>
								<?php if ( ! empty( $corea11y_item['undone'] ) ) : ?>
									<?php esc_html_e( 'Undone', 'msacalab-accessibility-checker' ); ?>
								<?php else : ?>
									<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
										<input type="hidden" name="action" value="corea11y_undo_alt">
										<input type="hidden" name="batch" value="<?php echo esc_attr( $corea11y_item['batch'] ); ?>">
										<?php wp_nonce_field( 'corea11y_undo_alt' ); ?>
										<button type="submit" class="button button-small"><?php esc_html_e( 'Undo', 'msacalab-accessibility-checker' ); ?></button>
									</form>
								<?php endif; ?>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</section>
	<?php endif; ?>
</div>
<?php
wp_print_inline_script_tag(
	"document.querySelectorAll('[data-corea11y-for]').forEach(function(box){var field=document.getElementById(box.getAttribute('data-corea11y-for'));if(!field){return;}box.addEventListener('change',function(){field.disabled=box.checked;});});"
);
