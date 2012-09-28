<?
namespace Asenine\Query;

class Insert
{
	public function __construct($baseStatement)
	{
		$this->baseStatement = $baseStatement;
		$this->values = array();
		$this->update = array();
	}

	public function __toString()
	{
		$query = trim($this->baseStatement);

		$query .= ' VALUES' . join(',', $this->values);

		if(count($this->update))
		{
			$query .= ' ON DUPLICATE KEY UPDATE ';
			foreach($this->update as $key)
				$query .= sprintf('%s = VALUES(%s),', $key);

			$query = rtrim($query, ',');
		}

		return $query;
	}


	public function addValues()
	{
		$this->values[] = call_user_func_array(array('\\Asenine\\DB', 'prepareQuery'), func_get_args());
		return $this;
	}

	public function addUpdate($key)
	{
		$this->update[] = $key;
		return $this;
	}
}
