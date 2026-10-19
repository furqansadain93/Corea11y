<?php
/**
 * Builds the list of pages an audit scans.
 *
 * @package CoreA11y
 */

defined( 'ABSPATH' ) || exit;

/**
 * Page discovery for each scan scope.
 */
class CoreA11y_Pages {

	/**
	 * Upper limit on pages per audit.
	 */
	const MAX_PAGES = 500;

	/**
	 * Pages for the configured scope.
	 *
	 * @param string $scope sample|all|custom.
	 * @return array<int,array{url:string,object_id:int,label:string}>
	 */
	public static function for_scope( $scope ) {
		switch ( $scope ) {
			case 'all':
				$pages = self::all();
				break;
			case 'custom':
				$pages = self::custom();
				break;
			default:
				$pages = self::sample();
		}

		/**
		 * Filter the pages an audit will scan.
		 *
		 * @param array  $pages Pages: url, object_id, label.
		 * @param string $scope Scan scope.
		 */
		$pages = apply_filters( 'corea11y_audit_pages', $pages, $scope );

		return array_slice( self::unique( $pages ), 0, self::MAX_PAGES );
	}

	/**
	 * One page per template: home, latest item of each public post type, one category, search.
	 *
	 * @return array
	 */
	public static function sample() {
		$pages = array( self::item( home_url( '/' ), 0, __( 'Home page', 'corea11y' ) ) );

		foreach ( self::post_types() as $type ) {
			$ids = get_posts(
				array(
					'post_type'      => $type->name,
					'post_status'    => 'publish',
					'posts_per_page' => 1,
					'fields'         => 'ids',
					'has_password'   => false,
				)
			);
			if ( $ids ) {
				$pages[] = self::item( get_permalink( $ids[0] ), $ids[0], $type->labels->singular_name );
			}
		}

		$cats = get_terms(
			array(
				'taxonomy'   => 'category',
				'number'     => 1,
				'hide_empty' => true,
			)
		);
		if ( ! is_wp_error( $cats ) && $cats ) {
			$link = get_term_link( $cats[0] );
			if ( ! is_wp_error( $link ) ) {
				$pages[] = self::item( $link, 0, __( 'Category archive', 'corea11y' ) );
			}
		}

		$pages[] = self::item( add_query_arg( 's', 'a', home_url( '/' ) ), 0, __( 'Search results', 'corea11y' ) );

		return $pages;
	}

	/**
	 * Every published, non-password-protected item of each public post type.
	 *
	 * @return array
	 */
	public static function all() {
		$pages = array( self::item( home_url( '/' ), 0, __( 'Home page', 'corea11y' ) ) );
		$types = wp_list_pluck( self::post_types(), 'name' );
		if ( ! $types ) {
			return $pages;
		}
		$ids = get_posts(
			array(
				'post_type'      => array_values( $types ),
				'post_status'    => 'publish',
				'posts_per_page' => self::MAX_PAGES,
				'fields'         => 'ids',
				'has_password'   => false,
				'orderby'        => 'menu_order date',
				'order'          => 'ASC',
			)
		);
		foreach ( $ids as $id ) {
			$pages[] = self::item( get_permalink( $id ), $id, get_the_title( $id ) );
		}
		return $pages;
	}

	/**
	 * URLs from settings.
	 *
	 * @return array
	 */
	public static function custom() {
		$pages = array();
		foreach ( array_filter( explode( "\n", (string) CoreA11y_Settings::get( 'custom_urls' ) ) ) as $url ) {
			$pages[] = self::item( $url, url_to_postid( $url ), $url );
		}
		return $pages ? $pages : array( self::item( home_url( '/' ), 0, __( 'Home page', 'corea11y' ) ) );
	}

	/**
	 * Public, viewable post types except attachments.
	 *
	 * @return WP_Post_Type[]
	 */
	private static function post_types() {
		$types = get_post_types( array( 'public' => true ), 'objects' );
		unset( $types['attachment'] );
		return array_filter( $types, 'is_post_type_viewable' );
	}

	/**
	 * Build a page item.
	 *
	 * @param string $url       URL.
	 * @param int    $object_id Post id or 0.
	 * @param string $label     Human label.
	 * @return array
	 */
	private static function item( $url, $object_id, $label ) {
		return array(
			'url'       => esc_url_raw( $url ),
			'object_id' => (int) $object_id,
			'label'     => wp_strip_all_tags( (string) $label ),
		);
	}

	/**
	 * Drop duplicate URLs and empty entries.
	 *
	 * @param array $pages Pages.
	 * @return array
	 */
	private static function unique( $pages ) {
		$seen = array();
		$out  = array();
		foreach ( $pages as $page ) {
			if ( empty( $page['url'] ) || isset( $seen[ $page['url'] ] ) ) {
				continue;
			}
			$seen[ $page['url'] ] = true;
			$out[]                = $page;
		}
		return $out;
	}
}
