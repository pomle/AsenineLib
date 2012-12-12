<?
/**
 * Represents an SQL Select Statement.
 *
 * @author Pontus Persson <pom@spotify.com>
 */
namespace Asenine\Database\Query;

use Asenine\Database\QueryException;

class Anonymous extends \Asenine\Database\Query
{
	protected $statement = '';


	public function __toString()
	{
		return $this->statement;
	}


	public function setQuery($statement)
	{
		$this->statement = $statement;
		return $this;
	}
}