<?php
/**
 * @group block-supports
 *
 * @covers ::wp_apply_border_support
 */
class Tests_Block_Supports_Border extends WP_UnitTestCase {
	/**
	 * @var string|null
	 */
	private $test_block_name;

	public function set_up() {
		parent::set_up();
		$this->test_block_name = null;
	}

	public function tear_down() {
		unregister_block_type( $this->test_block_name );
		$this->test_block_name = null;
		parent::tear_down();
	}

	/**
	 * @ticket 55505
	 */
	public function test_border_color_slug_with_numbers_is_kebab_cased_properly() {
		$this->test_block_name = 'test/border-color-slug-with-numbers-is-kebab-cased-properly';
		register_block_type(
			$this->test_block_name,
			array(
				'api_version' => 2,
				'attributes'  => array(
					'borderColor' => array(
						'type' => 'string',
					),
					'style'       => array(
						'type' => 'object',
					),
				),
				'supports'    => array(
					'__experimentalBorder' => array(
						'color'  => true,
						'radius' => true,
						'width'  => true,
						'style'  => true,
					),
				),
			)
		);
		$registry   = WP_Block_Type_Registry::get_instance();
		$block_type = $registry->get_registered( $this->test_block_name );
		$block_atts = array(
			'borderColor' => 'red',
			'style'       => array(
				'border' => array(
					'radius' => '10px',
					'width'  => '1px',
					'style'  => 'dashed',
				),
			),
		);

		$actual   = wp_apply_border_support( $block_type, $block_atts );
		$expected = array(
			'class' => 'has-border-color has-red-border-color',
			'style' => 'border-radius:10px;border-style:dashed;border-width:1px;',
		);

		$this->assertSame( $expected, $actual );
	}

	/**
	 * @ticket 55505
	 */
	public function test_border_with_skipped_serialization_block_supports() {
		$this->test_block_name = 'test/border-with-skipped-serialization-block-supports';
		register_block_type(
			$this->test_block_name,
			array(
				'api_version' => 2,
				'attributes'  => array(
					'style' => array(
						'type' => 'object',
					),
				),
				'supports'    => array(
					'__experimentalBorder' => array(
						'color'             => true,
						'radius'            => true,
						'width'             => true,
						'style'             => true,
						'skipSerialization' => true,
					),
				),
			)
		);
		$registry   = WP_Block_Type_Registry::get_instance();
		$block_type = $registry->get_registered( $this->test_block_name );
		$block_atts = array(
			'style' => array(
				'border' => array(
					'color'  => '#eeeeee',
					'width'  => '1px',
					'style'  => 'dotted',
					'radius' => '10px',
				),
			),
		);

		$actual   = wp_apply_border_support( $block_type, $block_atts );
		$expected = array();

		$this->assertSame( $expected, $actual );
	}

	/**
	 * @ticket 55505
	 */
	public function test_radius_with_individual_skipped_serialization_block_supports() {
		$this->test_block_name = 'test/radius-with-individual-skipped-serialization-block-supports';
		register_block_type(
			$this->test_block_name,
			array(
				'api_version' => 2,
				'attributes'  => array(
					'style' => array(
						'type' => 'object',
					),
				),
				'supports'    => array(
					'__experimentalBorder' => array(
						'color'                           => true,
						'radius'                          => true,
						'width'                           => true,
						'style'                           => true,
						'__experimentalSkipSerialization' => array( 'radius', 'color' ),
					),
				),
			)
		);
		$registry   = WP_Block_Type_Registry::get_instance();
		$block_type = $registry->get_registered( $this->test_block_name );
		$block_atts = array(
			'style' => array(
				'border' => array(
					'color'  => '#eeeeee',
					'width'  => '1px',
					'style'  => 'dotted',
					'radius' => '10px',
				),
			),
		);

		$actual   = wp_apply_border_support( $block_type, $block_atts );
		$expected = array(
			'style' => 'border-style:dotted;border-width:1px;',
		);

		$this->assertSame( $expected, $actual );
	}

	/**
	 * Tests that stabilized border supports will also apply to blocks using
	 * the experimental syntax, for backwards compatibility with existing blocks.
	 *
	 * @ticket 61728
	 *
	 * @covers ::wp_apply_border_support
	 */
	public function test_should_apply_experimental_border_supports() {
		$this->test_block_name = 'test/experimental-border-supports';
		register_block_type(
			$this->test_block_name,
			array(
				'api_version' => 3,
				'attributes'  => array(
					'style' => array(
						'type' => 'object',
					),
				),
				'supports'    => array(
					'__experimentalBorder' => array(
						'color'                         => true,
						'radius'                        => true,
						'style'                         => true,
						'width'                         => true,
						'__experimentalDefaultControls' => array(
							'color'  => true,
							'radius' => true,
							'style'  => true,
							'width'  => true,
						),
					),
				),
			)
		);
		$registry   = WP_Block_Type_Registry::get_instance();
		$block_type = $registry->get_registered( $this->test_block_name );
		$block_atts = array(
			'style' => array(
				'border' => array(
					'color'  => '#72aee6',
					'radius' => '10px',
					'style'  => 'dashed',
					'width'  => '2px',
				),
			),
		);

		$actual   = wp_apply_border_support( $block_type, $block_atts );
		$expected = array(
			'class' => 'has-border-color',
			'style' => 'border-color:#72aee6;border-radius:10px;border-style:dashed;border-width:2px;',
		);

		$this->assertSame( $expected, $actual );
	}

