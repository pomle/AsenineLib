<?php
namespace Asenine\CLI\Option;

use Asenine\CLI\Option;

class Text extends Option
{
	public static $valueName = 'STRING';

	public function parse($value)
	{
		if (!is_string($value)) {
			return false;
		}
		$this->value = $value;
		return true;
	}
}