<?php
/**
 * Server-side checks for content that a page scan may not reach.
 *
 * @package CoreA11y
 */

defined( 'ABSPATH' ) || exit;

/**
 * Checks run in PHP when an audit finishes.
 */
class CoreA11y_Server_Checks {

	/**
	 * Max posts/attachments examined per check.
	 */
	const LIMIT = 500;

	/**
	 * Run all checks and store issues.
	 *
	 * @param int   $audit_id    Audit id.
	 * @param array $scanned_ids Post ids already scanned by axe (keys), skipped to avoid double counting.
	 */
	public static function run( $audit_id, array $scanned_ids ) {
		self::content_images( $audit_id, $scanned_ids );
		self::media_alt( $audit_id );

		/**
		 * Fires after built-in server-side checks, so add-ons can add their own.
		 *
		 * @param int   $audit_id    Audit id.
		 * @param array $scanned_ids Post ids scanned by axe.
		 */
		do_action( 'corea11y_server_checks', $audit_id, $scanned_ids );
	}

	/**
	 * Images in published post content with no alt attribute at all.
	 *
	 * @param int   $audit_id    Audit id.
	 * @param array $scanned_ids Post ids to skip.
	 */
	public static function content_images( $audit_id, array $scanned_ids ) {
		$types = get_post_types( array( 'public' => true ) );
		unset( $types['attachment'] );

		$query = new WP_Query(
			array(
				'post_type'              => array_values( $types ),
				'post_status'            => 'publish',
				'posts_per_page'         => self::LIMIT,
				'has_password'           => false,
				'no_found_rows'          => true,
				'update_post_term_cache' => false,
				's'                      => '<img',
				'search_columns'         => array( 'post_content' ),
			)
		);

		foreach ( $query->posts as $post ) {
			if ( isset( $scanned_ids[ $post->ID ] ) ) {
				continue;
			}
			$tags = new WP_HTML_Tag_Processor( $post->post_content );
			while ( $tags->next_tag( 'img' ) ) {
				if ( null !== $tags->get_attribute( 'alt' ) ) {
					continue;
				}
				$src = (string) $tags->get_attribute( 'src' );
				self::insert(
					$audit_id,
					array(
						'page_url'   => get_permalink( $post ),
						'page_label' => get_the_title( $post ),
						'object_id'  => $post->ID,
						'rule_id'    => 'corea11y-content-img-alt',
						'wcag'       => '1.1.1',
						'impact'     => 'critical',
						'snippet'    => '<img src="' . esc_url_raw( $src ) . '">',
						'help'       => __( 'Images in post content must have alternative text', 'corea11y' ),
						'detail'     => __( 'Edit this post and add alt text to the image, or mark it as decorative (empty alt text) if it adds no information.', 'corea11y' ),
					)
				);
			}
		}
	}

	/**
	 * Media Library images with no alt text set.
	 *
	 * @param int $audit_id Audit id.
	 */
	public static function media_alt( $audit_id ) {
		$ids = get_posts(
			array(
				'post_type'      => 'attachment',
				'post_status'    => 'inherit',
				'post_mime_type' => 'image',
				'posts_per_page' => self::LIMIT,
				'fields'         => 'ids',
				// phpcs:ignore WordPress.DB.SlowDBQuery.slow_query_meta_query -- Runs once per audit, capped by LIMIT.
				'meta_query'     => array(
					'relation' => 'OR',
					array(
						'key'     => '_wp_attachment_image_alt',
						'compare' => 'NOT EXISTS',
					),
					array(
						'key'   => '_wp_attachment_image_alt',
						'value' => '',
					),
				),
			)
		);

		foreach ( $ids as $id ) {
			self::insert(
				$audit_id,
				array(
					'page_url'   => (string) wp_get_attachment_url( $id ),
					'page_label' => get_the_title( $id ),
					'object_id'  => $id,
					'rule_id'    => 'corea11y-media-alt',
					'wcag'       => '1.1.1',
					'impact'     => 'moderate',
					'snippet'    => '',
					'help'       => __( 'Media Library images should have alt text', 'corea11y' ),
					'detail'     => __( 'Add alt text in the Media Library so it is used wherever this image is inserted. Leave it empty only if the image is purely decorative.', 'corea11y' ),
				)
			);
		}
	}

	/**
	 * Insert one server-side issue.
	 *
	 * @param int   $audit_id Audit id.
	 * @param array $data     Issue fields.
	 */
	private static function insert( $audit_id, array $data ) {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Custom table.
		$wpdb->insert(
			CoreA11y_Install::tables()['issues'],
			array(
				'audit_id'   => $audit_id,
				'page_url'   => esc_url_raw( $data['page_url'] ),
				'page_label' => CoreA11y_Audit::clip( wp_strip_all_tags( $data['page_label'] ), 255 ),
				'object_id'  => (int) $data['object_id'],
				'rule_id'    => $data['rule_id'],
				'wcag'       => $data['wcag'],
				'impact'     => $data['impact'],
				'tier'       => 'owner',
				'source'     => 'server',
				'selector'   => '',
				'snippet'    => $data['snippet'],
				'help'       => $data['help'],
				'detail'     => $data['detail'],
			),
			array( '%d', '%s', '%s', '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s' )
		);
	}
}
