<?php
/**
 * Represents an SQL Update Statement.
 *
 * @author Pontus Persson <pom@spotify.com>
 */
namespace Asenine\Database\Query;

use Asenine\Database\QueryException;

class Update extends \Asenine\Database\Query
{
	protected
		$tables = array(),
		$set = array(),
		$where = array();


	public function __toString()
	{
		$query = "UPDATE";

		if (count($this->tables)) {
			$query .= ' ' . join(', ', $this->tables);
		}
		else {
			trigger_error('No tables defined.', E_USER_WARNING);
			return '';
		}

		if (count($this->set)) {
			$query .= ' SET ' . join(', ', $this->set);
		}
		else {
			trigger_error('No SET clause defined.', E_USER_WARNING);
			return '';
		}


		if (count($this->where)) {
			$query .= ' WHERE ' . join(' AND ', $this->where);
		}

		return $query;
	}


	public function tables($tables)
	{
		$this->tables = is_array($tables) ? $tables : func_get_args();
		return $this;
	}

	public function set()
	{
		$this->set[] = $this->prepare(func_get_args());
		return $this;
	}

	public function where()
	{
		$this->where[] = $this->prepare(func_get_args());
		return $this;
	}
}