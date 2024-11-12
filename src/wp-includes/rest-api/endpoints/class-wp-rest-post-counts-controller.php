<?php
/**
 * REST API: WP_REST_Post_Counts_Controller class
 *
 * @since 6.8.0
 *
 * @package WordPress
 * @subpackage REST_API
 */

/**
 * Core class used to return post counts by post type via the REST API.
 *
 * @since 6.8.0
 *
 * @see WP_REST_Controller
 */
class WP_REST_Post_Counts_Controller extends WP_REST_Controller {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->namespace = 'wp/v2';
		$this->rest_base = 'counts';
	}

	/**
	 * Registers the routes for post counts.
	 *
	 * @since 6.8.0
	 *
	 * @see register_rest_route()
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<post_type>[\w-]+)',
			array(
				'args'   => array(
					'post_type' => array(
						'description' => __( 'An alphanumeric identifier for the post type.' ),
						'type'        => 'string',
					),
				),
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_item' ),
					'permission_callback' => array( $this, 'get_item_permissions_check' ),
					'args'                => array(
						'context' => $this->get_context_param( array( 'default' => 'view' ) ),
					),
				),
				'schema' => array( $this, 'get_public_item_schema' ),
			)
		);
	}

	/**
	 * Checks if a given request has access to read post counts.
	 *
	 * @since 6.8.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return true|WP_Error True if the request has read access, WP_Error object otherwise.
	 */
	public function get_item_permissions_check( $request ) {
		$post_type = get_post_type_object( $request['post_type'] );

		if ( ! $post_type ) {
			return new WP_Error(
				'rest_invalid_post_type',
				__( 'Invalid post type.' ),
				array( 'status' => 404 )
			);
		}

		if ( ! current_user_can( $post_type->cap->read ) ) {
			return new WP_Error(
				'rest_cannot_read',
				__( 'Sorry, you are not allowed to read post counts for this post type.' ),
				array( 'status' => rest_authorization_required_code() )
			);
		}

		return true;
	}

	/**
	 * Retrieves post counts for a specific post type.
	 *
	 * @since 6.8.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function get_item( $request ) {
		$post_type = $request['post_type'];
		$counts    = wp_count_posts( $post_type );
		$data      = $this->prepare_item_for_response( $counts, $request );

		return rest_ensure_response( $data );
	}

	/**
	 * Prepares post counts for response.
	 *
	 * @since 6.8.0
	 *
	 * @param object          $item    Post counts data.
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response Response object.
	 */
	public function prepare_item_for_response( $item, $request ) {
		$data = array();

		if ( ! empty( $item ) ) {
			/*
			 * The fields comprise all non-internal post statuses,
			 * including any custom statuses that may be registered.
			 * 'trash' is an exception, so if it exists, it is added separately.
			 */
			$post_stati = get_post_stati( array( 'internal' => false ) );

			if ( get_post_status_object( 'trash' ) ) {
				$post_stati[] = 'trash';
			}
			// Include all public statuses in the response if there is a count.
			foreach ( $post_stati as $status ) {
				if ( isset( $item->$status ) ) {
					$data[ $status ] = (int) $item->$status;
				}
			}
		}

		$context = ! empty( $request['context'] ) ? $request['context'] : 'view';
		$data    = $this->add_additional_fields_to_object( $data, $request );
		$data    = $this->filter_response_by_context( $data, $context );

		return rest_ensure_response( $data );
	}

	/**
	 * Retrieves the post counts schema, conforming to JSON Schema.
	 *
	 * @since 6.8.0
	 *
	 * @return array Item schema data.
	 */
	public function get_item_schema() {
		if ( $this->schema ) {
			return $this->add_additional_fields_schema( $this->schema );
		}

		$schema = array(
			'$schema'              => 'http://json-schema.org/draft-04/schema#',
			'title'                => 'post-counts',
			'type'                 => 'object',
			/*
			 * Use a pattern matcher for post status keys.
			 * This allows for custom post statuses to be included,
			 * which can be registered after the schema is generated.
			 */
			'patternProperties'    => array(
				'^\w+$' => array(
					'description' => __( 'The number of posts for a given status.' ),
					'type'        => 'integer',
					'context'     => array( 'view', 'edit', 'embed' ),
					'readonly'    => true,
				),
			),
			'additionalProperties' => false,
		);

		$this->schema = $schema;

		return $this->add_additional_fields_schema( $this->schema );
	}
}
