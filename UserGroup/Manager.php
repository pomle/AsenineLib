<?
namespace Asenine\UserGroup;

use \Asenine\DB;

class Manager
{
	public static function getPolicies($userGroupID)
	{
		$query = DB::prepareQuery("WITH RECURSIVE inherited(user_group_id, depth, path, cycle) AS
				(
					SELECT
						i.user_group_id,
						1,
						ARRAY[i.user_group_id],
						false
					FROM
						asenine_user_group_inheritances i
					WHERE
						i.user_group_id = %d
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
			SELECT
				p.policy,
				p.id
			FROM
				inherited i
				JOIN asenine_user_group_policies gp ON gp.user_group_id = i.user_group_id
				JOIN asenine_policies p ON gp.policy_id = p.id",
		$userGroupID);

		$policies = DB::queryAndFetchArray($query);

		return $policies;
	}
}