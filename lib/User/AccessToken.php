<?
/**
 * Handles AccessTokens that lets users remember their login on selected devices.
 *
 * @author Pontus Persson <pom@spotify.com>
 */
namespace Asenine\User;

use \Asenine\User;
use \Asenine\DB;
use \Asenine\Util\Crypt;
use \Asenine\User\UserException;

class AccessToken
{
	const LIFETIME = 172800; // 60*60*24*20

	public static $encryptionKey = 'Dutoaomorrowinthmajoreved!ingtomorrowntBJGcafeeeninginthnengl,werhotwerhostisTo!wnHallscr';

	protected $index;
	protected $time;
	protected $secret;


	public static function createForUser(User $User)
	{
		$time = time();

		/* Create new token. */
		$secret = hash_hmac('sha512', $User->getUsername() . time() . uniqid(), 'iajf,aogihaow,gj,aighjao,3tj830c9,ucu09euu230,c2+c0i2039vty');

		$query = DB::prepareQuery("INSERT INTO asenine_user_accesstokens (user_id, time_created, time_expires, secret)
			VALUES(%d, %d, %d, %s)", $User->getID(), $time, $time + self::LIFETIME, $secret);

		$index = DB::queryAndGetID($query, 'asenine_user_accesstokens_id_seq');

		if (!is_numeric($index) || 0 == (int)$index) {
			throw new UserException('Remember me storage index came back 0 or not a number.');
		}

		/* Create a token for autologin and deploy in cookie. */
		return new self($index, time(), $secret);
	}

	public static function decode($cryptToken)
	{
		$cryptToken = base64_decode($cryptToken);

		$Crypt = new Crypt(md5(self::$encryptionKey));
		$rawToken = $Crypt->decrypt($cryptToken);

		$tokenParts = explode(':', $rawToken);

		if (3 !== count($tokenParts)) {
			throw new UserException('Token invalid.');
		}

		list($index, $time, $secret) = $tokenParts;

		return new self($index, $time, $secret);
	}


	public function __construct($index, $time, $secret)
	{
		$this->index = $index;
		$this->time = $time;
		$this->secret = $secret;
	}


	public function encode()
	{
		$rawToken = sprintf('%d:%d:%s', $this->index, $this->time, $this->secret);

		$Crypt = new Crypt(md5(self::$encryptionKey));
		$cryptToken = $Crypt->encrypt($rawToken);

		return base64_encode($cryptToken);
	}

	public function invalidate()
	{
		/* Invalidate index if found a match. */
		$query = DB::prepareQuery("UPDATE asenine_user_accesstokens SET
				time_utilized = %d
			WHERE
				id = %d",
			time(),
			$this->index);

		DB::queryAndCountAffected($query);
	}

	public function validate()
	{
		$nowTime = time();

		if ($this->time < $nowTime - self::LIFETIME) {
			throw new UserException('Token expired.');
		}

		$query = DB::prepareQuery("SELECT
				au.id AS user_id,
				aua.secret
			FROM
				asenine_users au
				JOIN asenine_user_accesstokens aua ON aua.user_id = au.id
			WHERE
				au.is_enabled = %b
				AND aua.time_utilized IS NULL
				AND aua.time_expires > %d
				AND aua.id = %d
			LIMIT 1",
			true,
			$nowTime,
			$this->index);

		$row = DB::queryAndFetchOne($query);

		if (!$row) {
			throw new UserException('Access token invalid or user login not enabled.');
		}

		list($userID, $dbSecret) = array_values($row);

		/* Secret must match as well. */
		if (md5($userSecret) !== md5($dbSecret)) {
			throw new UserException('Token mismatch.');
		}

		return $userID;
	}
}