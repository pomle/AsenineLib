<?php
namespace Asenine\User;

use \Asenine\DB;

class Manager
{
	const PASSWORD_RESET_TOKEN_LEN = 256;
	const OTP_SECRET_LEN = 32;

	public static function addPolicy($userID, $policyID)
	{
		$query = DB::prepareQuery("REPLACE INTO asenine_user_policies (user_id, policy_id) VALUES(%u, %u)", $userID, $policyID);
		return DB::queryAndCountAffected($query);
	}

	public static function createPasswordResetToken($userID, $expirationTime)
	{
		$passwordResetToken = \Asenine\Util\Token::createToken(self::PASSWORD_RESET_TOKEN_LEN);

		$query = DB::prepareQuery("INSERT INTO scatman_user_password_reset_tokens (user_id, time_created, time_expires, token)
			VALUES(%d, %d, %d, %s)", $userID, time(), $expirationTime, $passwordResetToken);
		DB::queryAndCountAffected($query);

		return $passwordResetToken;
	}

	public static function dropPolicy($userID, $policyID)
	{
		$query = DB::prepareQuery("DELETE FROM asenine_user_policies WHERE user_id = %u AND policy_id = %u", $userID, $policyID);
		return DB::queryAndCountAffected($query);
	}

	public static function getCount()
	{
		return (int)DB::queryAndFetchOne("SELECT COUNT(*) FROM asenine_users");
	}

	public static function getIPPools($userID)
	{
		$query = DB::prepareQuery("SELECT policy, span_start, (span_start + span_append) AS spanEnd FROM asenine_user_security_ips WHERE user_id = %u", $userID);
		$result = DB::queryAndFetchResult($query);

		$pools = array('allow' => new \Asenine\IPPool(), 'deny' => new \Asenine\IPPool());

		while($range = DB::assoc($result))
		{
			if( isset($pools[$range['policy']]) )
			{
				$pools[$range['policy']]->addRange(
					long2ip($range['span_start']),
					long2ip($range['spanEnd'])
				);
			}
		}

		return $pools;
	}

	public static function getPasswordCrypto($userID)
	{
		$query = DB::prepareQuery("SELECT passwordCrypto FROM asenine_users WHERE ID = %u", $userID);
		$passwordCrypto = DB::queryAndFetchOne($query);
		return $passwordCrypto;
	}

	public static function getPolicies($userID)
	{
		$query = DB::prepareQuery("SELECT
					p.policy,
					p.id
				FROM
					asenine_policies p
					JOIN asenine_user_policies up ON up.policy_id = p.id
				WHERE
					up.user_id = %d",
			$userID);

		$userPolicies = DB::queryAndFetchArray($query);

		$query = DB::prepareQuery("WITH RECURSIVE inherited(user_group_id, depth, path, cycle) AS
				(
					SELECT DISTINCT
						ugu.user_group_id,
						1,
						ARRAY[ugu.user_group_id],
						false
					FROM
						asenine_user_group_users ugu
						LEFT JOIN asenine_user_group_inheritances ugi ON ugi.user_group_id = ugu.user_group_id
					WHERE
						ugu.user_id = %d
				UNION
					SELECT
						g.user_group_id_inherited,
						i.depth + 1,
						path || i.user_group_id,
						g.user_group_id_inherited = ANY(path)
					FROM
						inherited i,
						asenine_user_group_inheritances g
					WHERE
						g.user_group_id = i.user_group_id
						AND NOT cycle
				)
			SELECT DISTINCT
				p.policy,
				p.id AS policy_id
			FROM
				inherited i
				JOIN asenine_user_group_policies gp ON gp.user_group_id = i.user_group_id
				JOIN asenine_policies p ON gp.policy_id = p.id
			ORDER BY
				policy ASC",
			$userID);


		$groupPolicies = DB::queryAndFetchArray($query);

		$policies = array_merge($userPolicies, $groupPolicies);

		return $policies;
	}

	public static function getPreferences($userID)
	{
		$query = DB::prepareQuery("SELECT preferences FROM asenine_users WHERE id = %u", $userID);

		$serializedPrefs = DB::queryAndFetchOne($query);

		$preferences = unserialize($serializedPrefs);

		return is_array($preferences) ? $preferences : array();
	}

	public static function getSettings($userID)
	{
		$query = DB::prepareQuery("SELECT name, value FROM asenine_user_settings WHERE user_id = %u", $userID);
		$settings = DB::queryAndFetchArray($query);
		foreach ($settings as $key => &$value) {
			$value = json_decode($value);
		}
		return $settings;
	}

	public static function resetCredentials($userID)
	{
		$query = DB::prepareQuery("UPDATE asenine_users
			SET password_hash = NULL, otp_secret = NULL
			WHERE id = %d", $userID);
		return DB::queryAndCountAffected($query);
	}

	public static function resetPreferences($userID)
	{
		$query = DB::prepareQuery("UPDATE asenine_users SET preferences = NULL WHERE ID = %u", $userID);
		return DB::queryAndCountAffected($query);
	}

	public static function resetSettings($userID)
	{
		$query = DB::prepareQuery("DELETE FROM asenine_user_settings WHERE user_id = %u", $userID);
		return DB::queryAndCountAffected($query);
	}

	public static function setPassword($userID, $password)
	{
		$time = time();

		$passwordSalt = \Asenine\User::createSalt();
		$passwordHash = \Asenine\User::createHash($password, $passwordSalt);
		$query = DB::prepareQuery("UPDATE
				asenine_users
			SET
				password_salt = %s,
				password_hash = %s,
				time_password_last_change = %d
			WHERE
				ID = %d",
			$passwordSalt,
			$passwordHash,
			$time,
			$userID);

		/* If affected rows is zero then user ID did not exist, so return false */
		$res = DB::queryAndCountAffected($query);

		return ($res > 0);
	}

	public static function setPreferences($userID, $preferences)
	{
		if( is_array($preferences) )
		{
			$query = DB::prepareQuery("UPDATE asenine_users SET preferences = %s WHERE ID = %u", serialize($preferences), $userID);
			return DB::queryAndCountAffected($query);
		}
		return false;
	}

	public static function setSettings($userID, array $settings)
	{
		self::resetSettings($userID);

		if (count($settings) > 0) {
			$query = "INSERT INTO asenine_user_settings (user_id, name, value) VALUES";
			foreach ($settings as $key => $value) {
				$query .= DB::prepareQuery('(%u, %s, %s),', $userID, $key, json_encode($value));
			}
			DB::queryAndCountAffected(rtrim($query, ','));
		}

		return true;
	}
}