	/**
	 * Tests that stabilized border supports are applied correctly.
	 *
	 * @ticket 61728
	 *
	 * @covers ::wp_apply_border_support
	 */
	public function test_should_apply_stabilized_border_supports() {
		$this->test_block_name = 'test/stabilized-border-supports';
		register_block_type(
			$this->test_block_name,
			array(
				'api_version' => 3,
				'attributes'  => array(
					'style' => array(
						'type' => 'object',
					),
				),
				'supports'    => array(
					'border' => array(
						'color'                         => true,
						'radius'                        => true,
						'style'                         => true,
						'width'                         => true,
						'__experimentalDefaultControls' => array(
							'color'  => true,
							'radius' => true,
							'style'  => true,
							'width'  => true,
						),
					),
				),
			)
		);
		$registry   = WP_Block_Type_Registry::get_instance();
		$block_type = $registry->get_registered( $this->test_block_name );
		$block_atts = array(
			'style' => array(
				'border' => array(
					'color'  => '#72aee6',
					'radius' => '10px',
					'style'  => 'dashed',
					'width'  => '2px',
				),
			),
		);

		$actual   = wp_apply_border_support( $block_type, $block_atts );
		$expected = array(
			'class' => 'has-border-color',
			'style' => 'border-color:#72aee6;border-radius:10px;border-style:dashed;border-width:2px;',
		);

		$this->assertSame( $expected, $actual );
	}

	/**
	 * Tests that experimental border support configuration gets stabilized correctly.
	 *
	 * @ticket 61728
	 */
	public function test_should_stabilize_border_supports() {
		$reflection = new ReflectionClass( WP_Block_Type::class );
		$method     = $reflection->getMethod( 'stabilize_supports' );
		$method->setAccessible( true );
		$block_type = new WP_Block_Type( 'test/block' );

		$supports = array(
			'__experimentalBorder' => array(
				'color'                           => true,
				'radius'                          => true,
				'style'                           => true,
				'width'                           => true,
				'__experimentalSkipSerialization' => true,
				'__experimentalDefaultControls'   => array(
					'color'  => true,
					'radius' => true,
					'style'  => true,
					'width'  => true,
				),
			),
		);

		$actual   = $method->invoke( $block_type, $supports );
		$expected = array(
			'border' => array(
				'color'             => true,
				'radius'            => true,
				'style'             => true,
				'width'             => true,
				'skipSerialization' => true,
				'defaultControls'   => array(
					'color'  => true,
					'radius' => true,
					'style'  => true,
					'width'  => true,
				),
			),
		);

		$this->assertSame( $expected, $actual, 'Stabilized border block support config does not match.' );
	}

	/**
	 * Tests the merging of border support configuration when stabilizing
	 * experimental config. Due to the ability to filter block type args, plugins
	 * or themes could filter using outdated experimental keys. While not every
	 * permutation of filtering can be covered, the majority of use cases are
	 * served best by merging configs based on the order they were defined if possible.
	 *
	 * @ticket 61728
	 */
	public function test_should_stabilize_border_supports_using_order_based_merge() {
		$reflection = new ReflectionClass( WP_Block_Type::class );
		$method     = $reflection->getMethod( 'stabilize_supports' );
		$method->setAccessible( true );
		$block_type = new WP_Block_Type( 'test/block' );

		$experimental_border_config = array(
			'color'                           => true,
			'radius'                          => true,
			'style'                           => true,
			'width'                           => true,
			'__experimentalSkipSerialization' => true,
			'__experimentalDefaultControls'   => array(
				'color'  => true,
				'radius' => true,
				'style'  => true,
				'width'  => true,
			),

			/*
			 * The following simulates theme/plugin filtering using `__experimentalBorder`
			 * key but stable serialization and default control keys.
			 */
			'skipSerialization'               => false,
			'defaultControls'                 => array(
				'color'  => true,
				'radius' => false,
				'style'  => true,
				'width'  => true,
			),
		);
		$stable_border_config = array(
			'color'                           => true,
			'radius'                          => true,
			'style'                           => false,
			'width'                           => true,
			'skipSerialization'               => false,
			'defaultControls'                 => array(
				'color'  => true,
				'radius' => false,
				'style'  => false,
				'width'  => true,
			),

			/*
			 * The following simulates theme/plugin filtering using stable `border` key
			 * but experimental serialization and default control keys.
			 */
			'__experimentalSkipSerialization' => true,
			'__experimentalDefaultControls'   => array(
				'color'  => false,
				'radius' => false,
				'style'  => false,
				'width'  => false,
			),
		);

		// Test with experimental config first.
		$experimental_first = array(
			'__experimentalBorder' => $experimental_border_config,
			'border'               => $stable_border_config,
		);

		$actual   = $method->invoke( $block_type, $experimental_first );
		$expected = array(
			'border' => array(
				'color'             => true,
				'radius'            => true,
				'style'             => false,
				'width'             => true,
				'skipSerialization' => true,
				'defaultControls'   => array(
					'color'  => false,
					'radius' => false,
					'style'  => false,
					'width'  => false,
				),
			),
		);

		$this->assertSame( $expected, $actual, 'Merged stabilized border block support config does not match when experimental keys are first.' );

		// Test with stable config first.
		$stable_first = array(
			'border'               => $stable_border_config,
			'__experimentalBorder' => $experimental_border_config,
		);

		$actual   = $method->invoke( $block_type, $stable_first );
		$expected = array(
			'border' => array(
				'color'             => true,
				'radius'            => true,
				'style'             => true,
				'width'             => true,
				'skipSerialization' => false,
				'defaultControls'   => array(
					'color'  => true,
					'radius' => false,
					'style'  => true,
					'width'  => true,
				),
			),
		);

		$this->assertSame( $expected, $actual, 'Merged stabilized border block support config does not match when stable keys are first.' );
	}
}
