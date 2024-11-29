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
			'trailing #'                         => array( '_-foo123#xyz', '_-foo123', '#xyz' ),
			'trailing .'                         => array( '😍foo123.xyz', '😍foo123', '.xyz' ),
			'trailing " "'                       => array( '😍foo123 more', '😍foo123', ' more' ),
			'escaped ASCII character'            => array( '\\xyz', 'xyz', '' ),
			'escaped space'                      => array( '\\ x', ' x', '' ),
			'escaped emoji'                      => array( '\\😍', '😍', '' ),
			'hex unicode codepoint'              => array( '\\1f0a1', '🂡', '' ),
			'HEX UNICODE CODEPOINT'              => array( '\\1D4B2', '𝒲', '' ),

			'hex tab-suffixed 1'                 => array( "\\31\t23", '123', '' ),
			'hex newline-suffixed 1'             => array( "\\31\n23", '123', '' ),
			'hex space-suffixed 1'               => array( "\\31 23", '123', '' ),
			'hex tab'                            => array( '\\9', "\t", '' ),
			'hex a'                              => array( '\\61 bc', 'abc', '' ),
			'hex a max escape length'            => array( '\\000061bc', 'abc', '' ),

			'out of range replacement min'       => array( '\\110000 ', "\u{fffd}", '' ),
			'out of range replacement max'       => array( '\\ffffff ', "\u{fffd}", '' ),
			'leading surrogate min replacement'  => array( '\\d800 ', "\u{fffd}", '' ),
			'leading surrogate max replacement'  => array( '\\dbff ', "\u{fffd}", '' ),
			'trailing surrogate min replacement' => array( '\\dc00 ', "\u{fffd}", '' ),
			'trailing surrogate max replacement' => array( '\\dfff ', "\u{fffd}", '' ),
		);
	}

	/**
	 * Data provider.
	 */
	public static function data_invalid_idents() {
		return array(
			'bad start >'   => array( '>' ),
			'bad start ['   => array( '[' ),
			'bad start #'   => array( '#' ),
			'bad start " "' => array( ' ' ),
			'bad start -'   => array( '-' ),
			'bad start 1'   => array( '-' ),
		);
	}

	/**
	 * @ticket TBD
	 *
	 * @dataProvider data_valid_idents
	 */
	public function test_valid_idents( string $input, string $result, string $rest ) {
		$c = new class() extends WP_CSS_Selector_Parser {
			public static function parse( string $input, int &$offset ) {}
			public static function test( string $input, &$offset ) {
				return self::parse_ident( $input, $offset );
			}
		};

		$offset = 0;
		$ident  = $c::test( $input, $offset );
		$this->assertSame( $ident, $result, 'Ident did not match.' );
		$this->assertSame( substr( $input, $offset ), $rest, 'Offset was not updated correctly.' );
	}

	/**
	 * @ticket TBD
	 *
	 * @dataProvider data_invalid_idents
	 */
	public function test_invalid_idents( string $input ) {
		$c = new class() extends WP_CSS_Selector_Parser {
			public static function parse( string $input, int &$offset ) {}
			public static function test( string $input, int &$offset ) {
				return self::parse_ident( $input, $offset );
			}
		};

		$offset = 0;
		$result = $c::test( $input, $offset );
		$this->assertNull( $result, 'Ident did not match.' );
		$this->assertSame( 0, $offset, 'Offset was incorrectly adjusted.' );
	}

	/**
	 * @ticket TBD
	 *
	 * @dataProvider data_ids
	 */
	public function test_parse_id( string $input, ?string $expected_id = null, ?string $rest = null ) {
		$offset = 0;
		$result = WP_CSS_ID_Selector::parse( $input, $offset );
		if ( null === $expected_id ) {
			$this->assertNull( $result );
		} else {
			$this->assertSame( $result->ident, $expected_id );
			$this->assertSame( substr( $input, $offset ), $rest );
		}
	}

	public static function data_ids(): array {
		return array(
			'valid #_-foo123'             => array( '#_-foo123', '_-foo123', '' ),
			'valid #foo#bar'              => array( '#foo#bar', 'foo', '#bar' ),
			'escaped #\31 23'             => array( '#\\31 23', '123', '' ),
			'with descendant #\31 23 div' => array( '#\\31 23 div', '123', ' div' ),

			'not ID foo'                  => array( 'foo' ),
			'not valid #1foo'             => array( '#1foo' ),
			'not id .bar'                 => array( '.bar' ),
		);
	}
}
