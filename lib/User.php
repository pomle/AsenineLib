<?
/**
 * User class.
 *
 * @author Pontus Persson <pom@spotify.com>
 */
namespace Asenine;

class User
{
	const USERNAME_MIN_LEN = 1;
	const USERNAME_MAX_LEN = 32;

	const PASSWORD_MIN_LEN = 6;
	const PASSWORD_MAX_AGE = null;

	private $isSettingsChanged = false;

	protected $csrfToken;
	protected $ip;
	protected $policies = array();
	public $settings = array();

	public $userID;
	public $username;
	public $isAdministrator;
	public $isEnabled;
	public $isLoggedIn;
	public $timeAutoLogout;
	public $timeKickOut;
	public $timeLastActivity;
	public $fullname;
	public $email;
	public $phone;
	public $preferences;


	/**
	 * Creates a Blowfish-compatible, random string for use as the user's unique salt
	 */
	public static function createSalt()
	{
		$salt = '';

		$chars = str_split('./0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz');
		$charCount = 22;

		while ($charCount--) {
			$salt .= $chars[array_rand($chars)];
		}

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
			throw new \RuntimeException("bcrypt not supported in this installation. See http://php.net/crypt");
		}

		if (($saltLen = strlen($salt)) < 22) {
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
		$this->csrfToken = hash('sha256', 'eaks up a message into blocks of a fixed size and iterates over t' . uniqid('asenine-csrf', true));
		$this->ip = $this->getCurrentIP();

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

	/* Updates security state anset takes action to log the user out if any of them match */
	final public function enforceSecurity()
	{
		$kick = false;

		$clientIP = getenv('REMOTE_ADDR');

		/* If user has been idle for too long, kick him. */
		if ($this->timeKickOut && time() >= $this->timeKickOut) {
			$kick = true;
		}

		/* If user has been disabled in database, kick. */
		if (true !== $this->isEnabled) {
			$kick = true;
		}

		if ($kick) {
			$this->isLoggedIn = false;
		}
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
		else {
			return null;
		}
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
	 * @param bool $changeTo TRUE will set the status to true, other values will set to false.
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

		$this->isSettingsChanged = true;

		return true;
	}
}