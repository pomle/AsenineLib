<?php
/**
 * User class.
 *
 * @author Pontus Persson <pom@spotify.com>
 */
namespace Asenine\Access;

use Asenine\Util\Token;

class User
{
	protected $isAdministrator;
	protected $policies = array();

	public $userID;
	public $username;
	public $isEnabled;
	public $isLoggedIn;
	public $timeAutoLogout;
	public $timeKickOut;
	public $timeLastActivity;
	public $fullname;
	public $email;
	public $phone;
	public $preferences;
	public $settings = array();

	/**
	 * Creates a Blowfish-compatible, random string for use as the user's unique salt
	 */
	public static function createSalt()
	{
		return Token::createToken(22);
	}

	/**
	 * Returns a hash created with Blowfish
	 *
	 * @param string $string
	 *    String to hash
	 * @param string $salt
	 *    String to use for salt
	 * @return string
	 *    Hash result
	 */
	public static function createHash($string, $salt)
	{
		if (!defined('CRYPT_BLOWFISH') || (CRYPT_BLOWFISH !== 1)) {
			throw new \RuntimeException("bcrypt not supported in this installation. See http://php.net/crypt");
		}

		$saltLen = strlen($salt);
		if ($saltLen < 22) {
			throw new \InvalidArgumentException("Illegal salt.");
		}

		/* TODO: Set typ $2y and upgrade to PHP 5.3.7 */
		$hash = crypt($string, '$2a$10$' . $salt);

		if (strlen($hash) < $saltLen) {
			throw new \RuntimeException("Hashing failed.");
		}

		return $hash;
	}


	public function __construct($userID = null)
	{
		$this->isEnabled = false;
		$this->isAdministrator = false;
		$this->isLoggedIn = false;
	}

	public function __destruct()
	{

	}


	public function addPolicy($policy)
	{
		$this->policies[$policy] = 1;
		return $this;
	}

	public function dropPolicy($policy)
	{
		if (isset($this->policies[$policy])) {
			unset($this->policies[$policy]);
		}
		return $this;
	}

	public function getID()
	{
		return $this->userID;
	}

	public function getPolicies()
	{
		return array_keys($this->policies);
	}

	public function getSetting($key)
	{
		if (isset($this->settings[$key])) {
			return $this->settings[$key];
		}
		else {
			return null;
		}
	}

	public function hasPolicy($policy)
	{
		return (true === $this->isAdministrator) || isset($this->policies[$policy]);
	}

	/* Returns true if user has ALL of the supplied policies */
	public function hasPolicies($policies)
	{
		$policies = is_array($policies) ? $policies : func_get_args();
		foreach ($policies as $policy) {
			if (true !== $this->hasPolicy($policy)) {
				return false;
			}
		}
		return true;
	}

	/* Returns true if user has ANY of the supplied policies */
	public function hasAnyPolicy($policies)
	{
		$policies = is_array($policies) ? $policies : func_get_args();
		foreach ($policies as $policy) {
			if (true === $this->hasPolicy($policy)) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Sets or returns the administrator status.
	 * If param is omitted, the current state is returned as boolean.
	 *
	 * @param bool $changeTo TRUE will set the status to true, other values will set to false.
	 * @return bool
	 * @return self
	 */
	public function isAdministrator($changeTo = null)
	{
		if (!is_null($changeTo)) {
			$this->isAdministrator = (true === $changeTo);
		}
		return (true === $this->isAdministrator);
	}

	public function isLoggedIn()
	{
		return (true === $this->isLoggedIn);
	}

	public function setPolicies(array $policies)
	{
		$this->policies = array();
		foreach ($policies as $policy) {
			$this->addPolicy($policy);
		}
	}

	/**
	 * Sets or updates a user's setting.
	 *
	 * @param string $changeTo TRUE will set the status to true, other values will set to false.
	 * @return bool
	 * @return self
	 */
	public function setSetting($key, $value = null)
	{
		if (!is_string($key)) {
			throw new \InvalidArgumentException('Key argument must be string.');
		}

		if (is_null($value) && isset($this->settings[$key])) {
			unset($this->settings[$key]);
		}
		else {
			$this->settings[$key] = $value;
		}

		return true;
	}

	public function verifyOTP($secret, $otp, $timestamp = null)
	{
		/* Populate array of timestamps to check against to protect against accidental overlap. */
		$timestamp = $timestamp ?: time();
		$timestamps = array($timestamp, $timestamp - 30, $timestamp + 30);

		$totp = new \OTPHP\TOTP($secret);
		foreach ($timestamps as $t) {
			if (true === $totp->verify($otp, $t)) {
				return true;
			}
		}

		return false;
	}

	public function verifyPassword($salt, $storedHash, $testPassword)
	{
		$calculatedHash = self::createHash($testPassword, $salt);
		return Token::safeCompare($storedHash, $calculatedHash);
	}
}