<?
/**
 * Policy class
 *
 * @author Pontus Persson <pom@spotify.com>
 */
namespace Asenine;

class PolicyException extends \Exception
{}

class Policy
{
	protected
		$policyID,
		$name,
		$description;


	public static function loadByName($policy)
	{
		$query = DB::prepareQuery("SELECT ID FROM asenine_policies WHERE policy = %s", $policy);

		if($policyID = DB::queryAndFetchOne($query))
		{
			return $policyID;
		}

		return false;
	}

	public static function loadFromDB($policyIDs)
	{}

	public static function saveToDB(self $Policy)
	{
		if (!isset($Policy->policyID)) {
			$query = DB::prepareQuery("INSERT INTO asenine_policies (policy) VALUES(%s)", $Policy->name);
			$Policy->policyID = (int)DB::queryAndGetID($query, 'asenine_policies_id_seq');
		}


		$query = DB::prepareQuery("UPDATE
				asenine_policies
			SET
				policy = %s,
				description = NULLIF(%s, '')
			WHERE
				id = %d",
			$Policy->name,
			$Policy->description,
			$Policy->policyID);

		DB::query($query);
	}

	public static function stripIllegalChars($string)
	{
		return preg_replace('%[^A-Za-z]%', '', $string);
	}


	public function __construct($name = null, $description = null)
	{
		$this->name = $name;
		$this->description = $description;
	}
}