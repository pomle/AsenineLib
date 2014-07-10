<?php
namespace Asenine\Media;

use Asenine\Disk\Archiver;
use Asenine\Media;

interface iPreset
{
	public function createFile($filepath);
}

abstract class Preset implements iPreset
{
	protected static $Archiver;
	public static $generationPath;
	public static $generationUrl;

	protected $mediaHash;
	protected $subPath;
	protected $ext;


	public static function setArchiver(Archiver $Archiver)
	{
		self::$Archiver = $Archiver;
	}


	final public function getFile($wait = true)
	{
		$fileExists = $this->isGenerated();

		if (!$fileExists) {
			$sleepTime = 100000; // 100 ms

			$dirPath = self::$generationPath . '/' . $this->getPath();
			if (!file_exists($dirPath) && !is_dir($dirPath) && !@mkdir($dirPath, 0755, true)) {
				throw new \RuntimeException("Path not reachable \"$dirPath\"");
			}

			$filePath = $this->getFullFilePath();


			if (!$resource = @fopen($filePath, "c")) {
				throw new \RuntimeException("Could not create handle for \"$filePath\"");
			}

			for(;;) {

				$haveLock = flock($resource, LOCK_EX | LOCK_NB, $wouldblock);

				if ($haveLock) {
					clearstatcache($filePath);

					$fileSize = filesize($filePath);

					/* A 0 byte fileSize means that we are the creator */
					if ($fileSize == 0) {
						ftruncate($resource, 0);

						if (!$this->createFile($filePath)) {
							throw new \RuntimeException(get_class($this) . "::createFile() returned false for $filePath");
						}
					}

					flock($resource, LOCK_UN);

					$fileExists = true;

					break;
				}

				if ($wait) {
					usleep($sleepTime);
				}
				else {
					break;
				}
			}

			fclose($resource);
		}

		return $fileExists ? $this->getFilePath() : false;
	}

	final public function getFileName()
	{
		return $this->mediaHash . $this->ext;
	}

	final public function getFilePath()
	{
		return $this->getPath() . $this->getFileName();
	}

	final public function getFullFilePath()
	{
		return self::$generationPath . '/' . $this->getFilePath();
	}

	final public function getMedia()
	{
		return Media::createFromFile($this->getSourceFile());
	}

	final public function getMediaHash()
	{
		return $this->mediaHash;
	}

	final public function getPath()
	{
		return 'autogen/preset/' . static::NAME . '/' . $this->subPath;
	}

	public function getSourceFile()
	{
		if (!self::$Archiver instanceof Archiver) {
			throw new \RuntimeException('Archiver object not initialized.');
		}
		return self::$Archiver->getFile($this->mediaHash);
	}

	final public function getURL($wait = true)
	{
		try {
			if (!$this->getFile($wait)) {
				return false;
			}

			return self::$generationUrl . $this->getFilePath();
		}
		catch (\Exception $e) {
			throw new \RuntimeException(get_called_class() . ' media generation failed, Reason: ' . $e->getMessage());
		}
	}

	final public function isGenerated()
	{
		$diskFile = $this->getFullFilePath();
		return (file_exists($diskFile) && filesize($diskFile) > 0);
	}
}