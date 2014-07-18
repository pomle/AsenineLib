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
	public $shortOpt;
	public $longOpt;
	public $default;
	public $value;
	public $desc;
	public $flags;
	public $switches;

	public function __construct($shortOpt = null, $longOpt = null, $default = null, $desc = null)
	{
		foreach (array($shortOpt, $longOpt) as $opt) {
			if (!is_null($opt) && !is_string($opt)) {
				throw new OptionException("$opt is not a valid switch, must be string.");
			}
		}

		if ($shortOpt) {
			$this->shortOpt = $shortOpt;
			$this->flags[] = $shortOpt;
			$this->switches[] = '-' . $shortOpt;
		}
		if ($longOpt) {
			$this->longOpt = $longOpt;
			$this->flags[] = $longOpt;
			$this->switches[] = '--' . $longOpt;
		}

		$this->desc = $desc;
		$this->default = $default;
	}


	public function parseArguments(array $arguments)
	{
		$s = '-' . $this->shortOpt;
		$l = '--' . $this->longOpt;
		$f = end($this->flags);

		$sExists = in_array($s, $arguments);
		$lExists = in_array($l, $arguments);

		// Both flags present.
		if ($sExists && $lExists) {
			throw new OptionException("Conflicting flags '$s' and '$l' specified.");
		}

		// Flag is not set
		if (!$sExists && !$lExists) {
			// No default
			if (is_null($this->default)) {
				throw new OptionException("Missing required flag '$f'.");
			}
		}
		// Flag is set
		else {
			// Send in the value after the flag for parsing.
			$i = array_search($sExists ? $s : $l, $arguments);
			$v = array_key_exists($i+1, $arguments) ? $arguments[$i+1] : $this->default;

			// Next value is a flag.
			if ($v[0] == '-') {
				$v = null;
			}

			if (!$this->parse($v)) {
				throw new OptionException("Flag '$f' has wrong format.");
			}
		}
	}
}