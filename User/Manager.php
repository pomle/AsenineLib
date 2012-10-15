<?
namespace Asenine\User;

use \Asenine\DB;

class Manager
{
	public static function addPolicy($userID, $policyID)
	{
		$query = DB::prepareQuery("REPLACE INTO asenine_user_policies (user_id, policy_id) VALUES(%u, %u)", $userID, $policyID);
		return DB::queryAndCountAffected($query);
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
				p.ID
			FROM
				asenine_policies p
				JOIN asenine_user_policies up ON up.policy_id = p.ID
			WHERE
				up.user_id = %u
			UNION SELECT
				p.policy,
				p.ID
			FROM
				asenine_policies p
				JOIN asenine_user_group_policies ugp ON ugp.policy_id = p.ID
				JOIN asenine_user_groups ug ON ug.ID = ugp.user_group_id
				JOIN asenine_user_group_users ugu ON ugu.user_group_id = ug.ID
			WHERE
				ugu.user_id = %u
			ORDER BY
				policy ASC",
			$userID, $userID);

		$policies = DB::queryAndFetchArray($query);

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
		return $settings;
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

		/* If affected rows is zero then user ID probably did not exist, so return false */
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

		if( count($settings) > 0 )
		{
			$query = "REPLACE INTO Asenine_UserSettings (userID, name, value) VALUES";
			foreach($settings as $key => $value)
			{
				$query .= DB::prepareQuery('(%u, %s, %s),', $userID, $key, $value);
			}
			DB::queryAndCountAffected(rtrim($query, ','));
		}

		return true;
	}
}