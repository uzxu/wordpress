<?php
/**
 * HTML API: WP_CSS_Selector class
 *
 * @package WordPress
 * @subpackage HTML-API
 * @since TBD
 */

use WordPressVIPMinimum\Sniffs\Security\StaticStrreplaceSniff;

// phpcs:disable Generic.Files.OneObjectStructurePerFile.MultipleFound

/**
 * Core class used by the HTML processor to parse CSS selectors.
 *
 * This class is designed for internal use by the HTML processor.
 *
 * This class is instantiated via the `WP_CSS_Selector::from_selector( string $selector )` method.
 * It accepts a CSS selector string and returns an instance of itself or `null` if the selector
 * is invalid or unsupported.
 *
 * A subset of the CSS selector grammar is supported. The grammar is defined in the CSS Syntax
 * specification, which is available at https://www.w3.org/TR/css-syntax-3/.
 *
 * Supported selector syntax:
 * - Type selectors (tag names, e.g. `div`)
 * - Class selectors (e.g. `.class-name`)
 * - ID selectors (e.g. `#unique-id`)
 * - Attribute selectors (e.g. `[attribute-name]` or `[attribute-name="value"]`)
 * - Comma-separated selector lists (e.g. `.selector-1, .selector-2`)
 * - The following combinators:
 *   - descendant (e.g. `.parent .descendant`)
 *   - child (`.parent > .child`)
 *
 * Unsupported selector syntax:
 * - Pseudo-element selectors (e.g. `::before`)
 * - Pseudo-class selectors (e.g. `:hover` or `:nth-child(2)`)
 * - Namespace prefixes (e.g. `svg|title` or `[xlink|href]`)
 * - The following combinators:
 *   - Next sibling (`.sibling + .sibling`)
 *   - Subsequent sibling (`.sibling ~ .sibling`)
 *
 * @since TBD
 *
 * @access private
 *
 * @see https://www.w3.org/TR/css-syntax-3/#consume-a-token
 * @see https://www.w3.org/tr/selectors/#parse-selector
 * @see https://www.w3.org/TR/selectors-api2/
 * @see https://www.w3.org/TR/selectors-4/
 *
 */
class WP_CSS_Selectors {

	/**
	 * Takes a CSS selectors string and returns an instance of itself or `null` if the selector
	 * is invalid or unsupported.
	 *
	 * @since TBD
	 *
	 * @param string $selectors CSS selectors string.
	 * @return static|null
	 */
	public static function from_selectors( string $selectors ) {
		$res = new static();
		return $res;
	}

	/**
	 * Returns a list of selectors.
	 *
	 * @since TBD
	 *
	 * @return WP_CSS_Selector[]
	 */
	private static function parse( string $input ) {
		// > A selector string is a list of one or more complex selectors ([SELECTORS4], section 3.1) that may be surrounded by whitespace and matches the dom_selectors_group production.
		$input = trim( $input, " \t\r\n\r" );

		if ( '' === $input ) {
			null;
		}

		/*
		 * > The input stream consists of the filtered code points pushed into it as the input byte stream is decoded.
		 * >
		 * > To filter code points from a stream of (unfiltered) code points input:
		 * > Replace any U+000D CARRIAGE RETURN (CR) code points, U+000C FORM FEED (FF) code points, or pairs of U+000D CARRIAGE RETURN (CR) followed by U+000A LINE FEED (LF) in input by a single U+000A LINE FEED (LF) code point.
		 * > Replace any U+0000 NULL or surrogate code points in input with U+FFFD REPLACEMENT CHARACTER (�).
		 *
		 * https://www.w3.org/TR/css-syntax-3/#input-preprocessing
		 */
		$input = str_replace( array( "\r\n" ), "\n", $input );
		$input = str_replace( array( "\r", "\f" ), "\n", $input );
		$input = str_replace( "\0", "\u{FFFD}", $input );

		$at        = 0;
		$length    = strlen( $input );
		$selectors = array();

		$at = strspn( $input, "\n\t ", $at );
		while ( $at < $length ) {
		}
	}
}

interface IWP_CSS_Selector_Parser {
	public static function parse( string $input, string $offset, ?int $consumed_bytes = null ): ?self;
}

abstract class WP_CSS_Selector_Parser implements IWP_CSS_Selector_Parser {
	public static function parse_whitespace( string $input, string &$offset ): bool {
		$length   = strspn( $input, " \t\r\n\f", $offset );
		$advanced = $length > 0;
		$offset  += $length;
		return $advanced;
	}

	/*
	 * Utiltities
	 * ==========
	 *
	 * The following functions do not consume any input.
	 */

