<?php
/**
 * Tests for Asenine User.php
 *
 * @author Pontus Persson <pom@spotify.com>
 */

use Asenine\Util\Random;
use Asenine\Util\Token;

class RandomTest extends PHPUnit_Framework_TestCase {

	function testRandomString()
	{
		$length = 256;
		$chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
		$string = Random::urandomString($length, $chars);
		$this->assertEquals(256, strlen($string));
		$this->assertEquals(0, preg_match("/[^$chars]/", $string));
	}
}
