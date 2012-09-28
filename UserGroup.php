<?
namespace Asenine;

class UserGroup
{
	public static function createInDB(self $UserGroup)
	{
		$timeCreated = time();

		$query = DB::prepareQuery("INSERT INTO Asenine_UserGroups (ID, timeCreated) VALUES(NULL, %u)", $timeCreated);

		if( $userGroupID = (int)DB::queryAndGetID($query) )
		{
			$UserGroup->userGroupID = (int)$userGroupID;
			$UserGroup->timeCreated = $timeCreated;
		}

		return true;
	}

	public static function loadFromDB($userGroupIDs)
	{
		if( !$returnArray = is_array($userGroupIDs) )
			$userGroupIDs = (array)$userGroupIDs;

		$userGroups = array_fill_keys($userGroupIDs, false);

		$query = DB::prepareQuery("SELECT
				ug.ID AS userGroupID,
				ug.name,
				ug.label,
				ug.description
			FROM
				Asenine_UserGroups ug
			WHERE
				ug.ID IN %a",
			$userGroupIDs);

		$result = DB::queryAndFetchResult($query);

		while($userGroup = DB::assoc($result))
		{
			$UserGroup = new self();

			$UserGroup->userGroupID = (int)$userGroup['userGroupID'];
			$UserGroup->name = $userGroup['name'];
			$UserGroup->label = $userGroup['label'] ?: null;
			$UserGroup->description = $userGroup['description'];

			$userGroups[$UserGroup->userGroupID] = $UserGroup;
		}

		$userGroups = array_filter($userGroups);

		return $returnArray ? $userGroups : reset($userGroups);
	}

	public static function saveToDB(self $UserGroup)
	{
		$timeModified = time();

		if( !isset($UserGroup->userGroupID) ) self::createInDB($UserGroup);

		$query = DB::prepareQuery("UPDATE
				Asenine_UserGroups
			SET
				timeModified = %u,
				name = NULLIF(%s, ''),
				label = NULLIF(%s, ''),
				description = NULLIF(%s, '')
			WHERE
				ID = %u",
			$timeModified,
			$UserGroup->name,
			$UserGroup->label,
			$UserGroup->description,
			$UserGroup->userGroupID);

		DB::queryAndCountAffected($query);

		$UserGroup->timeModified = $timeModified;

		return true;
	}
}