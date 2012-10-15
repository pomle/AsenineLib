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
		$query = DB::prepareQuery("INSERT INTO
			asenine_policies (
				id,
				policy,
				description
			) VALUES(NULLIF(%d, 0), %s, %s)
			ON DUPLICATE KEY UPDATE
				policy = VALUES(policy),
				description = VALUES(description)",
			$Policy->policyID,
			$Policy->name,
			$Policy->description);

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