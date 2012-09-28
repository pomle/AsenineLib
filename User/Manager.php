<?
namespace Asenine\User;

use \Asenine\DB;

class Manager
{
	public static function addPolicy($userID, $policyID)
	{
		$query = DB::prepareQuery("REPLACE INTO Asenine_UserPolicies (userID, policyID) VALUES(%u, %u)", $userID, $policyID);
		return DB::queryAndCountAffected($query);
	}

	public static function dropPolicy($userID, $policyID)
	{
		$query = DB::prepareQuery("DELETE FROM Asenine_UserPolicies WHERE userID = %u AND policyID = %u", $userID, $policyID);
		return DB::queryAndCountAffected($query);
	}

	public static function getCount()
	{
		return (int)DB::queryAndFetchOne("SELECT COUNT(*) FROM Asenine_Users");
	}

	public static function getIPPools($userID)
	{
		$query = DB::prepareQuery("SELECT policy, spanStart, (spanStart + spanAppend) AS spanEnd FROM Asenine_UserSecurityIPs WHERE userID = %u", $userID);
		$result = DB::queryAndFetchResult($query);

		$pools = array('allow' => new \Asenine\IPPool(), 'deny' => new \Asenine\IPPool());

		while($range = DB::assoc($result))
		{
			if( isset($pools[$range['policy']]) )
			{
				$pools[$range['policy']]->addRange(
					long2ip($range['spanStart']),
					long2ip($range['spanEnd'])
				);
			}
		}

		return $pools;
	}

	public static function getPasswordCrypto($userID)
	{
		$query = DB::prepareQuery("SELECT passwordCrypto FROM Asenine_Users WHERE ID = %u", $userID);
		$passwordCrypto = DB::queryAndFetchOne($query);
		return $passwordCrypto;
	}

	public static function getPolicies($userID)
	{
		$query = DB::prepareQuery("SELECT
				p.policy,
				p.ID
			FROM
				Asenine_Policies p
				JOIN Asenine_UserPolicies up ON up.policyID = p.ID
			WHERE
				up.userID = %u
			UNION SELECT
				p.policy,
				p.ID
			FROM
				Asenine_Policies p
				JOIN Asenine_UserGroupPolicies ugp ON ugp.policyID = p.ID
				JOIN Asenine_UserGroups ug ON ug.ID = ugp.userGroupID
				JOIN Asenine_UserGroupUsers ugu ON ugu.userGroupID = ug.ID
			WHERE
				ugu.userID = %u
			ORDER BY
				policy ASC",
			$userID, $userID);

		$policies = DB::queryAndFetchArray($query);

		return $policies;
	}

	public static function getPreferences($userID)
	{
		$query = DB::prepareQuery("SELECT preferences FROM Asenine_Users WHERE ID = %u", $userID);

		$serializedPrefs = DB::queryAndFetchOne($query);

		$preferences = unserialize($serializedPrefs);

		return is_array($preferences) ? $preferences : array();
	}

	public static function getSettings($userID)
	{
		$query = DB::prepareQuery("SELECT name, value FROM Asenine_UserSettings WHERE userID = %u", $userID);
		$settings = DB::queryAndFetchArray($query);
		return $settings;
	}

	public static function resetPreferences($userID)
	{
		$query = DB::prepareQuery("UPDATE Asenine_Users SET preferences = NULL WHERE ID = %u", $userID);
		return DB::queryAndCountAffected($query);
	}

	public static function resetSettings($userID)
	{
		$query = DB::prepareQuery("DELETE FROM Asenine_UserSettings WHERE userID = %u", $userID);
		return DB::queryAndCountAffected($query);
	}

	public static function setPassword($userID, $password)
	{
		$passwordSalt = \Asenine\User::createSalt();
		$passwordHash = \Asenine\User::createHash($password, $passwordSalt);

		$query = DB::prepareQuery("UPDATE
				Asenine_Users
			SET
				passwordSalt = %s,
				passwordHash = %s,
				timePasswordLastChange = UNIX_TIMESTAMP()
			WHERE
				ID = %d",
			$passwordSalt,
			$passwordHash,
			$userID);

		/* If affected rows is zero then user ID probably did not exist, so return false */
		$res = DB::queryAndCountAffected($query);

		return ($res > 0);
	}

	public static function setPreferences($userID, $preferences)
	{
		if( is_array($preferences) )
		{
			$query = DB::prepareQuery("UPDATE Asenine_Users SET preferences = %s WHERE ID = %u", serialize($preferences), $userID);
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