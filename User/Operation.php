<?
namespace Asenine\User;

use
	\Asenine\DB,
	\Asenine\User,
	\Asenine\UserException;

class Operation
{
	public static function setPasswordAsUser($userID, $currentPassword, $newPassword, $newPasswordVerify)
	{
		self::verifyPassword($newPassword, $newPasswordVerify);

		if(User::verifyPassword($userID, $currentPassword) !== true)
			throw new UserException(_('Current password does not match.'));

		return Manager::setPassword($userID, $newPassword);
	}

	public static function verifyPassword($newPassword, $newPasswordVerify)
	{
		if( strlen($newPassword) < User::PASSWORD_MIN_LEN)
			throw new \Exception(sprintf(_('Password invalid. Must consist of at least %d characters.'), User::PASSWORD_MIN_LEN));

		if( $newPassword !== $newPasswordVerify )
			throw new \Exception(_('New password mismatch.'));
	}

	public static function verifyUsername($username, $discountUserID = 0)
	{
		$usernameLen = mb_strlen($username);
		$minLen = User::USERNAME_MIN_LEN;
		$maxLen = User::USERNAME_MAX_LEN;

		if( (isset($minLen) && $usernameLen < $minLen) || (isset($maxLen) && $usernameLen > $maxLen) )
			throw New \Exception(sprintf(_('Username lenght invalid. Must consist of between %u and %u characters.'), $minLen, $maxLen));

		$query = DB::prepareQuery("SELECT COUNT(*) FROM asenine_users WHERE username = %s AND NOT ID = %u", $username, $discountUserID);
		if( (bool)DB::queryAndFetchOne($query) )
			throw New \Exception(sprintf(_('The username "%s" is already taken'), $username));
	}
}