<?php
/**
 * HTML API: WP_CSS_Selector class
 *
 * @package WordPress
 * @subpackage HTML-API
 * @since TBD
 */

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
 * - The following combinators:
 *   - descendant (e.g. `.parent .descendant`)
 *   - child (`.parent > .child`)
 * - Comma-separated selector lists (e.g. `.selector-1, .selector-2`)
 *
 * Unsupported selector syntax:
 * - The following combinators:
 *   - Next sibling (`.sibling + .sibling`)
 *   - Subsequent sibling (`.sibling ~ .sibling`)
 * - Pseudo-element selectors (e.g. `::before`)
 * - Pseudo-class selectors (e.g. `:hover` or `:nth-child(2)`)
 * - Namespace prefixes that need to be resolved (e.g. `svg|title` or `[xlink|href]`)
 *
 * @since TBD
 *
 * @access private
 *
 * @see https://www.w3.org/TR/css-syntax-3/#consume-a-token
 * @see https://www.w3.org/tr/selectors/#parse-selector
 *
 */
class WP_CSS_Selector {
	private function __construct() {}

	/**
	 * @return static|null
	 */
	public static function from_selector( string $selector ) {
		$res = new static();
		return $res;
	}

}
