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

	protected
		$csrfToken,
		$ip,
		$IPsAllowed,
		$IPsDenied,
		$policies;

	public
		$userID,
		$username,
		$isEnabled,
		$isAdministrator,
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
		if(!defined('CRYPT_BLOWFISH') || (CRYPT_BLOWFISH !== 1)) {
			throw new \Exception("bcrypt not supported in this installation. See http://php.net/crypt");
		}

		if(($saltLen = strlen($salt)) < 22) {
			throw new \Exception("Illegal salt.");
		}

		/* TODO: Set typ $2y and upgrade to PHP 5.3.7 */
		$hash = crypt($string, '$2a$10$' . $salt);

		if(strlen($hash) < $saltLen)
			throw new \Exception("Hashing failed.");

		return $hash;
	}

	/**
	 * Creates the initial, minimal viable row for the User in database
	 *
	 * @param \Asenine\User $User
	 *    User object to represent in DB
	 * @throw \Asenine\DBException
	 */
	protected static function createInDB(self $User)
	{
		$timeCreated = time();

		$query = DB::prepareQuery("INSERT INTO
			asenine_users (
				is_enabled,
				time_created,
				time_modified,
				username
			) VALUES(
				false,
				%u,
				%u,
				NULL)",
			$timeCreated,
			$timeCreated);

		if( $userID = (int)DB::queryAndGetID($query, 'asenine_users_id_seq') )
		{
			$User->userID = (int)$userID;
			$User->timeCreated = $timeCreated;
			$User->timeModified = $timeCreated;
		}
	}

	/**
	 * Loads one or more users from the database. Does not log them in.
	 *
	 * @param int|array $userIDs
	 *    User ID or array of user IDs
	 * @return mixed
	 *    Returns instance(s) of \Asenine\User
	 */
	public static function loadFromDB($userIDs)
	{
		if( !$returnArray = is_array($userIDs) )
			$userIDs = (array)$userIDs;

		/* Pre-loads an array with false values to remain sorting order from input array */
		$users = array_fill_keys($userIDs, false);

		$query = DB::prepareQuery("SELECT
				u.id AS user_id,
				u.is_enabled,
				u.is_administrator,
				u.time_auto_logout,
				u.time_created,
				u.time_modified,
				u.time_last_login,
				u.time_password_last_change,
				u.count_logins_successful,
				u.count_logins_failed,
				u.count_logins_failed_streak,
				u.username,
				u.fullname,
				u.phone,
				u.email
			FROM
				asenine_users u
			WHERE
				u.ID IN %a", $userIDs);

		$result = DB::fetch($query);

		while($user = DB::assoc($result))
		{
			$userID = (int)$user['user_id'];

			$User = new static($userID);

			$User->isEnabled = (bool)$user['is_enabled'];
			$User->isAdministrator = (bool)$user['is_administrator'];
			$User->username = $user['username'];
			$User->timeAutoLogout = (int)$user['time_auto_logout'] ?: null;
			$User->timeCreated = (int)$user['time_created'] ?: null;
			$User->timeModified = (int)$user['time_modified'] ?: null;
			$User->timeLastLogin = (int)$user['time_last_login'] ?: null;
			$User->timePasswordLastChange = (int)$user['time_password_last_change'] ?: null;

			$User->countLoginsSuccessful = (int)$user['count_logins_successful'];
			$User->countLoginsFailed = (int)$user['count_logins_failed'];
			$User->countLoginsFailedStreak = (int)$user['count_logins_failed_streak'];

			$User->fullname = $user['fullname'];
			$User->name = $User->fullname ?: $User->username;
			$User->email = $user['email'];
			$User->phone = $user['phone'];

			$users[$userID] = $User;
		}

		/* Removes any illegal ID pointer placeholders */
		$users = array_filter($users);

		return $returnArray ? $users : reset($users);
	}

	/**
	 * Fetches a user from database by username
	 *
	 * @param string $username
	 *    Username of user
	 * @return static
	 *    Instance of class
	 */
	public static function loadByUsername($username)
	{
		return static::loadFromDB(User\Dataset::getUserID($username));
	}

	/**
	 * Logs a user in with either password or token
	 *
	 * @param string $username
	 * @param string $password
	 * @param string $trialToken
	 * @throw \Asenine\UserException
	 * @return \Asenine\User
	 */
	public static function login($username, $password = null, $trialToken = null)
	{
		try
		{
			if(!strlen($username) || !strlen($password) && !strlen($trialToken))
				throw new UserException('Insufficient credentials supplied, missing username, password or token.');

			$query = DB::prepareQuery("SELECT
					id AS user_id,
					password_hash,
					password_salt,
					password_authtoken,
					time_authtoken_created
				FROM
					asenine_users
				WHERE
					is_enabled = true
					AND username = %s LIMIT 1",
				$username);

			/* Logs a user in with either password or token */
			if(!$user = DB::queryAndFetchOne($query))
				throw new UserException('Username invalid or user login not enabled.');

			list($userID, $storedHash, $passwordSalt, $storedToken, $timeToken) = array_values($user);

			$isPasswordLogin = false;

			try
			{
				if(isset($password) && strlen($password))
				{
					$isPasswordLogin = true;

					$trialHash = self::createHash($password, $passwordSalt);

					if($trialHash !== $storedHash)
						throw new UserException('Password mismatch.');
				}
				elseif(isset($trialToken) && strlen($trialToken))
				{
					if(strlen($storedToken) < 32)
						throw new UserException('Saved token invalid.');

					if(($timeToken + self::TOKEN_LIFE) < time())
						throw new UserException('Token has expired.');

					if(!Util\Token::safeCompare($storedToken, $trialToken))
						throw new UserException('Token in DB mismatches token in Cookie.');
				}
				else
				{
					throw new UserException('No valid means of authentication supplied.');
				}
			}
			catch(UserException $e)
			{
				/* If login fails, remove token and increment streaks.
					Also disables user if fail streak is too high. */
				$query = DB::prepareQuery("UPDATE
						asenine_users
					SET
						count_logins_failed = count_logins_failed + %d,
						count_logins_failed_streak = count_logins_failed_streak + %d,
						is_enabled = (count_logins_failed_streak < %u),
						password_authtoken = NULL,
						time_authtoken_created = NULL
					WHERE
						id = %u",
					$isPasswordLogin ? 1 : 0,
					$isPasswordLogin ? 1 : 0,
					self::FAIL_LOCK,
					$userID);

				DB::queryAndCountAffected($query);

				throw new UserException($e->getMessage());
			}


			/* All tests passed, proceed with setting up logged in user object */
			if(!$User = self::loadFromDB($userID))
				throw new UserException('User could not be loaded from database.');

			$User->isLoggedIn = true;

			$User->enforceSecurity();

			$User->settings = User\Manager::getSettings($User->userID);
			$User->preferences = User\Manager::getPreferences($User->userID);


			/* Create a token for autologin and deploy in cookie together with username */
			$newToken = hash_hmac('ripemd160', $User->username . microtime() . uniqid(true), 'c9q7rc98qcur9q8wytkcq09tucw89y');
			setcookie('username', $User->username, time() + 60*60*24*30, '/');
			setcookie('authtoken',	$newToken, time() + self::TOKEN_COOKIE_LIFE, '/');


			/* Update database to reflect latest login result */
			$now = time();

			$query = DB::prepareQuery("UPDATE
					asenine_users
				SET
					count_logins_successful = count_logins_successful + 1,
					count_logins_failed_streak = 0,
					time_last_login = %d,
					time_authtoken_created = %d,
					password_authtoken = %s
				WHERE
					id = %u",
				$now,
				$now,
				$newToken,
				$User->getID());

			DB::queryAndCountAffected($query);

			return $User;
		}
		catch(UserException $e)
		{
			throw new UserException(DEBUG ? $e->getMessage() : 'Login Failed.');

			return false;
		}
	}

	/* Removes a user from database */
	public static function removeFromDB(self $User)
	{
		$query = DB::prepareQuery("DELETE FROM asenine_users WHERE ID = %d", $User->userID);
		return DB::query($query);
	}


	/* Save/Updates a User in database */
	public static function saveToDB(self $User)
	{
		$timeModified = time();

		if(!isset($User->userID)) self::createInDB($User);

		$query = DB::prepareQuery("UPDATE
				asenine_users
			SET
				is_enabled = %b,
				is_administrator = %b,
				time_modified = %u,
				time_auto_logout = NULLIF(%u, 0),
				username = NULLIF(%s, ''),
				fullname = NULLIF(%s, ''),
				email = NULLIF(%s, ''),
				phone = NULLIF(%s, '')
			WHERE
				id = %u",
			$User->isEnabled,
			$User->isAdministrator,
			$timeModified,
			$User->timeAutoLogout,
			$User->username,
			$User->fullname,
			$User->email,
			$User->phone,
			$User->userID);

		DB::queryAndCountAffected($query);

		$User->timeModified = $timeModified;

		return true;
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

	public function __destruct()
	{
		if( $this->isLoggedIn() )
		{
			User\Manager::setPreferences($this->userID, $this->preferences);
			User\Manager::setSettings($this->userID, $this->settings);
		}
	}

	public function __get($key)
	{
		return $this->$key;
	}

	public function __wakeup()
	{
		if( $this->isLoggedIn() )
			$this->enforceSecurity();
	}

	/* Updates security state and takes action to log the user out if any of them match */
	public function enforceSecurity()
	{
		$this->updateSecurity();

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
		if( isset($this->settings[$key]) )
			return $this->settings[$key];

		else
			return null;
	}

	public function getStoredIP()
	{
		return $this->ip;
	}

	public function hasPolicy($policy)
	{
		return ( ($this->isAdministrator === true) || isset($this->policies[$policy]) );
	}

	/* Returns true if user has ALL of the supplied policies */
	public function hasPolicies($policies)
	{
		$policies = is_array($policies) ? $policies : func_get_args();

		foreach($policies as $policy)
			if( $this->hasPolicy($policy) === false ) return false;

		return true;
	}

	/* Returns true if user has ANY of the supplied policies */
	public function hasAnyPolicy($policies)
	{
		$policies = is_array($policies) ? $policies : func_get_args();

		foreach($policies as $policy)
			if( $this->hasPolicy($policy) === true ) return true;

		return false;
	}

	public function isAdministrator()
	{
		return ($this->isAdministrator === true);
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

	/* Sets a new password for the current user */
	public function setPassword($password)
	{
		return User\Manager::setPassword($this->userID, $password);
	}

	/* Pushes a setting to the User's settings storage */
	public function setSetting($key, $value = null)
	{
		$key = (string)$key;

		if( is_null($value) && isset($this->settings[$key]) )
			unset($this->settings[$key]);

		else
			$this->settings[$key] = $value;

		return true;
	}

	/* Refreshes security state from database */
	protected function updateSecurity()
	{
		$properties = User\Dataset::getProperties($this->userID);

		$this->isEnabled = (bool)$properties['is_enabled'];
		$this->isAdministrator = (bool)$properties['is_administrator'];

		$this->username = $properties['username'];

		$this->timeLastActivity = time();
		$this->timeKickOut = is_numeric($properties['time_auto_logout']) ? $this->timeLastActivity + $properties['time_auto_logout'] : null;

		$this->policies = User\Manager::getPolicies($this->userID);

		$ipPools = User\Manager::getIPPools($this->userID);

		$this->IPsAllowed = $ipPools['allow'];
		$this->IPsDenied = $ipPools['deny'];
	}
}