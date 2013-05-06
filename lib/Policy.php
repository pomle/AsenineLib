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
		$query = DB::prepareQuery("SELECT id FROM asenine_policies WHERE policy = %s", $policy);

		if ($policyID = DB::queryAndFetchOne($query)) {
			return self::loadFromDB($policyID);
		}

		return false;
	}

	public static function loadFromDB($policyIDs)
	{
		if (!$returnArray = is_array($policyIDs)) {
			$policyIDs = (array)$policyIDs;
		}

		$policies = array_fill_keys($policyIDs, null);

		$query = DB::prepareQuery("SELECT
				id,
				policy,
				description
			FROM
				asenine_policies
			WHERE
				id IN %a",
			$policyIDs);

		$result = DB::query($query);

		while ($row = DB::assoc($result)) {
			$Policy = new self($row['policy'], $row['description']);
			$Policy->policyID = (int)$row['id'];

			$policies[$Policy->policyID] = $Policy;
		}

		return $returnArray ? $policies : reset($policies);
	}

	public static function saveToDB(self $Policy)
	{
		$cleanName = self::stripIllegalChars($Policy->name);

		if ($Policy->name !== $cleanName) {
			throw new \Exception('Policy contains illegal characters.');
		}

		/* In case the exception is ever removed we want the data sanitized for the DB */
		$Policy->name = $cleanName;

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


	public function getDescription()
	{
		return $this->description;
	}

	public function getName()
	{
		return $this->name;
	}
}