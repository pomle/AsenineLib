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

}