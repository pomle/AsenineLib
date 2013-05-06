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
						ug.id AS user_group_id,
						1,
						ARRAY[ug.id],
						false
					FROM
						asenine_user_groups ug
						LEFT JOIN asenine_user_group_inheritances ugi ON ugi.user_group_id = ug.id
					WHERE
						ug.id = %d
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
			$userGroupID);

		$policies = DB::queryAndFetchArray($query);

		return $policies;
	}
}