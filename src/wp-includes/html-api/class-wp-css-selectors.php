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
	private $selectors;

	private function __construct( array $selectors ) {
		$this->selectors = $selectors;
	}

	/**
	 * Takes a CSS selectors string and returns an instance of itself or `null` if the selector
	 * is invalid or unsupported.
	 *
	 * @since TBD
	 *
	 * @param string $selectors CSS selectors string.
	 * @return self|null
	 */
	public static function from_selectors( string $selectors ): ?self {
		return self::parse( $selectors );
	}

	/**
	 * Returns a list of selectors.
	 *
	 * @since TBD
	 *
	 * @return WP_CSS_Selectors|null
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

		$length    = strlen( $input );
		$selectors = array();

		$offset = 0;

		while ( $offset < $length ) {
			$sel = WP_CSS_ID_Selector::parse( $input, $offset );
			if ( $sel ) {
				$selectors[] = $sel;
			}
		}
		if ( count( $selectors ) ) {
			return new WP_CSS_Selectors( $selectors );
		}
		return null;
	}
}

final class WP_CSS_ID_Selector extends WP_CSS_Selector_Parser {
	/** @var string */
	public $ident;

	private function __construct( string $ident ) {
		$this->ident = $ident;
	}

	public static function parse( string $input, string &$offset ): ?self {
		$ident = self::parse_hash_token( $input, $offset );
		if ( null === $ident ) {
			return null;
		}
		return new self( $ident );
	}
}

interface IWP_CSS_Selector_Parser {
	/**
	 * @return static|null
	 */
	public static function parse( string $input, string &$offset );
}

abstract class WP_CSS_Selector_Parser implements IWP_CSS_Selector_Parser {
	const UTF8_MAX_CODEPOINT_VALUE = 0x10FFFF;

	protected static function parse_whitespace( string $input, string &$offset ): bool {
		$length   = strspn( $input, " \t\r\n\f", $offset );
		$advanced = $length > 0;
		$offset  += $length;
		return $advanced;
	}

	/**
	 * Tokenization of hash tokens
	 *
	 * > U+0023 NUMBER SIGN (#)
	 * >   If the next input code point is an ident code point or the next two input code points are a valid escape, then:
	 * >     1. Create a <hash-token>.
	 * >     2. If the next 3 input code points would start an ident sequence, set the
	 * >        <hash-token>’s type flag to "id".
	 * >     3. Consume an ident sequence, and set the <hash-token>’s value to the
	 * >        returned string.
	 * >     4. Return the <hash-token>.
	 * >   Otherwise, return a <delim-token> with its value set to the current input code point.
	 *
	 * This implementation is not interested in the <delim-token>, a '#' delim token is not relevant for selectors.
	 */
	protected static function parse_hash_token( string $input, string &$offset ): ?string {
		if ( $offset + 1 >= strlen( $input ) || '#' !== $input[ $offset ] ) {
			return null;
		}

		$offset_after_hash = $offset + 1;
		if ( self::check_if_three_code_points_would_start_an_ident_sequence( $input, $offset_after_hash ) ) {
			$offset = $offset_after_hash;
			return self::parse_ident( $input, $offset );
		}
		return null;
	}

	/**
	 * Parse an ident token
	 *
	 * CAUTION: This method is _not_ for parsing and ID selector!
	 *
	 * > 4.3.11. Consume an ident sequence
	 * > This section describes how to consume an ident sequence from a stream of code points. It returns a string containing the largest name that can be formed from adjacent code points in the stream, starting from the first.
	 * >
	 * > Note: This algorithm does not do the verification of the first few code points that are necessary to ensure the returned code points would constitute an <ident-token>. If that is the intended use, ensure that the stream starts with an ident sequence before calling this algorithm.
	 * >
	 * > Let result initially be an empty string.
	 * >
	 * > Repeatedly consume the next input code point from the stream:
	 * >
	 * > ident code point
	 * >   Append the code point to result.
	 * > the stream starts with a valid escape
	 * >   Consume an escaped code point. Append the returned code point to result.
	 * > anything else
	 * >   Reconsume the current input code point. Return result.
	 *
	 * https://www.w3.org/TR/css-syntax-3/#consume-name
	 */
	protected static function parse_ident( string $input, string &$offset ): ?string {
		if ( ! self::check_if_three_code_points_would_start_an_ident_sequence( $input, $offset ) ) {
			return null;
		}

		$ident = '';

		while ( $offset < strlen( $input ) ) {
			if ( self::next_two_are_valid_escape( $input, $offset ) ) {
				$ident .= self::consume_escaped_codepoint( $input, $offset );
				continue;
			} elseif ( self::is_ident_codepoint( $input, $offset ) ) {
				// @todo this should append and advance the correct number of bytes.
				$ident  .= $input[ $offset ];
				$offset += 1;
				continue;
			}
			break;
		}

		return $ident;
	}

