<?php
namespace Asenine\CLI\Option;

use Asenine\CLI\Option;

class Help extends On
{
	public function __construct($s = 'h', $l = 'help', $d = 'no', $desc = 'Display help.')
	{
		parent::__construct($s, $l, $d, $desc);
	}
}