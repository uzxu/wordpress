<?php
/**
 * Unit tests for WP_REST_Post_Counts_Controller.
 *
 * @package WordPress
 * @subpackage REST API
 *
 * @group restapi
 */
class WP_Test_REST_Post_Counts_Controller extends WP_Test_REST_Controller_Testcase {
	/**
	 * @var int
	 */
	protected static $admin_id;

	public static function wpSetUpBeforeClass( WP_UnitTest_Factory $factory ) {
		self::$admin_id = $factory->user->create(
			array(
				'role' => 'administrator',
			)
		);

		if ( is_multisite() ) {
			grant_super_admin( self::$admin_id );
		}
	}

	public static function wpTearDownAfterClass() {
		self::delete_user( self::$admin_id );
	}

	public function set_up() {
		parent::set_up();

		register_post_type(
			'private-cpt',
			array(
				'public'             => false,
				'publicly_queryable' => false,
				'show_ui'            => true,
				'show_in_menu'       => true,
				'show_in_rest'       => true,
				'rest_base'          => 'private-cpts',
				'capability_type'    => 'post',
			)
		);
	}

	public function tear_down() {
		unregister_post_type( 'private-cpt' );
		parent::tear_down();
	}

	/**
	 * @covers WP_REST_Post_Counts_Controller::register_routes
	 */
	public function test_register_routes() {
		$routes = rest_get_server()->get_routes();
		$this->assertArrayHasKey( '/wp/v2/counts/(?P<post_type>[\w-]+)', $routes );
	}

	/**
	 * @covers WP_REST_Post_Counts_Controller::get_item_schema
	 */
	public function test_get_item_schema() {
		$request    = new WP_REST_Request( 'OPTIONS', '/wp/v2/counts/post' );
		$response   = rest_get_server()->dispatch( $request );
		$data       = $response->get_data();
		$properties = $data['schema']['patternProperties'];

		$this->assertCount( 1, $properties );
		$this->assertArrayHasKey( '^\w+$', $properties );
	}

	/**
	 * @covers WP_REST_Post_Counts_Controller::get_item
	 */
	public function test_get_item_response() {
		wp_set_current_user( self::$admin_id );
		$request  = new WP_REST_Request( 'GET', '/wp/v2/counts/post' );
		$response = rest_get_server()->dispatch( $request );
		$data     = $response->get_data();

		$this->assertSame( 200, $response->get_status() );
		$this->assertArrayHasKey( 'publish', $data );
		$this->assertArrayHasKey( 'future', $data );
		$this->assertArrayHasKey( 'draft', $data );
		$this->assertArrayHasKey( 'pending', $data );
		$this->assertArrayHasKey( 'private', $data );
		$this->assertArrayHasKey( 'trash', $data );
	}

	/**
	 * @covers WP_REST_Post_Counts_Controller::get_item
	 */
	public function test_get_item() {
		wp_set_current_user( self::$admin_id );
		register_post_status( 'post_counts_status', array( 'public' => true ) );

		$published = self::factory()->post->create( array( 'post_status' => 'publish' ) );
		$future    = self::factory()->post->create(
			array(
				'post_status' => 'future',
				'post_date'   => gmdate( 'Y-m-d H:i:s', strtotime( '+1 day' ) ),
			)
		);
		$draft     = self::factory()->post->create( array( 'post_status' => 'draft' ) );
		$pending   = self::factory()->post->create( array( 'post_status' => 'pending' ) );
		$private   = self::factory()->post->create( array( 'post_status' => 'private' ) );
		$trashed   = self::factory()->post->create( array( 'post_status' => 'trash' ) );
		$custom    = self::factory()->post->create( array( 'post_status' => 'post_counts_status' ) );

		$request  = new WP_REST_Request( 'GET', '/wp/v2/counts/post' );
		$response = rest_get_server()->dispatch( $request );
		$data     = $response->get_data();

		$this->assertSame( 1, $data['publish'], 'Published post count mismatch.' );
		$this->assertSame( 1, $data['future'], 'Future post count mismatch.' );
		$this->assertSame( 1, $data['draft'], 'Draft post count mismatch.' );
		$this->assertSame( 1, $data['pending'], 'Pending post count mismatch.' );
		$this->assertSame( 1, $data['private'], 'Private post count mismatch.' );
		$this->assertSame( 1, $data['trash'], 'Trashed post count mismatch.' );
		$this->assertSame( 1, $data['post_counts_status'], 'Custom post count mismatch.' );

		wp_delete_post( $published, true );
		wp_delete_post( $future, true );
		wp_delete_post( $draft, true );
		wp_delete_post( $pending, true );
		wp_delete_post( $private, true );
		wp_delete_post( $trashed, true );
		wp_delete_post( $custom, true );
		_unregister_post_status( 'post_counts_status' );
	}

	/**
	 * @covers WP_REST_Post_Counts_Controller::get_item
	 */
	public function test_get_item_with_sanitized_custom_post_status() {
		wp_set_current_user( self::$admin_id );
		register_post_status( '#<>post-me_AND9!', array( 'public' => true ) );

		$custom   = self::factory()->post->create( array( 'post_status' => 'post-me_and9' ) );
		$request  = new WP_REST_Request( 'GET', '/wp/v2/counts/post' );
		$response = rest_get_server()->dispatch( $request );
		$data     = $response->get_data();

		$this->assertSame( 1, $data['post-me_and9'], 'Custom post count mismatch.' );

		wp_delete_post( $custom, true );
		_unregister_post_status( 'post-me_and9' );
	}

	/**
	 * @covers WP_REST_Post_Counts_Controller::get_item_permissions_check
	 */
	public function test_get_item_private_post_type() {
		wp_set_current_user( self::$admin_id );
		$request  = new WP_REST_Request( 'GET', '/wp/v2/counts/private-cpt' );
		$response = rest_get_server()->dispatch( $request );

		$this->assertSame( 200, $response->get_status() );
	}

	/**
	 * @covers WP_REST_Post_Counts_Controller::get_item_permissions_check
	 */
	public function test_get_item_invalid_post_type() {
		wp_set_current_user( self::$admin_id );
		$request  = new WP_REST_Request( 'GET', '/wp/v2/counts/invalid-type' );
		$response = rest_get_server()->dispatch( $request );

		$this->assertErrorResponse( 'rest_invalid_post_type', $response, 404 );
	}

	/**
	 * @covers WP_REST_Post_Counts_Controller::get_item_permissions_check
	 */
	public function test_get_item_invalid_permission() {
		wp_set_current_user( 0 );
		$request  = new WP_REST_Request( 'GET', '/wp/v2/counts/post' );
		$response = rest_get_server()->dispatch( $request );

		$this->assertErrorResponse( 'rest_cannot_read', $response, 401 );
	}

	/**
	 * @doesNotPerformAssertions
	 */
	public function test_get_items() {
		// Controller does not implement delete_item().
	}

	/**
	 * @doesNotPerformAssertions
	 */
	public function test_delete_item() {
		// Controller does not implement delete_item().
	}

	/**
	 * @doesNotPerformAssertions
	 */
	public function test_create_item() {
		// Controller does not implement test_create_item().
	}

	/**
	 * @doesNotPerformAssertions
	 */
	public function test_update_item() {
		// Controller does not implement test_update_item().
	}

	/**
	 * @doesNotPerformAssertions
	 */
	public function test_prepare_item() {
		// Controller does not implement test_prepare_item().
	}

	/**
	 * @doesNotPerformAssertions
	 */
	public function test_context_param() {
		// Controller does not implement context_param().
	}
}