	/**
	 * > 4.3.8. Check if two code points are a valid escape
	 * > This section describes how to check if two code points are a valid escape. The algorithm described here can be called explicitly with two code points, or can be called with the input stream itself. In the latter case, the two code points in question are the current input code point and the next input code point, in that order.
	 * >
	 * > Note: This algorithm will not consume any additional code point.
	 * >
	 * > If the first code point is not U+005C REVERSE SOLIDUS (\), return false.
	 * >
	 * > Otherwise, if the second code point is a newline, return false.
	 * >
	 * > Otherwise, return true.
	 *
	 * https://www.w3.org/TR/css-syntax-3/#starts-with-a-valid-escape
	 *
	 * @todo this does not check whether the second codepoint is valid.
	 */
	public static function next_two_are_valid_escape( string $input, string $offset ): bool {
		if ( $offset + 1 >= strlen( $input ) ) {
			return false;
		}
		return '\\' === $input[ $offset ] && "\n" !== $input[ $offset + 1 ];
	}

	/**
	 * > ident-start code point
	 * >   A letter, a non-ASCII code point, or U+005F LOW LINE (_).
	 * > uppercase letter
	 * >   A code point between U+0041 LATIN CAPITAL LETTER A (A) and U+005A LATIN CAPITAL LETTER Z (Z) inclusive.
	 * > lowercase letter
	 * >   A code point between U+0061 LATIN SMALL LETTER A (a) and U+007A LATIN SMALL LETTER Z (z) inclusive.
	 * > letter
	 * >   An uppercase letter or a lowercase letter.
	 * > non-ASCII code point
	 * >   A code point with a value equal to or greater than U+0080 <control>.
	 */
	public static function is_ident_start_codepoint( string $input, string $offset ): bool {
		if ( $offset >= strlen( $input ) ) {
			return false;
		}

		return (
			'_' === $input[ $offset ] ||
			( 'a' <= $input[ $offset ] && $input[ $offset ] <= 'z' ) ||
			( 'A' <= $input[ $offset ] && $input[ $offset ] <= 'Z' ) ||
			$input[ $offset ] <= '\x7F'
		);
	}

	/**
	 * > ident code point
	 * >   An ident-start code point, a digit, or U+002D HYPHEN-MINUS (-).
	 * > digit
	 * >   A code point between U+0030 DIGIT ZERO (0) and U+0039 DIGIT NINE (9) inclusive.
	 */
	public static function is_ident_codepoint( string $input, string $offset ): bool {
		return '-' === $input[ $offset ] ||
			( '0' <= $input[ $offset ] && $input[ $offset ] <= '9' ) ||
			self::is_ident_start_codepoint( $input, $offset );
	}

	/**
	 * > 4.3.9. Check if three code points would start an ident sequence
	 * > This section describes how to check if three code points would start an ident sequence. The algorithm described here can be called explicitly with three code points, or can be called with the input stream itself. In the latter case, the three code points in question are the current input code point and the next two input code points, in that order.
	 * >
	 * > Note: This algorithm will not consume any additional code points.
	 * >
	 * > Look at the first code point:
	 * >
	 * > U+002D HYPHEN-MINUS
	 * >   If the second code point is an ident-start code point or a U+002D HYPHEN-MINUS, or the second and third code points are a valid escape, return true. Otherwise, return false.
	 * > ident-start code point
	 * >   Return true.
	 * > U+005C REVERSE SOLIDUS (\)
	 * >   If the first and second code points are a valid escape, return true. Otherwise, return false.
	 * > anything else
	 * >   Return false.
	 *
	 * https://www.w3.org/TR/css-syntax-3/#would-start-an-identifier
	 */
	public static function check_if_three_code_points_would_start_an_ident_sequence( string $input, string $offset ): bool {
		if ( $offset >= strlen( $input ) ) {
			return false;
		}

		// > U+005C REVERSE SOLIDUS (\)
		if ( '\\' === $input[ $offset ] ) {
			return self::next_two_are_valid_escape( $input, $offset );
		}

		// > U+002D HYPHEN-MINUS
		if ( '-' === $input[ $offset ] ) {
			$after_initial_hyphen_minus_offset = $offset + 1;
			if ( $offset >= strlen( $input ) ) {
				return false;
			}

			// > If the second code point is… U+002D HYPHEN-MINUS… return true
			if ( '-' === $input[ $after_initial_hyphen_minus_offset ] ) {
				return true;
			}

			// > If the second and third code points are a valid escape, return true.
			if ( self::next_two_are_valid_escape( $input, $after_initial_hyphen_minus_offset ) ) {
				return true;
			}

			// > If the second code point is an ident-start code point… return true.
			if ( self::is_ident_start_codepoint( $input, $after_initial_hyphen_minus_offset ) ) {
				return true;
			}

			// > Otherwise, return false.
			return false;
		}

		// > ident-start code point
		// >   Return true.
		// > anything else
		// >   Return false.
		return self::is_ident_start_codepoint( $input, $offset );
	}
}
