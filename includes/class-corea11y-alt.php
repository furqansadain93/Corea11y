<?php
/**
 * Alt text manager: every image on the site that is missing alt text, on one screen.
 *
 * Saving writes real alt text (a permanent fix that stays if CoreA11y is removed):
 * into the Media Library, and into the copies of the image already placed in posts
 * and pages, which WordPress stores separately. Every save can be undone, and post
 * edits also create a normal WordPress revision.
 *
 * @package CoreA11y
 */

defined( 'ABSPATH' ) || exit;

/**
 * Alt text manager.
 */
class CoreA11y_Alt {

	const LOG        = 'corea11y_alt_log';
	const LOG_MAX    = 30;
	const DECORATIVE = '_corea11y_decorative';
	const PER_PAGE   = 40;
	const MAX_POSTS  = 3000;

	/**
	 * Post types whose content is scanned for images.
	 *
	 * @return string[]
	 */
	private static function post_types() {
		return array_values( array_diff( get_post_types( array( 'public' => true ) ), array( 'attachment' ) ) );
	}

	/**
	 * Every image occurrence in published content: [ key => [ 'id', 'src', 'posts' => [ post_id => missing count ], 'total' ] ].
	 * Keys are "a{attachment id}" for Media Library images and "s{md5 of src}" for others.
	 *
	 * @return array
	 */
	public static function occurrences() {
		global $wpdb;
		$types = self::post_types();
		if ( ! $types ) {
			return array();
		}
		$in = implode( ',', array_fill( 0, count( $types ), '%s' ) );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber -- Read-only scan; placeholders built above.
		$posts = $wpdb->get_results( $wpdb->prepare( "SELECT ID, post_content FROM {$wpdb->posts} WHERE post_status = 'publish' AND post_type IN ($in) AND post_content LIKE %s ORDER BY ID DESC LIMIT %d", ...array_merge( $types, array( '%<img%', self::MAX_POSTS ) ) ) );

		$out = array();
		foreach ( $posts as $post ) {
			$tags = new WP_HTML_Tag_Processor( $post->post_content );
			while ( $tags->next_tag( 'img' ) ) {
				$src = (string) $tags->get_attribute( 'src' );
				$id  = self::attachment_from_tag( $tags );
				$key = $id ? 'a' . $id : 's' . md5( $src );
				if ( ! $id && '' === $src ) {
					continue;
				}
				if ( ! isset( $out[ $key ] ) ) {
					$out[ $key ] = array(
						'id'    => $id,
						'src'   => $src,
						'posts' => array(),
						'total' => 0,
					);
				}
				$alt = $tags->get_attribute( 'alt' );
				++$out[ $key ]['total'];
				if ( ! isset( $out[ $key ]['posts'][ $post->ID ] ) ) {
					$out[ $key ]['posts'][ $post->ID ] = 0;
				}
				if ( ! is_string( $alt ) || '' === trim( $alt ) ) {
					++$out[ $key ]['posts'][ $post->ID ];
				}
			}
		}
		return $out;
	}

	/**
	 * Media Library id of an <img>, from WordPress's "wp-image-123" class.
	 *
	 * @param WP_HTML_Tag_Processor $tags Positioned on an img.
	 * @return int
	 */
	private static function attachment_from_tag( $tags ) {
		$class = (string) $tags->get_attribute( 'class' );
		return preg_match( '/\bwp-image-(\d+)\b/', $class, $m ) ? (int) $m[1] : 0;
	}

