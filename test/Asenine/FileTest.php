<?php
/**
 * Tests for Asenine File
 *
 * @author Pontus Persson <pom@spotify.com>
 */

class FileTest extends PHPUnit_Framework_TestCase {

	function setUp() { }

	function tearDown() { }

	function test_File()
	{
		$fileName = 'Track.mp3';
		$fileLocation = DIR_TEST_FILES . $fileName;

		$File = new \Asenine\Disk\File($fileLocation);

		$this->assertSame($fileName, $File->getName());
		$this->assertSame($fileLocation, $File->getLocation());

		$fileSize = 4560385;
		$this->assertSame($fileSize, $File->getSize());

		$fileHash = '7da445f989bfaad661c3383363aec4604d9e5baea15c9d938ab79f4b2aa4c4af';
		$this->assertSame($fileHash, $File->getHash());

		$fileExt = 'mp3';
		$this->assertSame($fileExt, $File->getExtension());
	}
}
