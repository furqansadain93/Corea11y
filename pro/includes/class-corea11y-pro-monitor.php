<?php
/**
 * CoreA11y Pro weekly check.
 *
 * Once a week (WP-Cron), looks at what changed since the last audit using only the
 * site's own database: pages added or edited, images in them without alt text and new
 * Media Library images without alt text. It emails a short summary and, when an audit
 * is due, shows a reminder in WP Admin. The full audit runs in the browser, so it starts
 * when the site owner clicks "Run this week's audit".
 *
 * @package CoreA11y
 */

defined( 'ABSPATH' ) || exit;

/**
 * Weekly monitoring.
 */
class CoreA11y_Pro_Monitor {

	const HOOK       = 'corea11y_pro_weekly';
	const DUE_OPTION = 'corea11y_pro_due';
	const LAST       = 'corea11y_pro_last_check';

	/**
	 * Hook into WordPress.
	 */
	public static function register() {
		add_action( self::HOOK, array( __CLASS__, 'run' ) );
		add_action( 'admin_init', array( __CLASS__, 'maybe_schedule' ) );
		add_action( 'admin_notices', array( __CLASS__, 'notice' ) );
		add_action( 'admin_post_corea11y_pro_due_dismiss', array( __CLASS__, 'dismiss' ) );
		register_deactivation_hook( COREA11Y_FILE, array( __CLASS__, 'unschedule' ) );
	}

	/**
	 * Whether the weekly check should run on this site.
	 *
	 * @return bool
	 */
	public static function enabled() {
		return CoreA11y_Pro_License::licensed() && (bool) CoreA11y_Pro_Settings::get( 'weekly' );
	}

	/**
	 * Keep the schedule in line with the settings (cheap: reads one cron option).
	 */
	public static function maybe_schedule() {
		$scheduled = (bool) wp_next_scheduled( self::HOOK );
		if ( self::enabled() !== $scheduled ) {
			self::schedule();
		}
	}

	/**
	 * Schedule or unschedule the weekly event.
	 */
	public static function schedule() {
		if ( ! self::enabled() ) {
			self::unschedule();
			return;
		}
		if ( ! wp_next_scheduled( self::HOOK ) ) {
			// First check a day from now, then every week.
			wp_schedule_event( time() + DAY_IN_SECONDS, 'weekly', self::HOOK );
		}
	}

	/**
	 * Remove the weekly event.
	 */
	public static function unschedule() {
		wp_clear_scheduled_hook( self::HOOK );
	}

	/**
	 * Collect what changed since the last complete audit.
	 *
	 * @return array
	 */
	public static function collect() {
		global $wpdb;
		$audits = CoreA11y_Install::tables()['audits'];
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom table.
		$last = $wpdb->get_row( $wpdb->prepare( "SELECT id, score, issues_total, started_at, finished_at FROM %i WHERE status = 'complete' ORDER BY id DESC LIMIT 1", $audits ) );

		$since = $last && $last->finished_at ? $last->finished_at : gmdate( 'Y-m-d H:i:s', time() - WEEK_IN_SECONDS );
		$types = array_values( array_diff( get_post_types( array( 'public' => true ) ), array( 'attachment' ) ) );

		$changed = get_posts(
			array(
				'post_type'        => $types,
				'post_status'      => 'publish',
				'posts_per_page'   => 200,
				'orderby'          => 'modified',
				'order'            => 'DESC',
				'date_query'       => array(
					array(
						'column' => 'post_modified_gmt',
						'after'  => $since,
					),
				),
				'suppress_filters' => true,
			)
		);

		$no_alt = 0;
		$pages  = array();
		foreach ( $changed as $post ) {
			$missing = self::images_without_alt( (string) $post->post_content );
			$no_alt += $missing;
			$pages[] = array(
				'title'   => get_the_title( $post ),
				'url'     => get_permalink( $post ),
				'missing' => $missing,
			);
		}

		$new_media = get_posts(
			array(
				'post_type'        => 'attachment',
				'post_mime_type'   => 'image',
				'post_status'      => 'inherit',
				'posts_per_page'   => 200,
				'fields'           => 'ids',
				'date_query'       => array(
					array(
						'column' => 'post_date_gmt',
						'after'  => $since,
					),
				),
				'suppress_filters' => true,
			)
		);
		$media_no_alt = 0;
		foreach ( $new_media as $id ) {
			if ( '' === trim( (string) get_post_meta( $id, '_wp_attachment_image_alt', true ) ) ) {
				++$media_no_alt;
			}
		}

		$days = $last && $last->started_at ? (int) floor( ( time() - strtotime( $last->started_at . ' UTC' ) ) / DAY_IN_SECONDS ) : null;

		return array(
			'last'         => $last,
			'days'         => $days,
			'pages'        => $pages,
			'no_alt'       => $no_alt,
			'media_no_alt' => $media_no_alt,
			'fixes_on'     => count( array_filter( CoreA11y_Fixes::state() ) ),
			'due'          => ! $last || $pages || $media_no_alt || ( null !== $days && $days >= 7 ),
		);
	}

