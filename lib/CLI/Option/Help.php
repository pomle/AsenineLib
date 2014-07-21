<?php
namespace Asenine\CLI\Option;

use Asenine\CLI\Option;

class Help extends On
{
	public function __construct($s = null, $d = false, $desc = 'Display help.')
	{
		parent::__construct($s ?: array('-h', '--help'), $d, $desc);
	}
}