<?php
/**
 * Represents an SQL Query and a base class for all query classes.
 *
 * @author Pontus Persson <pom@spotify.com>
 */
namespace Asenine\Database;

class QueryException extends \Exception
{}

class Query
{
	protected $Connection;

	public function __construct(Connection $Connection)
	{
		$this->Connection = $Connection;
	}

	/**
	 * Executes this object as a query.
	 *
	 * @return \PDOStatement
	 */
	public function execute()
	{
		$statement = (string)$this;
		return $this->Connection->query($statement);
	}

	/**
	 * Prepares a string for use as a query using printf-like placeholders.
	 *
	 * If first argument is an array, that array will be used as arguments, otherwise argument list will be used.
	 *
	 * Usage example:
	 * 		::prepare("SELECT * FROM
	 *				mytable
	 *			WHERE
	 *				id = %d
	 *				AND is_enabled = %b
	 *				AND name = %s
	 *				AND date = %t",
	 *			123, true, 'Foo', new DateTime());
	 *
	 * @param array $params
	 * @param ... First argument is the query with placeholders while following arguments are values for placeholders.
	 * @return string
	 */
	public function prepare($params)
	{
		/* Handle both arrays and argument lists. */
		$params = is_array($params) ? $params : func_get_args();

		/* If there are no arguments following the first, we don't have to handle anything. */
		if (1 == count($params)) {
			return $params[0];
		}

		/* Shift of first argument and treat as the query template. */
		$query = array_shift($params);

		$Connection = $this->Connection;

		/* Define the closure that acts as the callback to preg_replace_callback. */
		$injector = function($matches) use($query, $params, $Connection) {
			static $index;

			if (!isset($index)) {
				$index = 0;
			}

			$flag = $matches[1];

			if (!array_key_exists($index, $params)) {
				throw new \InvalidArgumentException(sprintf('Missing argument %d for placeholder "%s" in query %s', $index + 1, $matches[0], $query));
			}

			$param = $params[$index++];

			switch($flag) {
				/* Array of strings. */
				case 'a':
				case 'A':
					if (!is_array($param) && !$param instanceof \Traversable) {
						throw new \InvalidArgumentException("Paramter for %$flag must be traversable");
					}
					foreach ($param as $item) {
						$value = is_array($item) ? $item[0] : $item;
						$values[] = ($flag === 'A') ? $Connection->escape($value) : (int)$value;
					}
					if (!isset($values)) {
						throw new \UnexpectedValueException("Traversable did not yield");
					}
					return '(' . join(',', $values) . ')';
					break;

				/* Boolean. */
				case 'b':
					return $param
						? $Connection::TYPE_TRUE
						: $Connection::TYPE_FALSE;
					break;

				/* Signed integer. */
				case 'd':
					return sprintf('%d', $param);
					break;

				/* Float. */
				case 'F':
				case 'f':
					return sprintf('%F', (float)$param);
					break;

				/* Unsigned integer. */
				case 'u':
					if ($param instanceof \DateTime) {
						return $param->format('U');
					}
					return sprintf('%u', $param);
					break;

				/* LIKE match string.
						Notice that this case continues to next on purpose. */
				case 'S':
					$param = '%' . $param . '%';

				/* String. */
				case 's':
					return $Connection->escape($param);
					break;

				case 't':
					if ($param instanceof \DateTime) {
						return $param->format($Connection::TYPE_TIMESTAMP);
					}
					else {
						return $Connection::TYPE_NULL;
					}
					break;
			}

			throw new \InvalidArgumentException(sprintf('No handler for placeholder "%s" in query "%s"', $matches[0], $query));
		};

		$query = preg_replace_callback('/%([AabduFfSst])/', $injector, $query);

		return $query;
	}
}