	/**
	 * Count images with no alt attribute at all (alt="" is a valid choice for decorative images).
	 *
	 * @param string $html HTML.
	 * @return int
	 */
	public static function images_without_alt( $html ) {
		if ( false === stripos( $html, '<img' ) ) {
			return 0;
		}
		$n    = 0;
		$tags = new WP_HTML_Tag_Processor( $html );
		while ( $tags->next_tag( 'img' ) ) {
			if ( null === $tags->get_attribute( 'alt' ) ) {
				++$n;
			}
		}
		return $n;
	}

	/**
	 * Weekly event: collect, remember, email.
	 */
	public static function run() {
		if ( ! self::enabled() ) {
			return;
		}
		$data = self::collect();
		update_option( self::LAST, array( 'time' => time(), 'pages' => count( $data['pages'] ), 'no_alt' => $data['no_alt'], 'media_no_alt' => $data['media_no_alt'] ), false );
		if ( $data['due'] ) {
			update_option( self::DUE_OPTION, array( 'time' => time(), 'pages' => count( $data['pages'] ) ), false );
		}
		self::email( $data );
	}

	/**
	 * Link that opens CoreA11y and starts an audit.
	 *
	 * @return string
	 */
	public static function audit_url() {
		return admin_url( 'admin.php?page=corea11y&corea11y_autorun=1' );
	}

	/**
	 * Send the weekly summary.
	 *
	 * @param array $d Data from collect().
	 * @return bool
	 */
	public static function email( array $d ) {
		$site = wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES );
		$fmt  = get_option( 'date_format' );
		$l    = array();

		/* translators: %s: site name. */
		$subject = sprintf( __( '[%s] Weekly accessibility check', 'corea11y' ), $site );

		$l[] = __( 'Hi,', 'corea11y' );
		$l[] = '';
		/* translators: 1: site name, 2: site address. */
		$l[] = sprintf( __( 'Here is this week\'s CoreA11y check for %1$s (%2$s).', 'corea11y' ), $site, home_url( '/' ) );
		$l[] = '';

		if ( ! $d['last'] ) {
			$l[] = __( 'You haven\'t run an audit yet. It takes about a minute and shows what to fix first:', 'corea11y' );
			$l[] = self::audit_url();
		} else {
			$when = get_date_from_gmt( $d['last']->started_at, $fmt );
			if ( 0 === (int) $d['days'] ) {
				$l[] = sprintf(
					/* translators: 1: score out of 100, 2: number of issues. */
					__( 'Last audit: today. CoreA11y score %1$s/100, %2$s issues.', 'corea11y' ),
					null === $d['last']->score ? '–' : (int) $d['last']->score,
					number_format_i18n( (int) $d['last']->issues_total )
				);
			} else {
				$l[] = sprintf(
					/* translators: 1: date, 2: number of days ago, 3: score out of 100, 4: number of issues. */
					_n( 'Last audit: %1$s (%2$d day ago). CoreA11y score %3$s/100, %4$s issues.', 'Last audit: %1$s (%2$d days ago). CoreA11y score %3$s/100, %4$s issues.', (int) $d['days'], 'corea11y' ),
					$when,
					(int) $d['days'],
					null === $d['last']->score ? '–' : (int) $d['last']->score,
					number_format_i18n( (int) $d['last']->issues_total )
				);
			}
			$l[] = '';
			$l[] = __( 'Since then:', 'corea11y' );
			/* translators: %d: number of pages or posts. */
			$l[] = '- ' . sprintf( _n( '%d page or post was added or edited', '%d pages or posts were added or edited', count( $d['pages'] ), 'corea11y' ), count( $d['pages'] ) );
			foreach ( array_slice( $d['pages'], 0, 5 ) as $p ) {
				$l[] = '    ' . $p['title'] . ' – ' . $p['url'];
			}
			if ( count( $d['pages'] ) > 5 ) {
				/* translators: %d: number of further pages. */
				$l[] = '    ' . sprintf( __( '…and %d more', 'corea11y' ), count( $d['pages'] ) - 5 );
			}
			/* translators: %d: number of images. */
			$l[] = '- ' . sprintf( _n( '%d image in those pages has no alt text', '%d images in those pages have no alt text', $d['no_alt'], 'corea11y' ), $d['no_alt'] );
			/* translators: %d: number of images. */
			$l[] = '- ' . sprintf( _n( '%d new image in the Media Library has no alt text', '%d new images in the Media Library have no alt text', $d['media_no_alt'], 'corea11y' ), $d['media_no_alt'] );
			/* translators: %d: number of quick fixes. */
			$l[] = '- ' . sprintf( _n( '%d quick fix is on', '%d quick fixes are on', $d['fixes_on'], 'corea11y' ), $d['fixes_on'] );
			$l[] = '';
			if ( $d['due'] ) {
				$l[] = __( 'Run this week\'s audit (about a minute) to see whether these changes added any problems:', 'corea11y' );
				$l[] = self::audit_url();
			} else {
				$l[] = __( 'Nothing changed since your last audit. No action needed this week.', 'corea11y' );
			}
		}

