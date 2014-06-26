<?php
/**
 * Represents an SQL Select Statement.
 *
 * @author Pontus Persson <pom@spotify.com>
 */
namespace Asenine\Database\Query;

use Asenine\Database\QueryException;

class Select extends \Asenine\Database\Query
{
	const JOIN_OUTER = 'OUTER';
	const JOIN_INNER = 'INNER';
	const JOIN_LEFT = 'LEFT';
	const JOIN_RIGHT = 'RIGHT';

	public $distinct = false;
	public $cols = array();
	public $from = array();
	public $join = array();
	public $where = array();
	public $group = array();
	public $having = array();
	public $order = array();
	public $offset = null;
	public $limit = null;


	public function __toString()
	{
		$query = 'SELECT';

		if ($this->distinct) {
			$query .= ' DISTINCT';
		}


		if (count($this->cols)) {
			$query .= ' ' . join(', ', $this->cols);
		}
		else {
			$query .= ' *';
		}


		/* From is mandator. */
		if (count($this->from)) {
			$query .= ' FROM ' . join(' ', $this->from);
		}
		else {
			trigger_error('No FROM clause defined.', E_USER_WARNING);
			return '';
		}

		if (count($this->join)) {
			$query .= ' ' . join(' ', $this->join);
		}

		if (count($this->where)) {
			$query .= ' WHERE ' . join(' AND ', $this->where);
		}

		if (count($this->group)) {
			$query .= ' GROUP BY ' . join(', ', $this->group);
		}

		if (count($this->having)) {
			$query .= ' HAVING ' . join(' AND ', $this->having);
		}

		if (count($this->order)) {
			$query .= ' ORDER BY ' . join(', ', $this->order);
		}


		if (isset($this->limit)) {
			$query .= sprintf(' OFFSET %u LIMIT %u', $this->offset, $this->limit);
		}

		return $query;
	}


	public function cols($cols)
	{
		$this->cols = array_merge($this->cols, is_array($cols) ? $cols : func_get_args());
		return $this;
	}

	public function from($from)
	{
		$this->from[] = trim($from);
		return $this;
	}

	public function group($group)
	{
		$this->group[] = $group;
		return $this;
	}

	public function having()
	{
		$this->having[] = $this->prepare(func_get_args());
		return $this;
	}

	public function join($table, $type = self::JOIN_INNER)
	{
		$this->join[] = $type . ' JOIN ' . $table;
		return $this;
	}

	public function limit($offset, $limit)
	{
		if (!is_null($offset) || !is_null($limit)) {
			if (!is_int($offset)) {
				throw new QueryException('Offset must be integer.');
			}
			if (!is_int($limit)) {
				throw new QueryException('Limit must be integer.');
			}
		}

		$this->offset = $offset;
		$this->limit = $limit;

		return $this;
	}

	public function order($order)
	{
		if (is_null($order)) {
			$this->order = array();
		}
		else {
			$fields = func_get_args();

			$last = end($fields);

			/* If last argument is either DESC or ASC then use it as flag. */
			if (preg_match('/ASC|DESC/i', $last, $match)) {
				$scending = strtoupper($match[0]);
				array_pop($fields);
			}
			else {
				$scending = 'ASC';
			}

			foreach ($fields as $field) {
				$this->order[] =  $field . ' ' . $scending;
			}
		}

		return $this;
	}

	public function where()
	{
		$this->where[] = $this->prepare(func_get_args());
		return $this;
	}
}