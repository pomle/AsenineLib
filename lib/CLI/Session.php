<?php
namespace Asenine\CLI;

class Session
{
	public $printHelpOnError = true;

	protected $Parser;
	protected $helpOption;
	protected $main;


	public function __construct(Parser $Parser, $main)
	{
		if (!is_callable($main)) {
			throw new \InvalidArgumentException('Entrypoint not callable.');
		}

		$this->Parser = $Parser;
		$this->main = $main;
	}


	public function help(Option $Option)
	{
		$this->helpOption = $Option;
		$this->Parser->addOption($Option);
	}

	public function run()
	{
		try {
			global $argv;
			if (!isset($argv) || !is_array($argv)) {
				throw new \RuntimeException('Command line arguments not available.');
			}

			if ($this->helpOption) {
				$this->helpOption->parseArguments($argv);
				if (true === $this->helpOption->value) {
					echo $this->Parser->getHelpText();
					return 0;
				}
			}

			$this->Parser->parse($argv);
			$main = $this->main;
			$main();
			return 0;

		} catch (OptionException $e) {
			echo "Argument error: ", $e->getMessage(), PHP_EOL;
			if ($this->printHelpOnError) {
				echo $this->Parser->getHelpText();
			}
			return $e->getCode();
		} catch (\Exception $e) {
			echo "Program error: ", $e->getMessage(), PHP_EOL;
			return $e->getCode();
		}
	}
}