<?php
/**
 * REST API routes.
 *
 * @package CoreA11y
 */

defined( 'ABSPATH' ) || exit;

/**
 * Registers routes under corea11y/v1. All routes require manage_options.
 */
class CoreA11y_Rest {

	const NS = 'corea11y/v1';

	/**
	 * Hook into WordPress.
	 */
	public function register() {
		add_action( 'rest_api_init', array( $this, 'routes' ) );
	}

	/**
	 * Register routes.
	 */
	public function routes() {
		register_rest_route(
			self::NS,
			'/status',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'status' ),
				'permission_callback' => array( $this, 'can_manage' ),
			)
		);

		register_rest_route(
			self::NS,
			'/audits',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'create_audit' ),
				'permission_callback' => array( $this, 'can_manage' ),
			)
		);

		register_rest_route(
			self::NS,
			'/audits/(?P<id>\d+)/pages',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'page_results' ),
				'permission_callback' => array( $this, 'can_manage' ),
				'args'                => array(
					'id'         => array(
						'type'     => 'integer',
						'required' => true,
					),
					'url'        => array(
						'type'     => 'string',
						'format'   => 'uri',
						'required' => true,
					),
					'violations' => array(
						'type'    => 'array',
						'default' => array(),
					),
					'review'     => array(
						'type'    => 'array',
						'default' => array(),
					),
					'error'      => array(
						'type'              => 'string',
						'default'           => '',
						'sanitize_callback' => 'sanitize_text_field',
					),
				),
			)
		);

		register_rest_route(
			self::NS,
			'/fixes/verify',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'verify_fixes' ),
				'permission_callback' => array( $this, 'can_manage' ),
				'args'                => array(
					'audit'   => array(
						'type'     => 'integer',
						'required' => true,
					),
					'results' => array(
						'type'    => 'array',
						'default' => array(),
					),
				),
			)
		);

		register_rest_route(
			self::NS,
			'/audits/(?P<id>\d+)/complete',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'complete_audit' ),
				'permission_callback' => array( $this, 'can_manage' ),
				'args'                => array(
					'id' => array(
						'type'     => 'integer',
						'required' => true,
					),
				),
			)
		);
	}

	/**
	 * Save the result of re-checking pages after quick fixes were turned on.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public function verify_fixes( WP_REST_Request $request ) {
		$clean = array();
		foreach ( (array) $request['results'] as $r ) {
			if ( ! is_array( $r ) || ! isset( $r['fix'], $r['url'], $r['after'] ) ) {
				continue;
			}
			$clean[] = array(
				'fix'   => sanitize_key( $r['fix'] ),
				'url'   => esc_url_raw( (string) $r['url'] ),
				'after' => (int) $r['after'],
			);
		}
		return rest_ensure_response( array( 'fixes' => CoreA11y_Fixes::record_verification( (int) $request['audit'], array_slice( $clean, 0, 500 ) ) ) );
	}

	/**
	 * Start an audit: build the page list and create the record.
	 *
	 * @return WP_REST_Response|WP_Error
	 */
	public function create_audit() {
		$scope = CoreA11y_Settings::get( 'scan_scope' );
		$pages = CoreA11y_Pages::for_scope( $scope );
		$id    = CoreA11y_Audit::create( $scope, $pages );
		if ( ! $id ) {
			return new WP_Error( 'corea11y_create_failed', __( 'The audit could not be started.', 'corea11y' ), array( 'status' => 500 ) );
		}
		return rest_ensure_response(
			array(
				'id'    => $id,
				'pages' => $pages,
			)
		);
	}

	/**
	 * Save results for one page.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function page_results( WP_REST_Request $request ) {
		$url    = esc_url_raw( $request['url'] );
		$stored = CoreA11y_Audit::add_page_results(
			(int) $request['id'],
			$url,
			url_to_postid( $url ),
			(array) $request['violations'],
			(string) $request['error'],
			(array) $request['review']
		);
		if ( is_wp_error( $stored ) ) {
			return $stored;
		}
		return rest_ensure_response( array( 'stored' => $stored ) );
	}

	/**
	 * Finish an audit.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function complete_audit( WP_REST_Request $request ) {
		$audit = CoreA11y_Audit::complete( (int) $request['id'] );
		if ( is_wp_error( $audit ) ) {
			return $audit;
		}
		return rest_ensure_response(
			array(
				'id'     => (int) $audit->id,
				'status' => $audit->status,
				'issues' => (int) $audit->issues_total,
				'score'  => null === $audit->score ? null : (int) $audit->score,
			)
		);
	}

	/**
	 * Permission check shared by all routes.
	 *
	 * @return bool
	 */
	public function can_manage() {
		return current_user_can( 'manage_options' );
	}

	/**
	 * Plugin status.
	 *
	 * @return WP_REST_Response
	 */
	public function status() {
		return rest_ensure_response(
			array(
				'version'    => COREA11Y_VERSION,
				'db_version' => get_option( CoreA11y_Install::DB_VERSION_OPTION ),
				'pro'        => (bool) apply_filters( 'corea11y_is_pro', false ),
			)
		);
	}
}
