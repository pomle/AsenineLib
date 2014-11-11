<?php
/**
 * File object used to simplify operations on Files in the file system.
 *
 * @author Pontus Persson <pom@spotify.com>
 */
namespace Asenine\Date;


class DateTime extends \DateTime
{
	public function __construct($time = null, $timeZone = null)
	{
		if (is_null($time)) {
			$time = date('Y-m-d\TH:i:s') . substr(microtime(), 1, 9);
		}
		if ($timeZone) {
			parent::__construct($time, $timeZone);
		}
		else {
			parent::__construct($time);
		}
	}
}