	/**
	 * Images that need alt text (or still need it in some pages).
	 *
	 * @param string $show 'used' (images on the site) or 'all' (also unused Media Library images).
	 * @return array List of rows.
	 */
	public static function rows( $show = 'used' ) {
		$occ  = self::occurrences();
		$rows = array();

		// Featured images use the Media Library alt text.
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Read-only lookup.
		$thumbs = $wpdb->get_results( "SELECT pm.meta_value AS id, pm.post_id FROM {$wpdb->postmeta} pm JOIN {$wpdb->posts} p ON p.ID = pm.post_id WHERE pm.meta_key = '_thumbnail_id' AND p.post_status = 'publish' LIMIT 3000" );
		$featured = array();
		foreach ( $thumbs as $t ) {
			$featured[ (int) $t->id ][] = (int) $t->post_id;
		}

		$ids = array_unique( array_merge( array_keys( $featured ), array_filter( wp_list_pluck( $occ, 'id' ) ) ) );
		if ( 'all' === $show ) {
			$ids = array_unique(
				array_merge(
					$ids,
					get_posts(
						array(
							'post_type'      => 'attachment',
							'post_mime_type' => 'image',
							'post_status'    => 'inherit',
							'fields'         => 'ids',
							'posts_per_page' => 2000,
							'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Admin screen, run on demand.
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
					)
				)
			);
		}

		foreach ( $ids as $id ) {
			$id = (int) $id;
			if ( ! $id || 'attachment' !== get_post_type( $id ) || ! wp_attachment_is_image( $id ) ) {
				continue;
			}
			$media_alt  = trim( (string) get_post_meta( $id, '_wp_attachment_image_alt', true ) );
			$decorative = (bool) get_post_meta( $id, self::DECORATIVE, true );
			$o          = isset( $occ[ 'a' . $id ] ) ? $occ[ 'a' . $id ] : array( 'posts' => array() );
			$missing_in = array_keys( array_filter( $o['posts'] ) );
			if ( $decorative || ( '' !== $media_alt && ! $missing_in ) ) {
				continue;
			}
			$used = array_unique( array_merge( array_keys( $o['posts'] ), isset( $featured[ $id ] ) ? $featured[ $id ] : array() ) );
			$rows[] = array(
				'key'        => 'a' . $id,
				'id'         => $id,
				'thumb'      => wp_get_attachment_image_url( $id, 'thumbnail' ),
				'name'       => wp_basename( (string) get_attached_file( $id ) ),
				'media_alt'  => $media_alt,
				'used'       => $used,
				'missing_in' => $missing_in,
				'edit'       => get_edit_post_link( $id, 'raw' ),
			);
		}

		// Images placed in content that aren't in the Media Library (or lost their class).
		foreach ( $occ as $key => $o ) {
			if ( $o['id'] ) {
				continue;
			}
			$missing_in = array_keys( array_filter( $o['posts'] ) );
			if ( ! $missing_in ) {
				continue;
			}
			$rows[] = array(
				'key'        => $key,
				'id'         => 0,
				'src'        => $o['src'],
				'thumb'      => $o['src'],
				'name'       => wp_basename( (string) wp_parse_url( $o['src'], PHP_URL_PATH ) ),
				'media_alt'  => '',
				'used'       => array_keys( $o['posts'] ),
				'missing_in' => $missing_in,
				'edit'       => '',
			);
		}

		// Images used on the most pages first.
		usort(
			$rows,
			static function ( $a, $b ) {
				return count( $b['used'] ) <=> count( $a['used'] );
			}
		);
		return $rows;
	}

	/**
	 * Save alt text from the screen. Returns a batch id for undo ('' if nothing changed).
	 *
	 * @param array $alts       Key => alt text.
	 * @param array $decorative Key => 1 for decorative images.
	 * @return array{batch:string,images:int,posts:int}
	 */
	public static function save( array $alts, array $decorative ) {
		$occ     = self::occurrences();
		$entries = array();
		$posts   = array();

		foreach ( array_unique( array_merge( array_keys( $alts ), array_keys( $decorative ) ) ) as $key ) {
			$key = (string) $key;
			if ( ! preg_match( '/^(a\d+|s[a-f0-9]{32})$/', $key ) ) {
				continue;
			}
			$is_deco = ! empty( $decorative[ $key ] );
			$alt     = $is_deco ? '' : ( isset( $alts[ $key ] ) ? trim( sanitize_text_field( (string) $alts[ $key ] ) ) : '' );
			if ( ! $is_deco && '' === $alt ) {
				continue;
			}
			$entry = array(
				'key'   => $key,
				'alt'   => $alt,
				'deco'  => $is_deco,
				'media' => null,
				'posts' => array(),
			);

			if ( 'a' === $key[0] ) {
				$id = (int) substr( $key, 1 );
				if ( 'attachment' !== get_post_type( $id ) || ! current_user_can( 'edit_post', $id ) ) {
					continue;
				}
				$old            = get_post_meta( $id, '_wp_attachment_image_alt', true );
				$entry['media'] = array(
					'id'   => $id,
					'old'  => is_string( $old ) ? $old : '',
					'deco' => (bool) get_post_meta( $id, self::DECORATIVE, true ),
				);
				update_post_meta( $id, '_wp_attachment_image_alt', $alt );
				if ( $is_deco ) {
					update_post_meta( $id, self::DECORATIVE, 1 );
				} else {
					delete_post_meta( $id, self::DECORATIVE );
				}
			}

			if ( isset( $occ[ $key ] ) ) {
				foreach ( array_keys( array_filter( $occ[ $key ]['posts'] ) ) as $post_id ) {
					if ( current_user_can( 'edit_post', $post_id ) ) {
						$posts[ $post_id ][ $key ] = $alt;
					}
				}
			}
			$entries[ $key ] = $entry;
		}

		// Update each post once, with every image changed in it.
		$changed_posts = 0;
		foreach ( $posts as $post_id => $by_key ) {
			$old_alts = self::write_post( $post_id, $by_key, null );
			if ( $old_alts ) {
				++$changed_posts;
				foreach ( $old_alts as $key => $olds ) {
					$entries[ $key ]['posts'][ $post_id ] = $olds;
				}
			}
		}

		if ( ! $entries ) {
			return array(
				'batch'  => '',
				'images' => 0,
				'posts'  => 0,
			);
		}

		$batch = wp_generate_uuid4();
		$log   = self::log();
		array_unshift(
			$log,
			array(
				'batch'   => $batch,
				'time'    => time(),
				'user'    => get_current_user_id(),
				'entries' => array_values( $entries ),
				'undone'  => false,
			)
		);
		update_option( self::LOG, array_slice( $log, 0, self::LOG_MAX ), false );

		/** Fires after alt text was saved from CoreA11y (used to clear page caches). */
		do_action( 'corea11y_fixes_changed', array(), $batch );

		return array(
			'batch'  => $batch,
			'images' => count( $entries ),
			'posts'  => $changed_posts,
		);
	}

	/**
	 * Set alt text on images in one post.
	 *
	 * When $restore is null: images of each key whose alt is missing or empty get the new alt; returns the
	 * old values (null = attribute was missing) in order, per key.
	 * When $restore is given (key => [ new alt, list of old values ]): images of each key that still have
	 * the new alt get their old value back, in order.
	 *
	 * @param int        $post_id Post id.
	 * @param array      $by_key  Key => alt.
	 * @param array|null $restore Key => [ 'alt' => new alt, 'old' => list ].
	 * @return array Old values per key (when writing).
	 */
	private static function write_post( $post_id, array $by_key, $restore ) {
		$post = get_post( $post_id );
		if ( ! $post ) {
			return array();
		}
		$tags  = new WP_HTML_Tag_Processor( $post->post_content );
		$olds  = array();
		$count = array();
		$dirty = false;
		while ( $tags->next_tag( 'img' ) ) {
			$id  = self::attachment_from_tag( $tags );
			$key = $id ? 'a' . $id : 's' . md5( (string) $tags->get_attribute( 'src' ) );
			$alt = $tags->get_attribute( 'alt' );
			if ( null === $restore ) {
				if ( ! isset( $by_key[ $key ] ) || ( is_string( $alt ) && '' !== trim( $alt ) ) ) {
					continue;
				}
				$olds[ $key ][] = is_string( $alt ) ? $alt : null;
				$tags->set_attribute( 'alt', $by_key[ $key ] );
				$dirty = true;
			} else {
				if ( ! isset( $restore[ $key ] ) || ! is_string( $alt ) || $alt !== $restore[ $key ]['alt'] ) {
					continue;
				}
				$n = isset( $count[ $key ] ) ? $count[ $key ] : 0;
				if ( ! array_key_exists( $n, $restore[ $key ]['old'] ) ) {
					continue;
				}
				$old = $restore[ $key ]['old'][ $n ];
				if ( null === $old ) {
					$tags->remove_attribute( 'alt' );
				} else {
					$tags->set_attribute( 'alt', $old );
				}
				$count[ $key ] = $n + 1;
				$dirty         = true;
			}
		}
		if ( $dirty ) {
			// The content already passed WordPress's checks when it was saved; only alt
			// attributes change here, so don't run it through them again.
			$kses = has_filter( 'content_save_pre', 'wp_filter_post_kses' );
			if ( $kses ) {
				kses_remove_filters();
			}
			$html = $tags->get_updated_html();
			if ( null !== $restore ) {
				// Removing an attribute that was added at the start of the tag leaves a double space.
				$html = preg_replace( '/<img {2,}/i', '<img ', $html );
			}
			wp_update_post(
				array(
					'ID'           => $post_id,
					'post_content' => wp_slash( $html ),
				)
			);
			if ( $kses ) {
				kses_init_filters();
			}
		}
		return $olds;
	}

	/**
	 * Undo a save.
	 *
	 * @param string $batch Batch id.
	 * @return bool
	 */
	public static function undo( $batch ) {
		$log = self::log();
		foreach ( $log as $i => $item ) {
			if ( $item['batch'] !== $batch || ! empty( $item['undone'] ) ) {
				continue;
			}
			$posts = array();
			foreach ( $item['entries'] as $e ) {
				if ( ! empty( $e['media'] ) && current_user_can( 'edit_post', $e['media']['id'] ) ) {
					update_post_meta( $e['media']['id'], '_wp_attachment_image_alt', $e['media']['old'] );
					if ( $e['media']['deco'] ) {
						update_post_meta( $e['media']['id'], self::DECORATIVE, 1 );
					} else {
						delete_post_meta( $e['media']['id'], self::DECORATIVE );
					}
				}
				foreach ( $e['posts'] as $post_id => $olds ) {
					$posts[ $post_id ][ $e['key'] ] = array(
						'alt' => $e['alt'],
						'old' => $olds,
					);
				}
			}
			foreach ( $posts as $post_id => $restore ) {
				if ( current_user_can( 'edit_post', $post_id ) ) {
					self::write_post( $post_id, array(), $restore );
				}
			}
			$log[ $i ]['undone'] = true;
			update_option( self::LOG, $log, false );
			do_action( 'corea11y_fixes_changed', array(), $batch );
			return true;
		}
		return false;
	}

	/**
	 * Saved batches, newest first.
	 *
	 * @return array
	 */
	public static function log() {
		$log = get_option( self::LOG, array() );
		return is_array( $log ) ? $log : array();
	}
}
