<?php
/**
 * Base class for DB object fetcher that all DB IO classes should extend.
 *
 * @author Pontus Persson <pom@spotify.com>
 */
namespace Asenine\DataIO;

use Asenine\Database\Connection;

interface iDB
{
	function fetch(array $pointers);
	function purge(array $pointers);
	function store(array $objects);
}

abstract class DB implements iDB
{
	protected $DB;

	/**
	 * Creates a new instance of the IO class.
	 *
	 * @param \Asenine\Database\Connection $Conn 	An instance of Database Connection to use.
	 */
	public function __construct(Connection $Connection)
	{
		$this->DB = $Connection;
	}


	/**
	 * Fetches data and prepares objects representing database data.
	 *
	 * @param array $pointers 	Keys for which rows to objectify.
	 * @return mixed
	 */
	public function fetch(array $pointers)
	{
		throw new \RuntimeException(__METHOD__ . ' is not implemented on ' . get_class($this));
	}

	/**
	 * Wrapper for multi fetcher ::fetch() that simplifies fetching of a single object.
	 * Basically only converts input to an array, and flattens returned array into a single variable.
	 *
	 * @param mixed $p 	Pointer on what to fetch, for example database row ID.
	 * @return mixed
	 * @throws \Spotify\Scatman\DataIO\Exception
	 */
	final public function fetchOne($p)
	{
		$r = $this->fetch(array($p));
		return reset($r);
	}

	/**
	 * Wrapper for multi purger ::purge() that simplifies purging of a single object.
	 * Basically only converts input to an array.
	 *
	 * @param mixed $p 	Pointer on what to purge, for example database row ID.
	 * @throws \Spotify\Scatman\DataIO\Exception
	 */
	final public function purgeOne($p)
	{
		$r = $this->purge(array($p));
	}

	/**
	 * Removes the object from database.
	 *
	 * @param array $pointers 	Keys for which rows to remove.
	 * @throws \Spotify\Scatman\DataIO\Exception
	 */
	public function purge(array $pointers)
	{
		throw new \RuntimeException(__METHOD__ . ' is not implemented on ' . get_class($this));
	}

	/**
	 * Updates the database representation of an array of objects.
	 *
	 * @param array $objects 	Array of objects to save.
	 * @throws \Spotify\Scatman\DataIO\Exception
	 */
	public function store(array $objects)
	{
		throw new \RuntimeException(__METHOD__ . ' is not implemented on ' . get_class($this));
	}

	/**
	 * Wrapper for multi storer ::store() that simplifies storing of a single object.
	 * Basically only converts input to an array.
	 *
	 * @param mixed $o 	The object to store. Must be compatible by extenders ::store() function.
	 */
	public function storeOne($o)
	{
		return $this->store(array($o));
	}
}