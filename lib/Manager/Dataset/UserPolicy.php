<?php
namespace Asenine\Manager\Dataset;

class UserPolicy
{
	public static function getAvailable()
	{
		$query = "SELECT ID, policy, description FROM Policies";
		return \DB::queryAndFetchArray($query);
	}

	public static function getDescription($policyIDs)
	{
		$query = \DB::prepareQuery("SELECT id, description FROM asenine_policies WHERE ID IN %a", $policyIDs);
		return \DB::queryAndFetchArray($query);
	}
}