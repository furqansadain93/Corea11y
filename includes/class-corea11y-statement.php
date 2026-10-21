<?php
/**
 * Accessibility statement: settings, a live [corea11y_statement] shortcode and an optional footer link.
 *
 * The structure follows the W3C/WAI guidance on accessibility statements. The statement is
 * "live": the latest audit date, open issues and checklist progress are filled in automatically.
 *
 * @package CoreA11y
 */

defined( 'ABSPATH' ) || exit;

/**
 * Accessibility statement generator.
 */
class CoreA11y_Statement {

	const OPTION    = 'corea11y_statement';
	const SHORTCODE = 'corea11y_statement';

	/**
	 * Defaults.
	 *
	 * @return array
	 */
	public static function defaults() {
		return array(
			'org'         => get_bloginfo( 'name' ),
			'email'       => get_option( 'admin_email' ),
			'phone'       => '',
			'status'      => 'partial',
			'limitations' => '',
			'measures'    => '',
			'auto_list'   => true,
			'response'    => 5,
			'footer_link' => false,
			'page_id'     => 0,
			'reviewed'    => '',
		);
	}

	/**
	 * Saved settings merged with defaults.
	 *
	 * @return array
	 */
	public static function get() {
		$saved = get_option( self::OPTION, array() );
		return wp_parse_args( is_array( $saved ) ? $saved : array(), self::defaults() );
	}

	/**
	 * Conformance status options (W3C wording).
	 *
	 * @return array<string,array{label:string,desc:string}>
	 */
	public static function statuses() {
		return array(
			'full'    => array(
				'label' => __( 'Fully conformant', 'corea11y' ),
				'desc'  => __( 'the content fully conforms to the accessibility standard without any exceptions', 'corea11y' ),
			),
			'partial' => array(
				'label' => __( 'Partially conformant', 'corea11y' ),
				'desc'  => __( 'some parts of the content do not fully conform to the accessibility standard', 'corea11y' ),
			),
			'non'     => array(
				'label' => __( 'Non conformant', 'corea11y' ),
				'desc'  => __( 'the content does not conform to the accessibility standard', 'corea11y' ),
			),
		);
	}

	/**
	 * Hook into WordPress.
	 */
	public static function register() {
		add_shortcode( self::SHORTCODE, array( __CLASS__, 'render' ) );
		add_action( 'wp_footer', array( __CLASS__, 'footer_link' ) );
	}

	/**
	 * Sanitize and save.
	 *
	 * @param array $input Raw input.
	 */
	public static function save( array $input ) {
		$old = self::get();
		$new = array(
			'org'         => sanitize_text_field( isset( $input['org'] ) ? $input['org'] : '' ),
			'email'       => sanitize_email( isset( $input['email'] ) ? $input['email'] : '' ),
			'phone'       => sanitize_text_field( isset( $input['phone'] ) ? $input['phone'] : '' ),
			'status'      => isset( $input['status'] ) && array_key_exists( $input['status'], self::statuses() ) ? $input['status'] : 'partial',
			'limitations' => sanitize_textarea_field( isset( $input['limitations'] ) ? $input['limitations'] : '' ),
			'measures'    => sanitize_textarea_field( isset( $input['measures'] ) ? $input['measures'] : '' ),
			'auto_list'   => ! empty( $input['auto_list'] ),
			'response'    => max( 1, min( 60, absint( isset( $input['response'] ) ? $input['response'] : 5 ) ) ),
			'footer_link' => ! empty( $input['footer_link'] ),
			'page_id'     => (int) $old['page_id'],
			'reviewed'    => gmdate( 'Y-m-d' ),
		);
		update_option( self::OPTION, $new );

		/**
		 * Fires after the accessibility statement settings are saved (the footer link may have changed).
		 */
		do_action( 'corea11y_statement_changed' );
	}

	/**
	 * The statement page, if it exists and isn't trashed.
	 *
	 * @return WP_Post|null
	 */
	public static function page() {
		$id   = (int) self::get()['page_id'];
		$post = $id ? get_post( $id ) : null;
		return ( $post && 'trash' !== $post->post_status ) ? $post : null;
	}

