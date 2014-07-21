<?php
namespace Asenine\CLI;

interface OptionInterface
{
	public function parseArguments(array $arguments);
	public function parse($value);
}

class OptionException extends \InvalidArgumentException
{}

abstract class Option implements OptionInterface
{
	public $switches;
	public $default;
	public $value;
	public $desc;


	public function __construct(array $switches, $default = null, $desc = null)
	{
		foreach ($switches as $switch) {
			if (!is_null($switch) && !is_string($switch)) {
				throw new OptionException("$switch is not a valid switch, must be string.");
			}
		}

		$this->switches = $switches;
		$this->value = $this->default = $default;
		$this->desc = $desc;
	}


	public function parseArguments(array $arguments)
	{
		$switch = null;
		foreach ($this->switches as $testSwitch) {
			if (in_array($testSwitch, $arguments)) {
				if ($switch) {
					throw new OptionException("Conflicting switches '$switch' and '$testSwitch' specified.");
				}
				$switch = $testSwitch;
			}
		}
		if (is_null($switch)) {
			if (is_null($this->default)) {
				throw new OptionException(sprintf("Missing required flag '%s'.", join('/', $this->switches)));
			}
		}
		else {
			$i = array_search($switch, $arguments);
			$v = array_key_exists($i+1, $arguments) ? $arguments[$i+1] : null;;

			// Next value is a flag.
			if ($v[0] == '-') {
				$v = null;
			}

			if (!$this->parse($v)) {
				throw new OptionException("Flag '$switch' has wrong format.");
			}
		}
	}
}