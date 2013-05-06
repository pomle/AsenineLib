<?
namespace Asenine\Util;

class Token
{
	public static function createPassword($length = 12, $chars = 'abcdefghijkmnopqrstuvwxyz023456789!"#%/()$')
	{
		if (!is_int($length) || $length < 0) {
			throw new \Exception('Argument #1 of ' . __METHOD__ . ' must be positive integer.');
		}

		if (!is_string($chars)) {
			throw new \Exception('Argument #2 of ' . __METHOD__ . ' must be string.');
		}

		$chars = str_split($chars);
		$password = '';

		while ($length--) {
			$password .= $chars[array_rand($chars)];
		}

		return $password;
	}

	public static function safeCompare($token1, $token2)
	{
		$salt = 'alx3jicalhm,qpictw9,cjom';
		$alg = 'sha256';

		$hash1 = hash($alg, $token1 . $salt);
		$hash2 = hash($alg, $token2 . $salt);

		return ($hash1 === $hash2);
	}
}