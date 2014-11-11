<?php
/**
 * Tests for Date
 *
 * @author Pontus Persson <pom@spotify.com>
 */
use Asenine\Date\DateTime;

class DateTest extends PHPUnit_Framework_TestCase
{
	function testDate()
	{
		$Date = new DateTime();
		$this->assertNotEquals('000000', $Date->format('u'));
	}
}