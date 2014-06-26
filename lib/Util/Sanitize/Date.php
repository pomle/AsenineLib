<?php
/**
 * Class containing methods for sanitizing date strings and throwing exceptions.
 */
namespace Asenine\Util\Sanitize;

class Date
{
	public function __construct($desiredFormat = 'Y-m-d')
	{
		$this->toFormat = $desiredFormat;
	}

	public function orExcept($input)
	{
		try {
			$D = new \DateTime($input);
			return $D->format($this->toFormat);
		}
		catch (\Exception $e) {
			throw new \InvalidArgumentException('Date erroneously formatted');
		}
	}

	public function orFallback($input, $fallback = null)
	{
		try {
			return $this->orExcept($input);
		}
		catch (\Exception $e) {
			return $fallback;
		}
	}
}