	/**
	 * Create the statement page as a draft.
	 *
	 * @return int|WP_Error Page id.
	 */
	public static function create_page() {
		$existing = self::page();
		if ( $existing ) {
			return $existing->ID;
		}
		$id = wp_insert_post(
			array(
				'post_type'    => 'page',
				'post_status'  => 'draft',
				'post_title'   => __( 'Accessibility statement', 'corea11y' ),
				'post_name'    => sanitize_title( _x( 'accessibility-statement', 'page slug', 'corea11y' ) ),
				'post_content' => '<!-- wp:shortcode -->[' . self::SHORTCODE . ']<!-- /wp:shortcode -->',
			),
			true
		);
		if ( ! is_wp_error( $id ) ) {
			$settings            = self::get();
			$settings['page_id'] = (int) $id;
			update_option( self::OPTION, $settings );
		}
		return $id;
	}

	/**
	 * Small link to the statement in the site footer (opt-in).
	 */
	public static function footer_link() {
		$s    = self::get();
		$page = self::page();
		if ( empty( $s['footer_link'] ) || ! $page || 'publish' !== $page->post_status ) {
			return;
		}
		printf(
			'<p class="corea11y-statement-link" style="text-align:center;margin:1em 0;"><a href="%1$s">%2$s</a></p>',
			esc_url( get_permalink( $page ) ),
			esc_html__( 'Accessibility statement', 'corea11y' )
		);
	}

