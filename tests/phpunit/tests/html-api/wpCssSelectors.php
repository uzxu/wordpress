<?php
/**
 * Unit tests covering WP_HTML_Processor functionality.
 *
 * @package WordPress
 *
 * @subpackage HTML-API
 *
 * @since TBD
 *
 * @group html-api
 *
 * @coversDefaultClass WP_CSS_Selectors
 */
class Tests_HtmlApi_WpCssSelectors extends WP_UnitTestCase {
	/**
	 * Data provider.
	 */
	public static function data_valid_idents() {
		return array(
			'trailing #'              => array( '_-foo123#xyz', '_-foo123', '#xyz' ),
			'trailing .'              => array( '😍foo123.xyz', '😍foo123', '.xyz' ),
			'trailing " "'            => array( '😍foo123 more', '😍foo123', ' more' ),
			'escaped ASCII character' => array( '\\xyz', 'xyz', '' ),
			'escaped space'           => array( '\\ x', ' x', '' ),
			'escaped emoji'           => array( '\\😍', '😍', '' ),
			'hex unicode codepoint'   => array( '\\abcd', 'ꯍ', '' ),

			'hex tab-suffixed 1'      => array( "\\31\t23", '123', '' ),
			'hex newline-suffixed 1'  => array( "\\31\n23", '123', '' ),
			'hex space-suffixed 1'    => array( "\\31 23", '123', '' ),
			'hex tab'                 => array( '\\9', "\t", '' ),
			'hex a'                   => array( '\\61 bc', 'abc', '' ),
			'hex a max escape length' => array( '\\000061bc', 'abc', '' ),
		);
	}

	/**
	 * @ticket TBD
	 *
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
		$this->assertSame( $ident, $result, 'Ident did not match.' );
		$this->assertSame( substr( $input, $offset ), $rest, 'Offset was not updated correctly.' );
	}
}
