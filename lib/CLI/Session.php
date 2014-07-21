<?php
namespace Asenine\CLI;

class Session
{
	public static $errorStream = STDERR;
	public static $outputStream = STDOUT;

	protected $Parser;
	protected $entryPoint;

	public $helpOption;
	public $printHelpOnError = true;

	public function __construct(Parser $Parser, $entryPoint)
	{
		if (!is_callable($entryPoint)) {
			throw new \InvalidArgumentException('Entrypoint not callable.');
		}

		$this->Parser = $Parser;
		$this->entryPoint = $entryPoint;
	}


	public function help(Option $Option)
	{
		$this->helpOption = $Option;
		$this->Parser->addOption($Option);
	}

	public function run()
	{
		try {
			$arguments = $this->Parser->getArguments();
			if ($this->helpOption) {
				$this->helpOption->parseArguments($arguments);
				if (true === $this->helpOption->value) {
					fwrite(self::$outputStream, $this->Parser->getHelpText());
					return 0;
				}
			}

			$this->Parser->parse();
			$entryPoint = $this->entryPoint;
			$entryPoint($this->Parser->results);
			return 0;

		} catch (OptionException $e) {
			fwrite(self::$errorStream, "Argument error: " . $e->getMessage() . PHP_EOL);
			if ($this->printHelpOnError) {
				fwrite(self::$outputStream, $this->Parser->getHelpText());
			}
		} catch (\Exception $e) {
			fwrite(self::$errorStream, "Program error: " . $e->getMessage() . PHP_EOL);
		}
		return $e->getCode() ?: 1;
	}
}