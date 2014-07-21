<?php
namespace Asenine\CLI;

class Parser
{
	public $arguments;
	public $options;
	public $results;
	public $switches;

	public function __construct($argv = null)
	{
		if (is_null($argv)) {
			global $argv;
		}
		if (!isset($argv) || !is_array($argv)) {
			throw new \RuntimeException('Command line arguments not available.');
		}
		$this->arguments = $argv;
		$this->options = array();
		$this->results = new \stdClass();
		$this->switches = array();
	}


	public function addOption(Option $Option, &$result = null)
	{
		foreach ($Option->switches as $switch) {
			if (in_array($switch, $this->switches)) {
				throw new \LogicException("Ambigious flag '$switch'.");
			}
			$this->switches[] = $switch;
		}

		// First set result to current value...
		$result = $Option->value;
		// ...then bind.
		$Option->value = &$result;
		$this->options[] = $Option;
	}

	public function getArguments()
	{
		return $this->arguments;
	}

	public function getHelpText()
	{
		$argv = $this->getArguments();
		$text = 'Usage: ' . $argv[0];

		$d = ' ';
		$lines = array();
		$lineLen = array();

		$options = $this->options;
		foreach ($options as $i => $O) {
			$arg = trim(end($O->switches) . ' ' . $O::$valueName);
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
			if ($O->desc) {
				$l = $lines[$i];
				$lines[$i] .= str_repeat($d, $colPos - mb_strlen($l)) . $O->desc;
				if (!is_null($O->default)) {
					//$lines[$i] .= ' (default: ' . $O->default . ')';
				}
			}
		}

		$text .= join(PHP_EOL, $lines) . PHP_EOL;
		return $text;
	}

	public function parse()
	{
		$this->results = new \stdClass();
		foreach ($this->options as $O) {
			$O->parseArguments($this->arguments);
			foreach ($O->switches as $s) {
				// Populate results object with values on switches as keys.
				$switch = trim($s, '--');
				$this->results->$switch = $O->value;
			}
		}
	}
}