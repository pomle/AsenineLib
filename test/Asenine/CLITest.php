<?php
/**
 * Tests for Asenine CLI
 *
 * @author Pontus Persson <pom@spotify.com>
 */

use Asenine\CLI;

class CLITest extends PHPUnit_Framework_TestCase {

	function setUp() { }
	function tearDown() { }

	function assertSessionMessageFail($message, CLI\Session $S)
	{
		$stream = fopen('php://memory', 'rw');
		$S::$errorStream = $stream;
		$S::$outputStream = $stream;
		$r = $S->run();
		rewind($stream);
		$o = fread($stream, 1024*64);
		fclose($stream);
		$this->assertTrue($r > 0, 'Session return code');
		$this->assertMessage($message, $o);
	}

	function assertMessage($message, $string)
	{
		$this->assertEquals(file_get_contents(DIR_TEST_TARGETS . "CLITest-$message.txt"), $string,
			"Divergent message results from '$message'.");
	}

	function testOptions()
	{
		$B = new CLI\Option\Boolean(array());
		$this->assertNull($B->default);
		$this->assertNull($B->value);
		$this->assertTrue($B->parse('0'));
		$this->assertFalse($B->value);
		$this->assertTrue($B->parse('n'));
		$this->assertFalse($B->value);
		$this->assertTrue($B->parse('no'));
		$this->assertFalse($B->value);
		$this->assertTrue($B->parse('1'));
		$this->assertTrue($B->value);
		$this->assertTrue($B->parse('y'));
		$this->assertTrue($B->value);
		$this->assertTrue($B->parse('yes'));
		$this->assertTrue($B->value);

		$I = new CLI\Option\Integer(array());
		$this->assertNull($I->default);
		$this->assertNull($I->value);
		$I = new CLI\Option\Integer(array(), 1);
		$this->assertEquals(1, $I->default);
		$this->assertEquals(1, $I->value);
		$this->assertFalse($I->parse('a'));
		$this->assertFalse($I->parse('1a'));
		$this->assertFalse($I->parse('a1'));
		$this->assertEquals(1, $I->value);
		$this->assertTrue($I->parse('2'));
		$this->assertEquals(2, $I->value);
		$this->assertTrue($I->parse('123'));
		$this->assertEquals(123, $I->value);

		$O = new CLI\Option\On(array());
		$this->assertNull($O->default);
		$O = new CLI\Option\On(array(), true);
		$this->assertTrue($O->default);
		$O = new CLI\Option\On(array(), false);
		$this->assertFalse($O->default);
		$this->assertFalse($O->value);
		$this->assertTrue($O->parse(null));
		$this->assertTrue($O->value);

		$T = new CLI\Option\Text(array());
		$this->assertNull($T->default);
		$this->assertNull($T->value);
		$T = new CLI\Option\Text(array(), 'foo');
		$this->assertEquals('foo', $T->default);
		$this->assertEquals('foo', $T->value);
		$this->assertTrue($T->parse('b'));
		$this->assertEquals('b', $T->value);
		$this->assertTrue($T->parse('bar'));
		$this->assertEquals('bar', $T->value);
	}

	function testParser()
	{
		$Parser = new CLI\Parser();
		$Parser->addOption(new CLI\Option\Text(array('-a'), 'foo', 'Desc A.'), $a);
		$Parser->addOption(new CLI\Option\Integer(array('-b', '--longb'), 123, 'Desc B.'), $b);
		$Parser->addOption(new CLI\Option\Boolean(array('-c', '--longc', '---extrac'), false, 'Desc C.'), $c);
		$Parser->addOption(new CLI\Option\On(array('-d', '--longd'), false, 'Desc D.'), $d);

		$this->assertMessage('help', $Parser->getHelpText());

		$Parser->arguments = array('script.php');
		$Parser->parse();
		$this->assertEquals('foo', $a);
		$this->assertEquals('foo', $Parser->results->a);
		$this->assertEquals(123, $b);
		$this->assertEquals(123, $Parser->results->b);
		$this->assertEquals(123, $Parser->results->longb);
		$this->assertEquals(false, $c);
		$this->assertEquals(false, $Parser->results->c);
		$this->assertEquals(false, $Parser->results->longc);
		$this->assertEquals(false, $Parser->results->extrac);
		$this->assertEquals(false, $d);
		$this->assertEquals(false, $Parser->results->d);
		$this->assertEquals(false, $Parser->results->longd);

		$Parser->arguments = explode(' ', 'script.php -a bar -b 321 -c yes -d');
		$Parser->parse();
		$this->assertEquals('bar', $a);
		$this->assertEquals('bar', $Parser->results->a);
		$this->assertEquals(321, $b);
		$this->assertEquals(321, $Parser->results->b);
		$this->assertEquals(321, $Parser->results->longb);
		$this->assertEquals(true, $c);
		$this->assertEquals(true, $Parser->results->c);
		$this->assertEquals(true, $Parser->results->longc);
		$this->assertEquals(true, $Parser->results->extrac);
		$this->assertEquals(true, $d);
		$this->assertEquals(true, $Parser->results->d);
		$this->assertEquals(true, $Parser->results->longd);
	}

	function testSession()
	{
		$Parser = new CLI\Parser();
		$Parser->addOption(new CLI\Option\Text(array('-t', '--text')));
		$Parser->addOption(new CLI\Option\Integer(array('-i', '--integer')));
		$Parser->addOption(new CLI\Option\Boolean(array('-b', '--bool')));

		$phpunit = $this;
		$main = function($results) use ($phpunit) {
			$phpunit->assertEquals('string', $results->text);
			$phpunit->assertEquals(123, $results->integer);
			$phpunit->assertEquals(true, $results->bool);
		};

		$Session = new CLI\Session($Parser, $main);
		$Session->help(new CLI\Option\Help());

		$Parser->arguments = array('script.php');
		$Session->printHelpOnError = false;
		$this->assertSessionMessageFail('argument-missing-text-nohelp', $Session);

		$Session->printHelpOnError = true;
		$this->assertSessionMessageFail('argument-missing-text', $Session);

		$Parser->arguments = explode(' ', 'script.php -t string');
		$this->assertSessionMessageFail('argument-missing-integer', $Session);

		$Parser->arguments = explode(' ', 'script.php -t string -i apa');
		$this->assertSessionMessageFail('argument-format-integer', $Session);

		$Parser->arguments = explode(' ', 'script.php -t string -i 123');
		$this->assertSessionMessageFail('argument-missing-bool', $Session);

		$Parser->arguments = explode(' ', 'script.php -t string -i 123 -b off');
		$this->assertSessionMessageFail('argument-format-bool', $Session);

		$Parser->arguments = explode(' ', 'script.php -t string -i 123 -b y');
		$r = $Session->run();
		$this->assertEquals(0, $r);
	}
}