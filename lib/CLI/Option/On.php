<?php
namespace Asenine\CLI\Option;

use Asenine\CLI\Option;

class On extends Option
{
	public static $valueName = null;
	public $value = false;

	public function parse($value)
	{
		$this->value = true;
		return true;
	}
}