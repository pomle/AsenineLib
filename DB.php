<?
namespace Asenine;

class DBException extends \Exception
{}

class DB
{
	private static
		$connections = array(),
		$PDO = null;

	private static
		$vars,
		$varIterator,
		$lastPreparedQuery;

	public static
		$queryCount = 0,
		$lastQuery = null,
		$allQueries = array();


	public static function addPDO(\PDO $PDO, $name = null)
	{
		if( $name )
			self::$connections[$name] = $PDO;
		else
			self::$connections[] = $PDO;

		if( !self::$PDO )
			self::$PDO = $PDO;
	}


	public static function assoc($Result)
	{
		return $Result->fetch(\PDO::FETCH_ASSOC);
	}

	public static function row($Result)
	{
		return $Result->fetch(\PDO::FETCH_NUM);
	}

	public static function countRows($Result)
	{
		return $Result->countRows();
	}

	public static function fetch($query)
	{
		return self::queryAndFetchResult($query);
	}


	public static function escapeString($value)
	{
		if( !is_string($value) ) throw New DBException(sprintf('%s requires arg #1 to be string, %s given', __METHOD__, gettype($value)));
		return self::$PDO->quote($value);
	}

	public static function prepareQuery()
	{
		$vars = func_get_args();
		$query = array_shift($vars);

		if(defined('DEFAULT_COLLATION'))
		{
			$query = str_replace('||', constant('DEFAULT_COLLATION'), $query);
		}
		else
		{
			 $query = str_replace('COLLATE ||', '', $query);
		}

		self::$vars = $vars;
		self::$varIterator = 0;
		self::$lastPreparedQuery = $query;

		$query = preg_replace_callback('/%([AabduFfSst])/', array('self', 'prepareVariable'), $query);

		self::$vars = null;

		return $query;
	}

	protected static function prepareVariable($matches)
	{
		$placeholder = $matches[1];
		$i = self::$varIterator++;

		if(!array_key_exists($i, self::$vars))
			throw new DBException(sprintf('Missing argument %d for placeholder %s in query %s', $i, $matches[0], self::$lastPreparedQuery));

		$var = self::$vars[$i];

		switch($placeholder)
		{
			### Array of integers
			case 'a':
				$var = array_map('intval', (array)$var + array(0));
				return '(' . join(',', $var) . ')';

			### Array of strings
			case 'A':
				$var = array_map(array('self', 'escapeString'), $var);
				return "('" . join("','", $var) . "')";

			### Boolean
			case 'b':
				return $var ? 'true' : 'false';


			### Signed integer
			case 'd':
				return sprintf('%d', $var);

			### Unsigned integer
			case 'u':
				return sprintf('%u', $var);

			### Float
			case 'F':
			case 'f':
				return sprintf('%F', (float)$var);

			### LIKE match string
			case 'S': ### Notice that this case continues to next on purpose
				$var = '%' . str_replace('*', '%', $var) . '%';

			### String
			case 's':
				return self::escapeString((string)$var);

			case 't':
				if($var instanceof \DateTime)
					return $var->format("'Y-m-d H:i:s'");
				else
					return 'NULL';
		}

		throw new DBException(sprintf('No handler for placeholder %s in query %s', $matches[0], self::$lastPreparedQuery));
	}

	public static function queryAndCountAffected($query)
	{
		return self::query($query, true);
	}

	public static function queryAndFetchArray($query)
	{
		$Stmt = self::queryAndFetchResult($query);

		$array = array();

		$c = $Stmt->columnCount();

		while($row = $Stmt->fetch(\PDO::FETCH_ASSOC))
		{
			switch($c)
			{
				case 1:
					$array[] = current($row);
				break;

				case 2:
					list($id, $value) = array_values($row);
					$array[$id] = $value;
				break;

				default:
					list($id) = array_values($row);
					$array[(int)$id] = array_slice($row, 1);
				break;
			}
		}

		return $array;
	}

	public static function queryAndFetchOne($query)
	{
		if( !$Stmt = self::queryAndFetchResult($query) )
			return false;

		if( $Stmt->rowCount() == 0 )
			return false;

		$values = $Stmt->fetch(\PDO::FETCH_ASSOC);

		if( count($values) == 1 )
			return reset($values); ### Return first value if only one

		return $values;
	}

	public static function queryAndFetchResult($query)
	{
		return self::query($query, false);
	}

	public static function queryAndGetID($query, $ref = null)
	{
		if( $Stmt = self::queryAndFetchResult($query) )
			return self::$PDO->lastInsertId($ref);

		return false;
	}

	public static function query($query, $returnAffected = false)
	{
		self::$queryCount++;
		self::$lastQuery = $query;
		self::$allQueries[] = $query;

		if( $returnAffected )
			$Res = self::$PDO->exec($query);
		else
			$Res = self::$PDO->query($query);

		if( $Res === false )
		{
			$err = self::$PDO->errorInfo();
			throw new DBException('Query Error on "' . $query . '"; ' . $err[2]);
		}

		return $Res;
	}

	public static function transactionCommit()
	{
		return self::$PDO->commit();
	}

	public static function transactionRollback()
	{
		return self::$PDO->rollback();
	}

	public static function transactionStart()
	{
		return self::$PDO->beginTransaction();
	}
}

try
{
	DB::addPDO(new \PDO(ASENINE_PDO_DSN, ASENINE_PDO_USER, ASENINE_PDO_PASS));
}
catch(\Exception $e)
{
	die( DEBUG ? sprintf('Database Initialization Failed with DSN %s, Reason: %s', ASENINE_PDO_DSN, $e->getMessage()) : 'System Failure');
}