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
 * @since TBD
 *
 * @access private
 *
 * @see https://www.w3.org/tr/selectors/#parse-selector
 */
class WP_CSS_Selector {
	private function __construct() {}

	/**
	 * @return static
	 */
	public static function from_selector( string $selector ) {
		$res = new static();
		return $res;
	}
}
