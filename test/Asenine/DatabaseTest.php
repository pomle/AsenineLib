<?php
/**
 * Tests for Database
 *
 * @author Pontus Persson <pom@spotify.com>
 */
use Asenine\Database\Connection;
use Asenine\Database\Query;

class DatabaseTest extends PHPUnit_Framework_TestCase {

	function getPDO()
	{
		$PDO = new \PDO('sqlite::memory:');
		$PDO->exec(file_get_contents(__DIR__ . '/../resource/files/Database.sql'));
		return $PDO;
	}

	function testConnection()
	{
		$PDO = $this->getPDO();
		$SDB = new Connection\SQLite($PDO);
		$PDB = new Connection\Postgres($PDO);

		$unixtime = 1405432119;
		$DateTime = DateTime::createFromFormat('U', $unixtime);
		$badString = 'Escap\'e "this"';

		$sqliteQuery = new Query\Any($SDB);
		$postgresQuery = new Query\Any($PDB);

		$sql = $sqliteQuery->prepare("INSERT INTO test (id, time_unix, time_stamp, title, complete, price) VALUES(NULL, %u, %t, %s, %b, %f)",
			$unixtime, $DateTime, $badString, true, 123.129);
		$this->assertEquals('INSERT INTO test (id, time_unix, time_stamp, title, complete, price) VALUES(NULL, 1405432119, \'2014-07-15 13:48:39\', \'Escap\'\'e "this"\', 1, 123.129000)', $sql);

		$sql = $postgresQuery->prepare("INSERT INTO test (id, time_unix, time_stamp, title, complete, price) VALUES(NULL, %u, %t, %s, %b, %f)",
			$unixtime, $DateTime, $badString, true, 123.129);
		$this->assertEquals('INSERT INTO test (id, time_unix, time_stamp, title, complete, price) VALUES(NULL, 1405432119, \'2014-07-15 13:48:39\', \'Escap\'\'e "this"\', true, 123.129000)', $sql);


		$sql = $sqliteQuery->prepare("SELECT * FROM test WHERE id IN %a", array(1,2,3));
		$this->assertEquals('SELECT * FROM test WHERE id IN (1,2,3)', $sql);

		$sql = $sqliteQuery->prepare("SELECT * FROM test WHERE id IN %A", array('bu\'nch', 'o"f', "susp'''icious", 'string\\\\\\\'"s'));
		$this->assertEquals('SELECT * FROM test WHERE id IN (\'bu\'\'nch\',\'o"f\',\'susp\'\'\'\'\'\'icious\',\'string\\\\\\\'\'"s\')', $sql);


		for ($i = 0; $i < 4; $i++) {
			$sql = $sqliteQuery->prepare("INSERT INTO test (id, time_unix, time_stamp, title, complete, price) VALUES(NULL, %u, %t, %s, %b, %f)",
				$unixtime, $DateTime, $badString . $i, true, 123.129);
			$SDB->execute($sql);
		}

		$Result = $SDB->execute("SELECT id FROM test");
		$sql = $sqliteQuery->prepare("SELECT * FROM test WHERE id IN %a", $Result);
		$this->assertEquals('SELECT * FROM test WHERE id IN (1,2,3,4)', $sql);

		$Result = $SDB->execute("SELECT title FROM test");
		$sql = $sqliteQuery->prepare("SELECT * FROM test WHERE id IN %a", $Result);
		$this->assertEquals('SELECT * FROM test WHERE id IN (0,0,0,0)', $sql);

		$Result = $SDB->execute("SELECT title FROM test");
		$sql = $sqliteQuery->prepare("SELECT * FROM test WHERE id IN %A", $Result);
		$this->assertEquals('SELECT * FROM test WHERE id IN (\'Escap\'\'e "this"0\',\'Escap\'\'e "this"1\',\'Escap\'\'e "this"2\',\'Escap\'\'e "this"3\')', $sql);

		/*
		If the traversable sent as argument for %a does not yield any results we add a zero int.
		This is for BC.
		TODO: Throw exception if not yielding.
		try { $e = null; $sqliteQuery->prepare("SELECT * FROM test WHERE id IN %a", $Result);
		} catch (\Exception $e) {} $this->assertInstanceOf('\UnexpectedValueException', $e);
		*/
		$sql = $sqliteQuery->prepare("SELECT * FROM test WHERE id IN %a", $Result);
		$this->assertEquals('SELECT * FROM test WHERE id IN (0)', $sql);

		try { $e = null; $sqliteQuery->prepare("SELECT * FROM test WHERE id IN %a", 'haaaalo');
		} catch (\Exception $e) {} $this->assertInstanceOf('\InvalidArgumentException', $e);
	}

	function testSelectStatement()
	{
		$PDO = $this->getPDO();
		$DB = new Connection\SQLite($PDO);

		// Test magic join detection.
		$Select = $DB->select('a, b, c, d, e')
			->from('a_table a')
			->join('b_table b ON b.value = %d', 1, 'LEFT')
			->join('c_table c ON c.value = %d', 2, 'RIGHT')
			->join('d_table d ON d.value = %d', 3, 'INNER')
			->join('e_table e ON e.value = %d', 4, 'OUTER');

		$this->assertEquals('SELECT a, b, c, d, e FROM a_table a LEFT JOIN b_table b ON b.value = 1 RIGHT JOIN c_table c ON c.value = 2 INNER JOIN d_table d ON d.value = 3 OUTER JOIN e_table e ON e.value = 4', (string)$Select);

		// Test parameterization.
		$Select = $DB->select('(a > %d) AS a_is_more', 10)
			->from('a_table AS a')
			->join('b_table AS b ON a.id = b.id AND a.size > %d', 20)
			->where('b.name = %s', 'foobar')
			->where('b.time > %t', new \DateTime('2014-01-01'))
			->group('a.id')
			->having('SUM(a.value) > %f', .5)
			->limit(5, 10);

		$this->assertEquals("SELECT (a > 10) AS a_is_more FROM a_table AS a INNER JOIN b_table AS b ON a.id = b.id AND a.size > 20 WHERE b.name = 'foobar' AND b.time > '2014-01-01 00:00:00' GROUP BY a.id HAVING SUM(a.value) > 0.500000 OFFSET 5 LIMIT 10", (string)$Select);
	}
}