<?
/**
 * Represents an SQL Insert Statement.
 *
 * @author Pontus Persson <pom@spotify.com>
 */
namespace Asenine\Database\Query;

use Asenine\Database\QueryException;

class Insert extends \Asenine\Database\Query
{
	protected
		$table = null,
		$cols = array(),
		$values = array();


	/**
	 * Generates the string representation of this query.
	 *
	 * @return string
	 */
	public function __toString()
	{
		$query = "INSERT INTO";

		if ($this->table) {
			$query .= ' ' . $this->table;
		}
		else {
			trigger_error('No table name defined.', E_USER_WARNING);
			return '';
		}

		if (count($this->cols)) {
			$query .= ' (' . join(', ', $this->cols) . ')';
		}

		if (count($this->values)) {
			$query .= ' VALUES' . join(',', $this->values);
		}
		else {
			trigger_error('No VALUES defined.', E_USER_WARNING);
			return '';
		}

		return $query;
	}


	/**
	 * Specifies the table cols to aim at.
	 * If array is given as the first argument, that array will be used as arguments,
	 * otherwise argument list will be used.
	 *
	 * Usage example: ::cols('name', 'value');
	 *
	 * @param array $cols 	Name of the columns.
	 * @param ... strings 	Column names.
	 * @return self
	 */
	public function cols($cols)
	{
		$this->cols = is_array($cols) ? $cols : func_get_args();
		return $this;
	}

	/**
	 * Executes the query and returns the last insert ID.
	 *
	 * @param string $table 	Name of the sequence, if applicable.
	 * @return string
	 */
	public function getID($name = null)
	{
		$this->execute();
		return $this->Connection->lastInsertId($name);
	}

	/**
	 * Specifies the table name to use for query.
	 *
	 * Usage example: ::into('mytable');
	 *
	 * @param string $table 	Name of the table.
	 * @return self
	 */
	public function into($table)
	{
		$this->table = (string)$table;
		return $this;
	}

	/**
	 * Adds a well-escaped value clause to the query.
	 *
	 * Usage example: ::values('%d, %s', 1, 'Foo');
	 *
	 * @see ::prepare()
	 * @param string $query 	Query template placeholder definition.
	 * @param mixed ... 		The values to inject into $query.
	 * @return self
	 */
	public function values($query = null)
	{
		if (null === $query) {
			return $this->values;
		}
		else {
			$this->values[] = '(' . $this->prepare(func_get_args()) . ')';
			return $this;
		}
	}
}