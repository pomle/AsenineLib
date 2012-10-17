<?
namespace Asenine\Util;

class Token
{
	public static function safeCompare($token1, $token2)
	{
		$salt = 'alx3jicalhm,qpictw9,cjom';
		$alg = 'sha256';

		$hash1 = hash($alg, $token1 . $salt);
		$hash2 = hash($alg, $token2 . $salt);

		return ($hash1 === $hash2);
	}
}