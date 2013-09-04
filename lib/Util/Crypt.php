<?
/**
 * Simple cryptography using mcrypt-* functions.
 *
 * @author Pontus Persson <pom@spotify.com>
 */
namespace Asenine\Util;

class Crypt
{
	protected $key;
	protected $algoritm;
	protected $mode;


	public function __construct($key, $algoritm = MCRYPT_RIJNDAEL_256, $mode = MCRYPT_MODE_ECB)
	{
		$this->key = (string)$key;
		$this->algoritm = $algoritm;
		$this->mode = $mode;
	}


	public function decrypt($string)
	{
		$iv_size = mcrypt_get_iv_size($this->algoritm, $this->mode);
		$iv = mcrypt_create_iv($iv_size, MCRYPT_RAND);
		return rtrim(mcrypt_decrypt($this->algoritm, $this->key, $string, $this->mode, $iv), "\0");
	}

	public function encrypt($string)
	{
		$iv_size = mcrypt_get_iv_size($this->algoritm, $this->mode);
		$iv = mcrypt_create_iv($iv_size, MCRYPT_RAND);
		return mcrypt_encrypt($this->algoritm, $this->key, $string, $this->mode, $iv);
	}
}