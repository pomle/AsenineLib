<?php
/**
 * Tests for Asenine File
 *
 * @author Pontus Persson <pom@spotify.com>
 */
use Asenine\Disk\File;

class FileTest extends PHPUnit_Framework_TestCase {

	function testFile()
	{
		$fileName = 'Track.mp3';
		$fileLocation = DIR_TEST_FILES . $fileName;

		/* Ensure auto detection of properties work. */
		$File = new File($fileLocation);
		$this->assertEquals($fileName, $File->getName());
		$this->assertEquals($fileLocation, $File->getLocation());
		$this->assertEquals(4560385, $File->getSize());
		$this->assertEquals('7da445f989bfaad661c3383363aec4604d9e5baea15c9d938ab79f4b2aa4c4af', $File->getHash());
		$this->assertEquals('mp3', $File->getExtension());
		$this->assertEquals('audio/mpeg', $File->getMime());

		/* Supplied properties should override real. */
		$File = new File($fileLocation, 1024, 'image/jpeg', 'Image.jpg', '9e5baea15c9d938ab79f4b2aa4c4af7da445f989bfaad661c3383363aec4604d');
		$this->assertEquals(1024, $File->getSize());
		$this->assertEquals('jpg', $File->getExtension());
		$this->assertEquals('image/jpeg', $File->getMime());
		$this->assertEquals('9e5baea15c9d938ab79f4b2aa4c4af7da445f989bfaad661c3383363aec4604d', $File->getHash());

		/* Ensure extension parsing behaves. */
		$File = new File('/tmp/fakefile', null, 'too/looong');
		$this->assertEquals(null, $File->getExtension());
		$File = new File('/tmp/fakefile', null, 'just/right');
		$this->assertEquals('right', $File->getExtension());
		$File = new File('/tmp/fakefile', null, null, 'too.looong');
		$this->assertEquals(null, $File->getExtension());
		$File = new File('/tmp/fakefile', null, null, 'multi.dotted.too.looong');
		$this->assertEquals(null, $File->getExtension());
		$File = new File('/tmp/fakefile', null, null, 'multi.dotted.just.right');
		$this->assertEquals('right', $File->getExtension());
	}
}