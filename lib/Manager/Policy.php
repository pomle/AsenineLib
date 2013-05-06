<?
namespace Asenine\Manager;

class Policy extends Common\DB
{
	public static function loadFromDB($policyIDs)
	{
		$policies = array();

		$query = \DB::prepareQuery("SELECT
				p.id AS policy_id,
				p.policy,
				p.description
			FROM
				Policies p
			WHERE
				p.ID IN %a", $policyIDs);

		$result = \DB::queryAndFetchResult($query);

		while($policy = \DB::assoc($result))
		{
			$Policy = new \stdClass();

			$Policy->policyID = (int)$policy['policy_id'];
			$Policy->policy = $policy['policy'];
			$Policy->name = $Policy->policy;
			$Policy->description = $policy['description'];

			$policies[$Policy->policyID] = $Policy;
		}

		return $policies;
	}
}