		$l[] = '';
		$l[] = '--';
		$l[] = __( 'You get this email because the weekly check is on in CoreA11y > Settings. You can change who gets it or turn it off there:', 'corea11y' );
		$l[] = admin_url( 'admin.php?page=corea11y-settings' );
		$l[] = __( 'Automated checks find many common problems but not all of them, and do not certify WCAG conformance.', 'corea11y' );

		return wp_mail( CoreA11y_Pro_Settings::recipients(), $subject, implode( "\n", $l ) );
	}

	/**
	 * The reminder, while an audit is due and none has run since the weekly check.
	 *
	 * @return array|null
	 */
	public static function due() {
		$due = get_option( self::DUE_OPTION );
		if ( ! is_array( $due ) || empty( $due['time'] ) ) {
			return null;
		}
		$last = CoreA11y_Audit::latest();
		if ( $last && 'complete' === $last->status && $last->started_at && strtotime( $last->started_at . ' UTC' ) > (int) $due['time'] ) {
			delete_option( self::DUE_OPTION );
			return null;
		}
		return $due;
	}

	/**
	 * Show the reminder on the WordPress dashboard and CoreA11y screens.
	 */
	public static function notice() {
		if ( ! current_user_can( CoreA11y_Admin::CAP ) || ! self::enabled() ) {
			return;
		}
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( ! $screen || ( 'dashboard' !== $screen->id && false === strpos( (string) $screen->id, 'corea11y' ) ) ) {
			return;
		}
		$due = self::due();
		if ( ! $due ) {
			return;
		}
		$pages = isset( $due['pages'] ) ? (int) $due['pages'] : 0;
		echo '<div class="notice notice-info"><p><strong>' . esc_html__( 'CoreA11y weekly check:', 'corea11y' ) . '</strong> ';
		if ( $pages ) {
			/* translators: %d: number of pages or posts. */
			echo esc_html( sprintf( _n( '%d page or post changed since your last audit.', '%d pages or posts changed since your last audit.', $pages, 'corea11y' ), $pages ) );
		} else {
			esc_html_e( 'it\'s time for this week\'s audit.', 'corea11y' );
		}
		echo '</p><p><a class="button button-primary" href="' . esc_url( self::audit_url() ) . '">' . esc_html__( 'Run this week\'s audit', 'corea11y' ) . '</a> ';
		echo '<a class="button-link" href="' . esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=corea11y_pro_due_dismiss' ), 'corea11y_pro_due_dismiss' ) ) . '">' . esc_html__( 'Not now', 'corea11y' ) . '</a></p></div>';
	}

	/**
	 * Hide the reminder until next week's check (admin-post handler).
	 */
	public static function dismiss() {
		if ( ! current_user_can( CoreA11y_Admin::CAP ) ) {
			wp_die( esc_html__( 'You do not have permission to do this.', 'corea11y' ), 403 );
		}
		check_admin_referer( 'corea11y_pro_due_dismiss' );
		delete_option( self::DUE_OPTION );
		wp_safe_redirect( wp_get_referer() ? wp_get_referer() : admin_url( 'admin.php?page=corea11y' ) );
		exit;
	}
}
