<?php
/**
 * Represents a Connection to a Database.
 *
 * @author Pontus Persson <pom@spotify.com>
 */
namespace Asenine\Database;

use \Asenine\Util\Debug;

class Connection
{
	const TYPE_TRUE = 'true';
	const TYPE_FALSE = 'false';
	const TYPE_NULL = 'NULL';
	const TYPE_TIMESTAMP = "'Y-m-d H:i:s'";

	private $transactionLevel = 0;
	protected $PDO;
	public $echo = false;

	/**
	 * Initialises a new Connection object.
	 *
	 * @param \PDO $PDO 	A PHP Data Object instance.
	 */
	public function __construct(\PDO $PDO)
	{
		$this->PDO = $PDO;
	}

	/**
	 * Initiates a transaction on the PDO object.
	 *
	 * @return self
	 */
	public function begin()
	{
		if ($this->transactionLevel == 0) {
			$this->getPDO()->beginTransaction();
		}
		$this->transactionLevel++;
		return $this;
	}

	/**
	 * Commits an ongoing transaction.
	 *
	 * @throws \PDOException
	 * @return self
	 */
	public function commit()
	{
		$this->transactionLevel--;
		if ($this->transactionLevel == 0) {
			$this->getPDO()->commit();
		}
		return $this;
	}

	/**
	 * Creates a new instance of Delete query class and attaches this DB Connection to it.
	 * Also propagates the first argument into Delete::from() method.
	 *
	 * Example usage: self::delete('mytable')->where('id = 3')->execute();
	 *
	 * @return Query\Delete
	 */
	public function delete($from)
	{
		$Delete = new Query\Delete($this);
		$Delete->from($from);
		return $Delete;
	}

	/**
	 * Escapes a string according to the PDO object and returns the escaped string for use in a query.
	 *
	 * @param string $string 	The string to escape.
	 * @return string
	 */
	public function escape($value)
	{
		if (is_null($value)) {
			return static::TYPE_NULL;
		}

		return $this->getPDO()->quote((string)$value);
	}

	/**
	 * Creates a new instance of Any query class with this DB Connection attached to it,
	 * prepares a query and executes it, returning the PDOStatment.
	 *
	 * Example usage: self::execute("SELECT COUNT(*) FROM mytable WHERE id = %d", 123);
	 *
	 * @return \PDOStatement
	 */
	public function execute()
	{
		$Query = new Query\Any($this);
		$statement = $Query->prepare(func_get_args());
		$Query->setQuery($statement);
		return $Query->execute();
	}

	/**
	 * Creates a new instance of Insert query class and attaches this DB Connection to it,
	 * propagating first argument into the Insert::into() method.
	 *
	 * Example usage: self::insert('mytable')->cols('name')->values('%s', 'Spotify')->execute();
	 *
	 * @return Query\Insert
	 */
	public function insert($into)
	{
		$Insert = new Query\Insert($this);
		$Insert->into($into);
		return $Insert;
	}

	public function getPDO()
	{
		return $this->PDO;
	}

	/**
	 * Returns the last INSERT id.
	 *
	 * @param string $name 		Some databases has several possible values, supply name to disambiguate.
	 * @return string
	 */
	public function lastInsertId($name = null)
	{
		return $this->getPDO()->lastInsertId($name);
	}

	/**
	 * Runs a raw query thru the PDO object's query() method.
	 *
	 * @param Query $Query 	The query instance.
	 * @throws \PDOException
	 * @return \PDOStatement
	 */
	public function query($query)
	{
		if ($this->echo) {
			echo preg_replace("%\s+%", " ", $query), "\n";
		}

		$Result = $this->getPDO()->query($query);

		if (false === $Result) {
			$errors = $this->getPDO()->errorInfo();
			throw new \LogicException("Query failed: " . $errors[2] . ", " . $query);
		}

		return $Result;
	}

	/**
	 * Rollbacks a transaction on the PDO object.
	 *
	 * @throws \PDOException
	 * @return self
	 */
	public function rollback()
	{
		$this->getPDO()->rollBack();
		$this->transactionLevel = 0;
		return $this;
	}

	/**
	 * Creates a new instance of Select query class and attaches this DB Connection to it,
	 * propagating arguments into the Select::cols() method.
	 *
	 * Example usage: self::select('name', 'value')->from('mytable')->where('%id', 123)->execute();
	 *
	 * @return Query\Select
	 */
	public function select()
	{
		$Select = new Query\Select($this);
		$Select->cols(func_get_args());
		return $Select;
	}

	/**
	 * Creates a new instance of Update query class and attaches this DB Connection to it,
	 * propagating arguments into the Update::tables() method.
	 *
	 * Example usage: self::update('mytable', 'my2ndtable')->set('name = %s', 'Foo')->where('%id', 123)->execute();
	 *
	 * @return Query\Update
	 */
	public function update()
	{
		$Update = new Query\Update($this);
		$Update->tables(func_get_args());
		return $Update;
	}
}