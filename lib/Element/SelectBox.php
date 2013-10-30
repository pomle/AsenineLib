<?
namespace Asenine\Element;

class SelectBox extends Common\Root
{
	public
		$name,
		$value,
		$items;


	public static function keyValue($name, $value, Array $values)
	{
		$SelectBox = new self($name, $value);
		$SelectBox->addItemsFromArray($values, true);
		return $SelectBox;
	}

	public static function keyPair($name, $value, Array $values)
	{
		$SelectBox = new self($name, $value);
		$SelectBox->addItemsFromArray($values, false);
		return $SelectBox;
	}



	public function __construct($name = null, $value = null, Array $items = array(), $valueIsKey = false)
	{
		$this->name = $name;
		$this->value = $value;
		$this->items = $items;

		if (count($items) && $valueIsKey) {
			$this->items = array_combine($this->items, $this->items);
		}

		$this->isNoneSelectable = false;
	}

	public function __toString()
	{
		$this->addData('clear', $this->value);
		$this->addData('origin', $this->value);

		ob_start();
		?>
		<select name="<? echo htmlspecialchars($this->name); ?>"<? echo $this->getAttributes(); ?>>
			<?
			if($this->isNoneSelectable) { ?><option value=""></option><? }

			foreach($this->items as $key => $value) {
				$isSelected = (!is_null($this->value) && $this->value == $key);
				?><option value="<? echo htmlspecialchars($key); ?>" <? if($isSelected) echo 'selected="selected"'; ?>><? echo htmlspecialchars($value), '&emsp;'; ?></option><?
			}
			?>
		</select>
		<?
		return ob_get_clean();
	}


	public function addItemsFromArray(Array $array, $useValueAsKey = false)
	{
		foreach($array as $key => $value)
			$this->addItem($value, $useValueAsKey ? $value : $key);

		return $this;
	}

	public function addItemsFromArrayOfArrays(Array $arrays, $valueName, $keyName = null)
	{
		foreach($arrays as $arrayKey => $array)
			$this->addItem($array[$valueName], isset($keyName) ? $array[$keyName] : $arrayKey);

		return $this;
	}

	public function addItemsFromArrayOfObjects(Array $objects, $valueName, $keyName = null)
	{
		if( !is_string($valueName) ) trigger_error(__METHOD__ . ' requires parameter 2 to be string', E_USER_ERROR);

		foreach($objects as $objectKey => $Object)
		{
			$this->addItem
			(
				$Object->$valueName,
				isset($keyName) ? $Object->$keyName : $objectKey
			);
		}
		return $this;
	}

	public function addItem($value, $key = null)
	{
		if( is_null($key) )
			$this->items[$value] = $value;
		else
			$this->items[$key] = $value;

		return $this;
	}

	public function isNoneSelectable($state)
	{
		$this->isNoneSelectable = (bool)$state;
		return $this;
	}

	public function sort()
	{
		return $this->sortValuesAlpha();
	}

	public function sortValuesAlpha()
	{
		asort($this->items, SORT_LOCALE_STRING);
		return $this;
	}
}