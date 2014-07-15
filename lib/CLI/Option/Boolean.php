<?php
namespace Asenine\CLI\Option;

use Asenine\CLI\Option;

class Boolean extends Option
{
	public static $valueName = '(yes|no)';

	public function parse($value)
	{
		switch ($value) {
			case '1':
			case 'y':
			case 'yes':
				$this->value = true;
				return true;
			case '0':
			case 'n':
			case 'no':
				$this->value = false;
				return true;
		}
		return false;
	}
}