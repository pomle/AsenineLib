<?php
namespace Asenine\Util;

class Random
{
	/**
	 * Generate a random number between $min and $max using /dev/urandom.
	 *
	 * This code from: http://codeascraft.com/2012/07/19/better-random-numbers-in-php-using-devurandom/
	 *
	 * @param $min int Lower range.
	 * @param $max int Uppder range.
	 * @return int Random int between $min and $max.
	 */
	public static function urandom($min = 0, $max = 0x7FFFFFFF)
	{
		$diff = $max - $min;
		if ($diff < 0 || $diff > 0x7FFFFFFF) {
			throw new \RuntimeException("Bad range");
		}

		$bytes = mcrypt_create_iv(4, MCRYPT_DEV_URANDOM);
		if ($bytes === false || strlen($bytes) != 4) {
			throw new \RuntimeException("Unable to get 4 bytes");
		}

		$ary = unpack("Nint", $bytes);
		$val = $ary['int'] & 0x7FFFFFFF; // 32-bit safe
		$fp = (float) $val / 2147483647.0; // convert to [0,1]

		return round($fp * $diff) + $min;
	}

	/**
	 * Generate a random string of $length using characters from $chars.
	 * This code from: http://codeascraft.com/2012/07/19/better-random-numbers-in-php-using-devurandom/
	 *
	 * @param $lenght Lenght of returned string.
	 * @param $chars String with set of chars to use.
	 * @return string $length long string composed of a random set of chars from $chars.
	 */
	public static function urandomString($length, $chars)
	{
		if (!is_int($length) || $length <= 0) {
			throw new \InvalidArgumentException('Argument #1 of ' . __METHOD__ . ' must be positive integer.');
		}

		if (!is_string($chars) || strlen($chars) == 0) {
			throw new \InvalidArgumentException('Argument #2 of ' . __METHOD__ . ' must be non-zero length string.');
		}

		$charIndexMax = mb_strlen($chars) - 1;
		$string = '';

		while ($length--) {
			$charIndex = self::urandom(0, $charIndexMax);
			$string .= mb_substr($chars, $charIndex, 1);
		}

		return $string;
	}
}