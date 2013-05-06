<?
/**
 * Represents a Connection to a Postgres database.
 *
 * @author Pontus Persson <pom@spotify.com>
 */
namespace Asenine\Database\Connection;

class SQLite extends \Asenine\Database\Connection
{
	const TYPE_TRUE = '1';
	const TYPE_FALSE = '0';
	const TYPE_TIMESTAMP = "'Y-m-d H:i:s'";

	/**
	 * SQLite does not allow multiple VALUES-clauses. This function intercept
	 * INSERT queries, separates clauses, and returns latest $Result.
	 *
	 */
	public function query($query)
	{
		if (0 === strpos($query, 'INSERT')) {

			//echo $query, "\n";

			list($a, $v) = explode('VALUES', $query);

			$chars = str_split($v);
			$buffer = '';
			$level = 0;

			foreach($chars as $c) {

				if ('(' === $c) {
					$level++;
				}

				if ($level >= 1 ) {
					$buffer .= $c;
				}

				if (')' === $c) {
					$level--;
				}


				if (0 === $level) {

					if (0 !== strlen($buffer)) {
						$query = $a . 'VALUES' . $buffer;

						//echo $query, "\n";
						$Result = parent::query($query);
					}
					$buffer = '';
				}
			}

			return $Result;
		}
		else {
			return parent::query($query);
		}
	}
}