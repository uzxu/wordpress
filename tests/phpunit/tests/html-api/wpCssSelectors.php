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
	 *
	 * @return array
	 */
	public static function data_idents(): array {
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
			'can start with -ident'              => array( '-ident', '-ident', '' ),
			'can start with --anything'          => array( '--anything', '--anything', '' ),
			'can start with ---anything'         => array( '--_anything', '--_anything', '' ),
			'can start with --1anything'         => array( '--1anything', '--1anything', '' ),
			'can start with -\31 23'             => array( '-\31 23', '-123', '' ),
			'can start with --\31 23'            => array( '--\31 23', '--123', '' ),
			'ident ends before ]'                => array( 'ident]', 'ident', ']' ),

			// Invalid
			'bad start >'                        => array( '>ident' ),
			'bad start ['                        => array( '[ident' ),
			'bad start #'                        => array( '#ident' ),
			'bad start " "'                      => array( ' ident' ),
			'bad start 1'                        => array( '1ident' ),
			'bad start -1'                       => array( '-1ident' ),
		);
	}

	/**
	 * @ticket TBD
	 */
	public function test_is_ident_and_is_ident_start() {
		$c = new class() extends WP_CSS_Selector_Parser {
			public static function parse( string $input, int &$offset ) {}

			public static function test_is_ident( string $input, int $offset ) {
				return self::is_ident_codepoint( $input, $offset );
			}

			public static function test_is_ident_start( string $input, int $offset ) {
				return self::is_ident_start_codepoint( $input, $offset );
			}
		};

		$this->assertFalse( $c::test_is_ident( '[', 0 ) );
		$this->assertFalse( $c::test_is_ident( ']', 0 ) );
		$this->assertFalse( $c::test_is_ident_start( '[', 0 ) );
		$this->assertFalse( $c::test_is_ident_start( ']', 0 ) );
	}

	/**
	 * @ticket TBD
	 *
	 * @dataProvider data_idents
	 */
	public function test_parse_ident( string $input, ?string $expected = null, ?string $rest = null ) {
		$c = new class() extends WP_CSS_Selector_Parser {
			public static function parse( string $input, int &$offset ) {}
			public static function test( string $input, &$offset ) {
				return self::parse_ident( $input, $offset );
			}
		};

		$offset = 0;
		$result = $c::test( $input, $offset );
		if ( null === $expected ) {
			$this->assertNull( $result );
		} else {
			$this->assertSame( $expected, $result, 'Ident did not match.' );
			$this->assertSame( $rest, substr( $input, $offset ), 'Offset was not updated correctly.' );
		}
	}

	/**
	 * @ticket TBD
	 *
	 * @dataProvider data_id_selectors
	 */
	public function test_parse_id( string $input, ?string $expected = null, ?string $rest = null ) {
		$offset = 0;
		$result = WP_CSS_ID_Selector::parse( $input, $offset );
		if ( null === $expected ) {
			$this->assertNull( $result );
		} else {
			$this->assertSame( $expected, $result->ident );
			$this->assertSame( $rest, substr( $input, $offset ) );
		}
	}

	/**
	 * Data provider.
	 *
	 * @return array
	 */
	public static function data_id_selectors(): array {
		return array(
			'valid #_-foo123'             => array( '#_-foo123', '_-foo123', '' ),
			'valid #foo#bar'              => array( '#foo#bar', 'foo', '#bar' ),
			'escaped #\31 23'             => array( '#\\31 23', '123', '' ),
			'with descendant #\31 23 div' => array( '#\\31 23 div', '123', ' div' ),

			'not ID foo'                  => array( 'foo' ),
			'not ID .bar'                 => array( '.bar' ),
			'not valid #1foo'             => array( '#1foo' ),
		);
	}

	/**
	 * @ticket TBD
	 *
	 * @dataProvider data_class_selectors
	 */
	public function test_parse_class( string $input, ?string $expected = null, ?string $rest = null ) {
		$offset = 0;
		$result = WP_CSS_Class_Selector::parse( $input, $offset );
		if ( null === $expected ) {
			$this->assertNull( $result );
		} else {
			$this->assertSame( $expected, $result->ident );
			$this->assertSame( $rest, substr( $input, $offset ) );
		}
	}

	/**
	 * Data provider.
	 *
	 * @return array
	 */
	public static function data_class_selectors(): array {
		return array(
			'valid ._-foo123'             => array( '._-foo123', '_-foo123', '' ),
			'valid .foo.bar'              => array( '.foo.bar', 'foo', '.bar' ),
			'escaped .\31 23'             => array( '.\\31 23', '123', '' ),
			'with descendant .\31 23 div' => array( '.\\31 23 div', '123', ' div' ),

			'not class foo'               => array( 'foo' ),
			'not class #bar'              => array( '#bar' ),
			'not valid .1foo'             => array( '.1foo' ),
		);
	}

	/**
	 * @ticket TBD
	 *
	 * @dataProvider data_type_selectors
	 */
	public function test_parse_type( string $input, ?string $expected = null, ?string $rest = null ) {
		$offset = 0;
		$result = WP_CSS_Type_Selector::parse( $input, $offset );
		if ( null === $expected ) {
			$this->assertNull( $result );
		} else {
			$this->assertSame( $expected, $result->ident );
			$this->assertSame( $rest, substr( $input, $offset ) );
		}
	}

	/**
	 * Data provider.
	 *
	 * @return array
	 */
	public static function data_type_selectors(): array {
		return array(
			'any *'          => array( '* .class', '*', ' .class' ),
			'a'              => array( 'a', 'a', '' ),
			'div.class'      => array( 'div.class', 'div', '.class' ),
			'custom-type#id' => array( 'custom-type#id', 'custom-type', '#id' ),

			// invalid
			'#id'            => array( '#id' ),
			'.class'         => array( '.class' ),
			'[attr]'         => array( '[attr]' ),
		);
	}

	/**
	 * @ticket TBD
	 *
	 * @dataProvider data_attribute_selectors
	 */
	public function test_parse_attribute(
		string $input,
		?string $expected_name = null,
		?string $expected_matcher = null,
		?string $expected_value = null,
		?string $expected_modifier = null,
		?string $rest = null
	) {
		$offset = 0;
		$result = WP_CSS_Attribute_Selector::parse( $input, $offset );
		if ( null === $expected_name ) {
			$this->assertNull( $result );
		} else {
			$this->assertSame( $expected_name, $result->name );
			$this->assertSame( $expected_matcher, $result->matcher );
			$this->assertSame( $expected_value, $result->value );
			$this->assertSame( $expected_modifier, $result->modifier );
			$this->assertSame( $rest, substr( $input, $offset ) );
		}
	}

	/**
	 * Data provider.
	 *
	 * @return array
	 */
	public static function data_attribute_selectors(): array {
		return array(
			'[href]'                   => array( '[href]', 'href', null, null, null, '' ),
			'[href] type'              => array( '[href] type', 'href', null, null, null, ' type' ),
			'[href]#id'                => array( '[href]#id', 'href', null, null, null, '#id' ),
			'[href].class'             => array( '[href].class', 'href', null, null, null, '.class' ),
			'[href][href2]'            => array( '[href][href2]', 'href', null, null, null, '[href2]' ),
			'[\n href\t\r]'            => array( "[\n href\t\r]", 'href', null, null, null, '' ),
			'[href=foo]'               => array( '[href=foo]', 'href', WP_CSS_Attribute_Selector::MATCH_EXACT, 'foo', null, '' ),
			'[href \n =   bar   ]'     => array( "[href \n =   bar   ]", WP_CSS_Attribute_Selector::MATCH_EXACT, 'bar', null, '' ),
			'[href \n ^=   baz   ]'    => array( "[href \n ^=   baz   ]", WP_CSS_Attribute_Selector::MATCH_PREFIXED_BY, 'bar', null, '' ),
			'[match $= insensitive i]' => array( '[match $= insensitive i]', WP_CSS_Attribute_Selector::MATCH_SUFFIXED_BY, 'insensitive', WP_CSS_Attribute_Selector::MODIFIER_CASE_INSENSITIVE, '' ),
			'[match|=sensitive s]'     => array( '[match|=sensitive s]', WP_CSS_Attribute_Selector::MATCH_EXACT_OR_EXACT_WITH_HYPHEN, 'sensitive', WP_CSS_Attribute_Selector::MODIFIER_CASE_SENSITIVE, '' ),
			'[match="quoted[][]"]'     => array( '[match="quoted[][]"]', WP_CSS_Attribute_Selector::MATCH_EXACT_OR_EXACT_WITH_HYPHEN, 'quoted[][]', null, '' ),
			"[match='quoted!{}']"      => array( "[match='quoted!{}']", WP_CSS_Attribute_Selector::MATCH_EXACT_OR_EXACT_WITH_HYPHEN, 'quoted!{}', null, '' ),
			"[match*='quoted's]"       => array( "[match*='quoted's]", WP_CSS_Attribute_Selector::MATCH_EXACT_OR_EXACT_WITH_HYPHEN, 'quoted', WP_CSS_Attribute_Selector::MODIFIER_CASE_SENSITIVE, '' ),

			// Invalid
			'foo'                      => array( 'foo' ),
			'[foo'                     => array( '[foo' ),
			'[#foo]'                   => array( '[#foo]' ),
			'[*|*]'                    => array( '[*|*]' ),
			'[ns|*]'                   => array( '[ns|*]' ),
			'[* |att]'                 => array( '[* |att]' ),
			'[*| att]'                 => array( '[*| att]' ),
			'[att * =]'                => array( '[att * =]' ),
			'[att * =]'                => array( '[att * =]' ),
			'[att i]'                  => array( '[att i]' ),
			'[att s]'                  => array( '[att s]' ),
			'[att="val" I]'            => array( '[att="val" I]' ),
			'[att="val" S]'            => array( '[att="val" S]' ),
		);
	}
}
