<?php
namespace Asenine\User;

use \Asenine\DB as DB;

class Dataset
{
	public static function getAll()
	{
		$query = "SELECT id FROM asenine_users";
		$userIDs = DB::queryAndFetchArray($query);
		return self::getNames($userIDs);
	}

	public static function getEmail($userID)
	{
		$query = DB::prepareQuery("SELECT email FROM asenine_users WHERE id = %u", $userID);
		return DB::queryAndFetchOne($query);
	}

	public static function getFullname($userID)
	{
		$query = DB::prepareQuery("SELECT fullname FROM asenine_users WHERE id = %u", $userID);
		return DB::queryAndFetchOne($query);
	}

	public static function getGroups($userID)
	{
		$query = DB::prepareQuery("SELECT user_group_id FROM asenine_user_group_users ugu WHERE ugu.user_id = %u", $userID);
		return DB::queryAndFetchArray($query);
	}

	public static function getNames($userIDs)
	{
		$query = DB::prepareQuery("SELECT id, username, IFNULL(NULLIF(fullname, ''), username) AS fullname FROM asenine_users WHERE ID IN %a ORDER BY username ASC", $userIDs);
		return DB::queryAndFetchArray($query);
	}

	public static function getPasswordCrypto($userID)
	{
		$query = DB::prepareQuery("SELECT password_salt FROM asenine_users WHERE id = %u", $userID);
		$passwordCrypto = DB::queryAndFetchOne($query);
		return $passwordCrypto;
	}

	public static function getPolicies($userID)
	{
		$query = DB::prepareQuery("SELECT policy_id FROM asenine_user_policies WHERE user_id = %u", $userID);
		return DB::queryAndFetchArray($query);
	}

	public static function getProperties($userID)
	{
		$query = DB::prepareQuery("SELECT
				id AS user_id,
				is_enabled,
				is_administrator,
				time_created,
				time_modified,
				time_password_last_change,
				time_last_login,
				count_logins_successful,
				count_logins_failed,
				username,
				fullname,
				email,
				phone,
				time_auto_logout
			FROM
				asenine_users
			WHERE
				id = %u",
			$userID);

		return DB::queryAndFetchOne($query);
	}

	public static function getUserID($username)
	{
		$query = DB::prepareQuery("SELECT id FROM asenine_users WHERE username = %s LIMIT 1", $username);
		$userID = (int)DB::queryAndFetchOne($query);
		return $userID;
	}

	public static function getUsername($userID)
	{
		$query = DB::prepareQuery("SELECT username FROM asenine_users WHERE id = %u", $userID);
		return DB::queryAndFetchOne($query);
	}

	public static function isAdministrator($userID)
	{
		$query = DB::prepareQuery("SELECT is_administrator FROM asenine_users WHERE id = %u", $userID);
		$isAdministrator = (bool)DB::queryAndFetchOne($query);
		return $isAdministrator;
	}

	public static function isEnabled($userID)
	{
		$query = DB::prepareQuery("SELECT is_enabled FROM asenine_users WHERE ID = %u", $userID);
		$isEnabled = (bool)DB::queryAndFetchOne($query);
		return $isEnabled;
	}
}