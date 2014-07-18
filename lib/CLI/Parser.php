<?php
namespace Asenine\CLI;

class Parser
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
			$l = $lines[$i];
			$lines[$i] .= str_repeat($d, $colPos - mb_strlen($l)) . $O->desc;
			if (!is_null($O->default)) {
				$lines[$i] .= ' (default: ' . $O->default . ')';
			}
		}

		$text .= join(PHP_EOL, $lines) . PHP_EOL;
		return $text;
	}

	public function parse(array $params)
	{
		foreach ($this->options as $O) {
			$O->parseArguments($params);
		}
	}
}