	/**
	 * Render the statement (shortcode callback and admin preview).
	 *
	 * @return string
	 */
	public static function render() {
		$s       = self::get();
		$org     = $s['org'] ? $s['org'] : get_bloginfo( 'name' );
		$site    = wp_parse_url( home_url(), PHP_URL_HOST );
		$status  = self::statuses()[ $s['status'] ];
		$audits  = CoreA11y_Admin::recent_audits( 1, 'complete' );
		$audit   = $audits ? $audits[0] : null;
		$checks  = CoreA11y_Checklist::summary();
		$total   = array_sum( $checks );
		$fmt     = get_option( 'date_format' );
		$h       = 'h2';

		ob_start();
		?>
		<div class="corea11y-statement">
			<p>
				<?php
				/* translators: 1: organization name, 2: website address. */
				echo esc_html( sprintf( __( '%1$s is committed to making %2$s accessible to everyone, including people with disabilities. We continually improve the experience for all visitors and apply the relevant accessibility standards.', 'corea11y' ), $org, $site ) );
				?>
			</p>

			<<?php echo esc_html( $h ); ?>><?php esc_html_e( 'Conformance status', 'corea11y' ); ?></<?php echo esc_html( $h ); ?>>
			<p>
				<?php
				/* translators: 1: website address, 2: conformance status (e.g. "Partially conformant"), 3: explanation of that status. */
				echo esc_html( sprintf( __( 'The Web Content Accessibility Guidelines (WCAG) define requirements to improve accessibility for people with disabilities. Our assessment of %1$s against WCAG 2.2 Level AA: %2$s. This means that %3$s.', 'corea11y' ), $site, $status['label'], $status['desc'] ) );
				?>
			</p>

			<<?php echo esc_html( $h ); ?>><?php esc_html_e( 'Measures we take', 'corea11y' ); ?></<?php echo esc_html( $h ); ?>>
			<ul>
				<li><?php esc_html_e( 'We check our pages regularly with automated accessibility testing (axe-core).', 'corea11y' ); ?></li>
				<li><?php esc_html_e( 'We review the requirements that software can\'t judge with a manual checklist.', 'corea11y' ); ?></li>
				<li><?php esc_html_e( 'We fix problems at the source rather than relying on an overlay.', 'corea11y' ); ?></li>
				<?php foreach ( array_filter( array_map( 'trim', explode( "\n", (string) $s['measures'] ) ) ) as $line ) : ?>
					<li><?php echo esc_html( $line ); ?></li>
				<?php endforeach; ?>
			</ul>

			<<?php echo esc_html( $h ); ?>><?php esc_html_e( 'Known limitations', 'corea11y' ); ?></<?php echo esc_html( $h ); ?>>
			<?php if ( $s['limitations'] ) : ?>
				<p><?php echo nl2br( esc_html( $s['limitations'] ) ); ?></p>
			<?php endif; ?>
			<?php
			if ( ! empty( $s['auto_list'] ) && $audit ) :
				$groups = CoreA11y_Audit::grouped_issues( (int) $audit->id );
				?>
				<p>
					<?php
					if ( $groups ) {
						/* translators: 1: date, 2: number of issues, 3: number of pages. */
						echo esc_html( sprintf( __( 'Our latest automated check on %1$s found %2$s issues across %3$s pages. We are working on these areas:', 'corea11y' ), get_date_from_gmt( $audit->started_at, $fmt ), number_format_i18n( (int) $audit->issues_total ), number_format_i18n( (int) $audit->pages_scanned ) ) );
					} else {
						/* translators: %s: date. */
						echo esc_html( sprintf( __( 'Our latest automated check on %s found no issues that automated testing can detect.', 'corea11y' ), get_date_from_gmt( $audit->started_at, $fmt ) ) );
					}
					?>
				</p>
				<?php if ( $groups ) : ?>
					<ul>
						<?php
						$titles = array();
						foreach ( $groups as $g ) {
							$titles[ CoreA11y_Explain::public_title( $g['rule'], $g['help'] ) ] = true;
						}
						?>
						<?php foreach ( array_slice( array_keys( $titles ), 0, 8 ) as $title ) : ?>
							<li><?php echo esc_html( $title ); ?></li>
						<?php endforeach; ?>
					</ul>
				<?php endif; ?>
			<?php elseif ( ! $s['limitations'] ) : ?>
				<p><?php esc_html_e( 'We are not aware of specific limitations. If you find one, please let us know.', 'corea11y' ); ?></p>
			<?php endif; ?>

			<<?php echo esc_html( $h ); ?>><?php esc_html_e( 'Feedback', 'corea11y' ); ?></<?php echo esc_html( $h ); ?>>
			<p><?php esc_html_e( 'We welcome your feedback. If you run into an accessibility barrier, please contact us:', 'corea11y' ); ?></p>
			<ul>
				<?php if ( $s['email'] ) : ?>
					<li><?php esc_html_e( 'Email:', 'corea11y' ); ?> <a href="<?php echo esc_url( 'mailto:' . antispambot( $s['email'] ) ); ?>"><?php echo esc_html( antispambot( $s['email'] ) ); ?></a></li>
				<?php endif; ?>
				<?php if ( $s['phone'] ) : ?>
					<li><?php esc_html_e( 'Phone:', 'corea11y' ); ?> <a href="<?php echo esc_url( 'tel:' . preg_replace( '/[^0-9+]/', '', $s['phone'] ) ); ?>"><?php echo esc_html( $s['phone'] ); ?></a></li>
				<?php endif; ?>
			</ul>
			<p>
				<?php
				/* translators: %s: number of business days. */
				echo esc_html( sprintf( _n( 'We try to respond within %s business day.', 'We try to respond within %s business days.', (int) $s['response'], 'corea11y' ), number_format_i18n( (int) $s['response'] ) ) );
				?>
			</p>

			<<?php echo esc_html( $h ); ?>><?php esc_html_e( 'Assessment approach', 'corea11y' ); ?></<?php echo esc_html( $h ); ?>>
			<p>
				<?php
				/* translators: 1: number of manual checks reviewed, 2: total checks. */
				echo esc_html( sprintf( __( 'We assess the accessibility of this website by self-evaluation: automated testing of our pages, plus a manual review of requirements that need human judgment (%1$s of %2$s checks reviewed so far).', 'corea11y' ), number_format_i18n( $total - (int) $checks[''] ), number_format_i18n( $total ) ) );
				?>
			</p>

			<<?php echo esc_html( $h ); ?>><?php esc_html_e( 'Technical specifications', 'corea11y' ); ?></<?php echo esc_html( $h ); ?>>
			<p><?php esc_html_e( 'Accessibility of this website relies on HTML, CSS, JavaScript and WAI-ARIA working with your browser and any assistive technologies or plugins installed on your computer.', 'corea11y' ); ?></p>

			<p class="corea11y-statement-date">
				<?php
				$reviewed = $s['reviewed'] ? mysql2date( $fmt, $s['reviewed'] ) : wp_date( $fmt );
				/* translators: %s: date. */
				echo esc_html( sprintf( __( 'This statement was last reviewed on %s.', 'corea11y' ), $reviewed ) );
				if ( $audit ) {
					echo ' ';
					/* translators: %s: date. */
					echo esc_html( sprintf( __( 'The latest automated check ran on %s.', 'corea11y' ), get_date_from_gmt( $audit->started_at, $fmt ) ) );
				}
				?>
			</p>
		</div>
		<?php
		return (string) ob_get_clean();
	}
}