	/**
	 * Consume an escaped code point.
	 *
	 * > 4.3.7. Consume an escaped code point
	 * > This section describes how to consume an escaped code point. It assumes that the U+005C
	 * > REVERSE SOLIDUS (\) has already been consumed and that the next input code point has
	 * > already been verified to be part of a valid escape. It will return a code point.
	 * >
	 * > Consume the next input code point.
	 * >
	 * > hex digit
	 * >   Consume as many hex digits as possible, but no more than 5. Note that this means 1-6
	 * >   hex digits have been consumed in total. If the next input code point is whitespace,
	 * >   consume it as well. Interpret the hex digits as a hexadecimal number. If this number is
	 * >   zero, or is for a surrogate, or is greater than the maximum allowed code point, return
	 * >   U+FFFD REPLACEMENT CHARACTER (�). Otherwise, return the code point with that value.
	 * > EOF
	 * >   This is a parse error. Return U+FFFD REPLACEMENT CHARACTER (�).
	 * > anything else
	 * >   Return the current input code point.
	 */
	protected static function consume_escaped_codepoint( $input, &$offset ): ?string {
		$char = $input[ $offset ];
		if (
			( '0' <= $char && $char <= '9' ) ||
			( 'a' <= $char && $char <= 'f' ) ||
			( 'A' <= $char && $char <= 'F' )
		) {
			$hex_end_offset = $offset + 1;
			while (
				strlen( $input ) > $hex_end_offset &&
				$hex_end_offset - $offset < 6 &&
			(
			( '0' <= $char && $char <= '9' ) ||
			( 'a' <= $char && $char <= 'f' ) ||
			( 'A' <= $char && $char <= 'F' )
			)
			) {
				$hex_end_offset += 1;
			}

			$codepoint_value = hexdec( substr( $input, $offset, $hex_end_offset - $offset ) );

			// > A surrogate is a leading surrogate or a trailing surrogate.
			// > A leading surrogate is a code point that is in the range U+D800 to U+DBFF, inclusive.
			// > A trailing surrogate is a code point that is in the range U+DC00 to U+DFFF, inclusive.
			// The surrogate ranges are adjacent, so the complete range is 0xD800..=0xDFFF,
			// inclusive.
			$codepoint_char = (
				0 === $codepoint_value ||
				$codepoint_value > self::UTF8_MAX_CODEPOINT_VALUE ||
				( 0xD800 <= $codepoint_value || $codepoint_value <= 0xDFFF )
			) ?
				"\u{FFFD}" :
				mb_chr( $codepoint_value, 'UTF-8' );

			$offset = $hex_end_offset;

			// If the next input code point is whitespace, consume it as well.
			if (
				strlen( $input ) > $offset &&
				(
					"\n" === $input[ $offset ] ||
					"\t" === $input[ $offset ] ||
					' ' === $input[ $offset ]
				)
			) {
				++$offset;
			}
			return $codepoint_char;
		}

		$codepoint_char = mb_substr( $input, $offset, 1, 'UTF-8' );
		$offset        += strlen( $codepoint_char );
		return $codepoint_char;
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
	protected static function next_two_are_valid_escape( string $input, string $offset ): bool {
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
	protected static function is_ident_start_codepoint( string $input, string $offset ): bool {
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
	protected static function is_ident_codepoint( string $input, string $offset ): bool {
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
	protected static function check_if_three_code_points_would_start_an_ident_sequence( string $input, string $offset ): bool {
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
