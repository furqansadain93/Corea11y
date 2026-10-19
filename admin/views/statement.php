<?php
/**
 * Accessibility statement screen.
 *
 * @package CoreA11y
 * @var array        $s      Statement settings.
 * @var WP_Post|null $page   Statement page.
 * @var object|null  $latest Latest completed audit.
 */

defined( 'ABSPATH' ) || exit;

// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Display-only flag after redirect.
$corea11y_flag = isset( $_GET['statement'] ) ? sanitize_key( wp_unslash( $_GET['statement'] ) ) : '';
?>
<div class="wrap corea11y">
	<div class="corea11y-page-head">
		<div>
			<h1>
				<?php esc_html_e( 'Accessibility statement', 'corea11y' ); ?>
				<?php echo CoreA11y_UI::tip( __( 'Accessibility statement', 'corea11y' ), __( 'A public page explaining your commitment to accessibility, how accessible your site is, known problems and how visitors can contact you. Many laws, including the European Accessibility Act, expect one, and it shows good faith.', 'corea11y' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped in CoreA11y_UI. ?>
			</h1>
			<p class="corea11y-meta"><?php esc_html_e( 'Fill in a few details and CoreA11y writes the statement for you. It stays up to date with your latest audit automatically.', 'corea11y' ); ?></p>
		</div>
		<?php if ( $page ) : ?>
			<div class="corea11y-actions">
				<a class="button" href="<?php echo esc_url( get_edit_post_link( $page->ID ) ); ?>"><?php esc_html_e( 'Edit page', 'corea11y' ); ?></a>
				<a class="button" href="<?php echo esc_url( get_permalink( $page ) ); ?>"><?php echo 'publish' === $page->post_status ? esc_html__( 'View page', 'corea11y' ) : esc_html__( 'Preview page', 'corea11y' ); ?></a>
			</div>
		<?php endif; ?>
	</div>

	<?php if ( 'saved' === $corea11y_flag ) : ?>
		<div class="notice notice-success inline" role="status"><p><?php esc_html_e( 'Statement saved. Your statement page shows the changes right away.', 'corea11y' ); ?></p></div>
	<?php elseif ( 'created' === $corea11y_flag && $page ) : ?>
		<div class="notice notice-success inline" role="status">
			<p><strong><?php esc_html_e( 'Statement page created as a draft.', 'corea11y' ); ?></strong> <?php esc_html_e( 'Review it, then publish it from the page editor.', 'corea11y' ); ?></p>
			<p><a class="button button-primary" href="<?php echo esc_url( get_edit_post_link( $page->ID ) ); ?>"><?php esc_html_e( 'Review and publish', 'corea11y' ); ?></a></p>
		</div>
	<?php endif; ?>

	<?php if ( 'full' === $s['status'] && $latest && (int) $latest->issues_total > 0 ) : ?>
		<div class="notice notice-warning inline"><p><?php esc_html_e( 'You selected "Fully conformant", but your latest audit still found issues. Only claim full conformance after a thorough review, ideally by an accessibility expert.', 'corea11y' ); ?></p></div>
	<?php endif; ?>

	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="corea11y-card corea11y-form">
		<input type="hidden" name="action" value="corea11y_save_statement">
		<?php wp_nonce_field( 'corea11y_save_statement' ); ?>

		<p>
			<label for="corea11y-st-org"><?php esc_html_e( 'Organization name', 'corea11y' ); ?></label>
			<input type="text" id="corea11y-st-org" name="statement[org]" class="regular-text" value="<?php echo esc_attr( $s['org'] ); ?>" autocomplete="organization">
		</p>
		<p>
			<label for="corea11y-st-email"><?php esc_html_e( 'Contact email for accessibility feedback', 'corea11y' ); ?></label>
			<input type="email" id="corea11y-st-email" name="statement[email]" class="regular-text" value="<?php echo esc_attr( $s['email'] ); ?>" autocomplete="email">
		</p>
		<p>
			<label for="corea11y-st-phone"><?php esc_html_e( 'Contact phone (optional)', 'corea11y' ); ?></label>
			<input type="tel" id="corea11y-st-phone" name="statement[phone]" class="regular-text" value="<?php echo esc_attr( $s['phone'] ); ?>" autocomplete="tel">
		</p>
		<p>
			<label for="corea11y-st-response"><?php esc_html_e( 'Response time (business days)', 'corea11y' ); ?></label>
			<input type="number" id="corea11y-st-response" name="statement[response]" min="1" max="60" class="small-text" value="<?php echo esc_attr( $s['response'] ); ?>">
		</p>

		<fieldset>
			<legend>
				<?php esc_html_e( 'How well does your site conform to WCAG 2.2 AA?', 'corea11y' ); ?>
				<?php echo CoreA11y_UI::tip( __( 'Conformance status', 'corea11y' ), __( 'Choose honestly. Automated audits can\'t prove full conformance. Most sites should choose "Partially conformant" until a full manual review is done.', 'corea11y' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped in CoreA11y_UI. ?>
			</legend>
			<?php foreach ( CoreA11y_Statement::statuses() as $corea11y_k => $corea11y_st ) : ?>
				<label><input type="radio" name="statement[status]" value="<?php echo esc_attr( $corea11y_k ); ?>" <?php checked( $s['status'], $corea11y_k ); ?>> <strong><?php echo esc_html( $corea11y_st['label'] ); ?></strong>: <?php echo esc_html( $corea11y_st['desc'] ); ?></label><br>
			<?php endforeach; ?>
		</fieldset>

		<p>
			<label><input type="checkbox" name="statement[auto_list]" value="1" <?php checked( ! empty( $s['auto_list'] ) ); ?>> <?php esc_html_e( 'List the problem areas from my latest audit automatically (recommended)', 'corea11y' ); ?></label>
		</p>
		<p>
			<label for="corea11y-st-lim"><?php esc_html_e( 'Other known limitations (optional)', 'corea11y' ); ?></label>
			<textarea id="corea11y-st-lim" name="statement[limitations]" rows="3" class="large-text" aria-describedby="corea11y-st-lim-help"><?php echo esc_textarea( $s['limitations'] ); ?></textarea>
			<span class="description" id="corea11y-st-lim-help"><?php esc_html_e( 'For example: "Older PDF documents may not be fully accessible. Contact us for an accessible version."', 'corea11y' ); ?></span>
		</p>
		<p>
			<label for="corea11y-st-measures"><?php esc_html_e( 'Extra measures you take (optional, one per line)', 'corea11y' ); ?></label>
			<textarea id="corea11y-st-measures" name="statement[measures]" rows="3" class="large-text" aria-describedby="corea11y-st-measures-help"><?php echo esc_textarea( $s['measures'] ); ?></textarea>
			<span class="description" id="corea11y-st-measures-help"><?php esc_html_e( 'For example: "Our content team is trained in writing accessible content."', 'corea11y' ); ?></span>
		</p>
		<p>
			<label><input type="checkbox" name="statement[footer_link]" value="1" <?php checked( ! empty( $s['footer_link'] ) ); ?>> <?php esc_html_e( 'Add a link to the statement at the bottom of every page (once the page is published)', 'corea11y' ); ?></label>
		</p>

		<p class="corea11y-actions">
			<button type="submit" class="button button-primary" name="do" value="save"><?php esc_html_e( 'Save statement', 'corea11y' ); ?></button>
			<?php if ( ! $page ) : ?>
				<button type="submit" class="button" name="do" value="create"><?php esc_html_e( 'Save and create statement page', 'corea11y' ); ?></button>
			<?php endif; ?>
		</p>
	</form>

	<section class="corea11y-section">
		<h2><?php esc_html_e( 'Preview', 'corea11y' ); ?></h2>
		<div class="corea11y-card corea11y-statement-preview">
			<?php echo CoreA11y_Statement::render(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped in render(). ?>
		</div>
		<p class="corea11y-note"><?php esc_html_e( 'This statement follows the structure recommended by the W3C Web Accessibility Initiative. It is a template, not legal advice. Review it before publishing.', 'corea11y' ); ?></p>
	</section>
</div>
