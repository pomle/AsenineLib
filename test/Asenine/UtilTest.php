<?php
/**
 * Tests for Asenine Utils
 *
 * @author Pontus Persson <pom@spotify.com>
 */

use \Asenine\Util\Token;

class UtilTest extends PHPUnit_Framework_TestCase {

	function setUp() { }

	function tearDown() { }

	function test_TokenCompare()
	{
		$testToken1 = 'FOO';
		$testToken2 = 'BAR';

		$this->assertTrue(Token::safeCompare($testToken1, $testToken1));
		$this->assertFalse(Token::safeCompare($testToken1, $testToken2));
	}
}
