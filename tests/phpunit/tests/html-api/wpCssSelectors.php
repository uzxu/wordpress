<?php
/**
 * Unit tests covering WP_HTML_Processor functionality.
 *
 * @package WordPress
 * @subpackage HTML-API
 *
 * @since TBD
 *
 * @group html-api
 *
 * @coversDefaultClass WP_CSS_Selectors
 */
class Tests_HtmlApi_WpCssSelectors extends WP_UnitTestCase {

	public static function data_valid_idents() {
		return array(
			array( '_-foo123#xyz', '_-foo123', '#xyz' ),
			array( '😍foo123.xyz', '😍foo123', '.xyz' ),
			array( '\\xyz', 'xyz', '' ),
			array( '\\ x', ' x', '' ),
			array( '\\😍', '😍', '' ),
			array( '\\abcd', 'ꯍ', '' ),

			array( "\\31\t23", '123', '' ),
			array( "\\31\n23", '123', '' ),
			array( "\\31 23", '123', '' ),
			array( '\\9', "\t", '' ),
			array( '\\61 bc', 'abc', '' ),
			array( '\\000061bc', 'abc', '' ),
		);
	}

	/**
	 * @dataProvider data_valid_idents
	 */
	public function test_valid_idents( string $input, string $result, string $rest ) {
		$c = new class() extends WP_CSS_Selector_Parser {
			public static function parse( string $input, string &$offset ) {}
			public static function test( string $input, &$offset ) {
				return self::parse_ident( $input, $offset );
			}
		};

		$offset = 0;
		$ident  = $c::test( $input, $offset );
		$this->assertSame( $ident, $result );
		$this->assertSame( substr( $input, $offset ), $rest );
	}
}
