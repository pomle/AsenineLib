<?
namespace Asenine;

class UserGroup
{
	public static function createInDB(self $UserGroup)
	{
		$timeCreated = time();

		$query = DB::prepareQuery("INSERT INTO asenine_user_groups (time_created) VALUES(%u)", $timeCreated);

		if( $userGroupID = (int)DB::queryAndGetID($query, 'asenine_user_groups_id_seq') )
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
				ug.id AS user_group_id,
				ug.name,
				ug.label,
				ug.description
			FROM
				asenine_user_groups ug
			WHERE
				ug.ID IN %a",
			$userGroupIDs);

		$result = DB::queryAndFetchResult($query);

		while($userGroup = DB::assoc($result))
		{
			$UserGroup = new self();

			$UserGroup->userGroupID = (int)$userGroup['user_group_id'];
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
				asenine_user_groups
			SET
				time_modified = %u,
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