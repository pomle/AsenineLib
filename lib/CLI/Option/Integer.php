<?php
namespace Asenine\CLI\Option;

use Asenine\CLI\Option;

class Integer extends Option
{
	public static $valueName = 'NUM';

	public function parse($value)
	{
		if (preg_match('/[^0-9]/', $value) > 0) {
			return false;
		}
		$this->value = (int)$value;
		return true;
	}
}