<?phpphp
/**
 * Tests for DataIO class.
 *
 * @author Pontus Persson <pom@spotify.com>
 */

class DataIOTester extends \Asenine\DataIO\DB
{
	public function fetch(array $ids)
	{
		$objects = array();
		$Result = $this->DB->execute("SELECT * FROM test_table WHERE id IN %a", $ids);
		foreach ($Result as $row) {
			$objects[] = $row[0];
		}
		return $objects;
	}

	public function purge(array $ids)
	{
		$this->tableRowDeleteVerify('test_table', $ids);
	}

	public function store(array $objects)
	{
		foreach ($objects as $object) {
			$this->DB->execute("INSERT INTO test_table (id, time_created, time_modified) VALUES(%d, %d, %d)", $object, time(), time());
		}
	}
}

class DataIOTest extends PHPUnit_Framework_TestCase {

	function setUp()
	{
		$PDO = new \PDO('sqlite2::memory:');
		$PDO->exec("CREATE TABLE test_table (
			id INTEGER PRIMARY KEY,
			time_created integer NOT NULL,
			time_modified integer
		);");

		$this->DB = new \Asenine\Database\Connection($PDO);
	}

	function tearDown() { }

	function test_tableRowDeleteVerify()
	{
		$DataIO = new DataIOTester($this->DB);

		$DataIO->storeOne(1);
		$DataIO->purgeOne(1);

		try {
			$DataIO->purgeOne(1);
			$this->fail('Invalid call to purge did not except.');
		} catch (\RuntimeException $e) {}
	}
}
