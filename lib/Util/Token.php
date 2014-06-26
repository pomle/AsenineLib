<?php
namespace Asenine\Util;

class Token
{
	public static function createOTPSecret($length = 12, $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567')
	{
		return Random::urandomString($length, $chars);
	}

	public static function createPassword($length = 12, $chars = 'abcdefghijkmnopqrstuvwxyz023456789!"#%/()$')
	{
		return Random::urandomString($length, $chars);
	}

	public static function createToken($length = 256, $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789')
	{
		return Random::urandomString($length, $chars);
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