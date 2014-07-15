<?php
namespace Asenine\CLI;

class Helper
{
	public $options = array();
	public $flags = array();

	public function addOption(Option $Option, &$result = null)
	{
		foreach ($Option->flags as $f) {
			if (in_array($f, $this->flags)) {
				throw new \LogicException("Ambigious flag '$f'.");
			}
			$this->flags[] = $f;
		}

		// First set result to current value...
		$result = $Option->value;
		// ...then bind.
		$Option->value = &$result;
		$this->options[] = $Option;
	}

	public function getHelpText()
	{
		global $argv;
		$text = 'Usage: ' . $argv[0];

		$d = ' ';
		$lines = array();
		$lineLen = array();

		$options = $this->options;
		foreach ($options as $i => $O) {
			$arg = end($O->switches) . ' ' . $O::$valueName;
			if (!is_null($O->default)) {
				$arg = '[' . $arg . ']';
			}
			$text .= ' ' . $arg;

			$lines[$i] = str_repeat($d, 2) . join('/', $O->switches);
			$lineLen[] = mb_strlen($lines[$i]);
		}
		$text .= PHP_EOL;

		$colPos = max($lineLen) + 4;
		$lineLen = array();
		foreach ($this->options as $i => $O) {
			$l = $lines[$i];
			$lines[$i] .= str_repeat($d, $colPos - mb_strlen($l)) . $O->desc;
			if (!is_null($O->default)) {
				$lines[$i] .= ' (default: ' . $O->default . ')';
			}
		}

		$text .= join(PHP_EOL, $lines) . PHP_EOL;
		return $text;
	}

	public function execute()
	{
		try {
			$this->parse();
		} catch (\InvalidArgumentException $e) {
			echo $e->getMessage() . PHP_EOL;
		}
	}

	public function parse()
	{
		$s = array();
		$l = array();
		foreach ($this->options as $i => $O) {
			if ($O->shortOpt) {
				$s[] = $O->shortOpt . ':';
			}
			if ($O->longOpt) {
				$l[] = $O->longOpt . ':';
			}
		}

		$parsed = getopt(join('', $s), $l);

		foreach ($this->options as $O) {
			$flag = end($O->flags);
			$s = $O->shortOpt;
			$l = $O->longOpt;
			$d = $O->default;
			if (isset($parsed[$s], $parsed[$l])) {
				throw new \InvalidArgumentException("Both '$s' and '$l' specified.");
			}
			if (!isset($parsed[$s]) && !isset($parsed[$l])) {
				if (is_null($d)) {
					throw new \InvalidArgumentException("Missing required argument '$flag'.");
				}
			}
			else {
				if (!$O->parse(isset($parsed[$s]) ? $parsed[$s] : $parsed[$l])) {
					throw new \InvalidArgumentException("Argument '$flag' has wrong format.");
				}
			}
		}
	}
}

interface OptionInterface
{
	public function parse($value);
}

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
				throw new \InvalidArgumentException("$opt is not a valid switch, must be string.");
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
		$this->parse($this->default);
	}
}