<?
/**
 * User class that handles login and policies
 *
 * @author Pontus Persson <pom@spotify.com>
 */
namespace Asenine;

class UserException extends \Exception
{}


class User
{
	const TOKEN_COOKIE_LIFE = 172800; // 60*60*24*20
	const TOKEN_LIFE = 172800; // 60*60*24*20

	const USERNAME_MIN_LEN = 1;
	const USERNAME_MAX_LEN = 32;

	const PASSWORD_MIN_LEN = 6;
	const PASSWORD_MAX_AGE = null;

	const FAIL_LOCK = 10;

	private
		$isSettingsChanged = false;

	public
		$csrfToken,
		$isAdministrator,
		$ip,
		$policies;

	public
		$username,
		$isEnabled,
		$isLoggedIn,
		$timeAutoLogout,
		$timeKickOut,
		$timeLastActivity;

	public
		$fullname,
		$email,
		$phone,
		$preferences,
		$settings;

	/**
	 * Creates a Blowfish-compatible, random string for use as the user's unique salt
	 */
	public static function createSalt()
	{
		$salt = '';

		$chars = str_split('./0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz');
		$charCount = 22;

		while($charCount--)
			$salt .= $chars[array_rand($chars)];

		return $salt;
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
			throw new \Exception("bcrypt not supported in this installation. See http://php.net/crypt");
		}

		if (($saltLen = strlen($salt)) < 22) {
			throw new \Exception("Illegal salt.");
		}

		/* TODO: Set typ $2y and upgrade to PHP 5.3.7 */
		$hash = crypt($string, '$2a$10$' . $salt);

		if (strlen($hash) < $saltLen) {
			throw new \Exception("Hashing failed.");
		}

		return $hash;
	}


	public function __construct($userID = null)
	{
		$this->csrfToken = hash('sha256', 'eaks up a message into blocks of a fixed size and iterates over t' . uniqid('asenine-csrf', true));
		$this->ip = $this->getCurrentIP();

		$this->userID = (int)$userID ?: null;
		$this->isAdministrator = false;
		$this->isLoggedIn = false;

		$this->IPsAllowed = new IPPool();
		$this->IPsDenied = new IPPool();

		$this->settings = array();
		$this->preferences = array();
	}


	public function addPolicy($policy)
	{
		$this->policies[$policy] = 0;
		return $this;
	}

	/* Updates security state anset takes action to log the user out if any of them match */
	final public function enforceSecurity()
	{
		$kick = false;

		$clientIP = getenv('REMOTE_ADDR');

		### If IPs in Allow-pool, make sure we're in it
		if( count($this->IPsAllowed->ranges) )
			$kick = !$this->IPsAllowed->hasIP($clientIP);

		### If IPs in Deny-pool, override allow and kick no matter what
		if( count($this->IPsDenied->ranges) )
			$kick = $this->IPsDenied->hasIP($clientIP);


		### If user has been idle for too long, kick him
		if( $this->timeKickOut && time() >= $this->timeKickOut )
			$kick = true;

		### If user has been disabled in database, kick
		if( $this->isEnabled !== true )
			$kick = true;


		if( $kick )
			$this->isLoggedIn = false;
	}

	public function dropPolicy($policy)
	{
		if (isset($this->policies[$policy])) {
			unset($this->policies[$policy]);
		}
		return $this;
	}

	public function getCSRFToken()
	{
		return $this->csrfToken;
	}

	public function getCurrentIP()
	{
		return getenv('REMOTE_ADDR');
	}

	public function getID()
	{
		return $this->userID;
	}

	public function getPolicies()
	{
		return $this->policies;
	}

	public function getSetting($key)
	{
		if (isset($this->settings[$key])) {
			return $this->settings[$key];
		}
		return null;
	}

	public function getStoredIP()
	{
		return $this->ip;
	}

	public function hasPolicy($policy)
	{
		return (true === $this->isAdministrator()) || isset($this->policies[$policy]);
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
	 * @param bool $changeTo	TRUE will set the status to true, other values will set to false.
	 * @return bool
	 * @return self
	 */
	public function isAdministrator($changeTo = null)
	{
		if (is_null($changeTo)) {
			return (true === $this->isAdministrator);
		}
		else {
			$this->isAdministrator = (true === $changeTo);
			return $this;
		}
	}

	public function isLoggedIn()
	{
		return $this->isLoggedIn;
	}

	/* Explicitly logs the user out and destroys authorization token */
	public function logout()
	{
		if( $this->isLoggedIn !== true ) return false;

		$query = DB::prepareQuery("UPDATE
				asenine_users
			SET
				password_authtoken = NULL,
				time_authtoken_created = NULL
			WHERE
				ID = %u",
			$this->userID);

		DB::queryAndCountAffected($query);

		$this->isLoggedIn = false;

		setcookie('authtoken', '', 0, '/');

		return true;
	}


	/* Pushes a setting to the User's settings storage */
	public function setSetting($key, $value = null)
	{
		$key = (string)$key;

		if( is_null($value) && isset($this->settings[$key]) )
			unset($this->settings[$key]);

		else
			$this->settings[$key] = $value;

		$this->isSettingsChanged = true;

		return true;
	}
}