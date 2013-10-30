<?
/**
 * Wrapper for Select statements providing pagination.
 *
 * @author Pontus Persson <pom@spotify.com>
 */
namespace Asenine\Database\Util;

use Asenine\Database\Exception as DatabaseException;
use Asenine\Database\QueryException;

class PaginatedSelect implements \Iterator
{
	protected $page;
	protected $pageSize;
	protected $pageOffset;
	protected $pageLimit;

	protected $recordCount;
	protected $recordCurrent;
	protected $recordEnd;
	protected $recordStart;
	protected $recordSize;

	protected $Statement;
	protected $Result;

	protected $row;


	public function __construct(\Asenine\Database\Query\Select $Statement, $pageSize = 1000, $fetchType = \PDO::FETCH_ASSOC)
	{
		$this->fetchType = $fetchType;
		$this->pageSize = max(1, $pageSize);
		$this->recordStart = (int)$Statement->offset;
		$this->recordEnd = is_null($Statement->limit) ? null : $this->recordStart + $Statement->limit;
		$this->recordCount = $this->recordEnd - $this->recordStart;
		$this->Statement = clone $Statement;
		$this->rewind();
	}

	public function count()
	{
		return $this->recordCount;
	}

	public function current()
	{
		return $this->row;
	}

	public function fetch()
	{
		if ($this->valid()) {
			$row = $this->current();
			$this->next();
			return $row;
		}
		else {
			return false;
		}
	}

	public function key()
	{
		return $this->recordCurrent;
	}

	public function next()
	{
		++$this->recordCurrent;
	}

	public function rewind()
	{
		$this->recordCurrent = $this->recordStart;
		$this->page = 0;
		$this->pageLimit = 0;
		$this->row = null;
	}

	public function valid()
	{
		/* $this->recordEnd with null value designates an end is always reached in the resultset. */
		if (is_int($this->recordEnd) && $this->recordCurrent >= $this->recordEnd) {
			return false;
		}

		/* If we have exhausted a page, get a new result set. */
		if (0 == $this->pageLimit) {
			$this->pageOffset = $this->page * $this->pageSize + $this->recordStart;
			$this->pageLimit = is_null($this->recordEnd)
				? $this->pageSize
				: min($this->recordEnd - $this->recordCurrent, $this->pageSize);

			$this->Statement->limit($this->pageOffset, $this->pageLimit);

			$retries = 5;

			for (;;) {
				try {
					$this->Result = $this->Statement->execute();
					break;
				}
				catch (DatabaseException $e) {
					if ($retries--) {
						usleep(1000);
						continue;
					}
					throw $e;
				}
			}

			$this->page++;
		}

		$row = $this->Result->fetch($this->fetchType);

		$this->pageLimit--;

		if ($row) {
			$this->row = $row;
		}
		else {
			/* We hit end, update memory of size. */
			$this->recordEnd = $this->recordCurrent - 1;
			$this->recordCount = $this->recordEnd - $this->recordStart;
			$this->row = false;
		}

		return $this->row !== false;
	}
}