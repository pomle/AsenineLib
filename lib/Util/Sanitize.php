<?php
/**
 * Class containing methods for sanitizing strings and throwing exceptions.
 */
namespace Asenine\Util;

class Sanitize
{
	/**
	 * Multibyte-safe lenght test of a string throwing $min_e if $string is shorter than $min
	 * and throwing $max_e if string is longer than $max;
	 *
	 * @param string $string 			The string to test.
	 * @param integer|null $min 		The minimum length of string. Restriction not enforced if omitted with null.
	 * @param integer|null $max 		The maximum length of string. Restriction not enforced if omitted with null.
	 * @param Exception $min_e 			Exception to throw if $string shorter than $min.
	 * @param Exception $max_e 			Exception to throw if $string longer than $max.
	 * @return string 					A string no longer than $max.
	 * @throws Exception
	 */
	public static function len($string, $min, $max, \Exception $min_e = null, \Exception $max_e = null)
	{
		$len = mb_strlen($string);

		if (!is_null($min) && $len < $min) {

			if ($min_e) {
				throw $min_e;
			}
		}

		if (!is_null($max) && $len > $max) {

			if ($max_e) {
				throw $max_e;
			}

			$string = mb_substr($string, 0, $max);
		}

		return $string;
	}

	/**
	 * Removes all characters from $string that is matched by regexp in $pattern,
	 * optionally throwing an exception if sanitized string mismatches input.
	 *
	 * @param string $string 		The string to test.
	 * @param string $pattern		Regexp-pattern to match.
	 * @param Exception $e 			Exception to throw if $pattern matches anything in $string.
	 * @return string 				The sanitized string.
	 * @throws Exception
	 */
	public static function regexp($string, $pattern, \Exception $e = null)
	{
		$sanitizedString = preg_replace($pattern, '', $string);

		if ($e && md5($string) !== md5($sanitizedString)) {
			throw $e;
		}

		return $sanitizedString;
	}
}