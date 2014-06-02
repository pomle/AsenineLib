<?php
namespace Asenine;

class MediaException extends \Exception
{}

interface iMedia
{
	public static function canHandleFile($filePath);
	public function __construct($mediaHash = null, File $File = null);
	public function getInfo();
}

class Media implements iMedia
{
	protected
		$File;

	public
		$mediaID,
		$mediaHash,
		$mimeType,
		$fileOriginalName;


	public static function canHandleFile($filePath)
	{
		return false;
	}

	public static function createFromFile(File $File)
	{
		if (!$File->reads()) {
			throw new \RuntimeException("File not readable: \"" . $File . "\"");
			return false;
		}

		if (!static::canHandleFile($File)) {
			throw new \RuntimeException(get_called_class() . " can not handle file: \"" . $File . "\"");
			return false;
		}

		$mediaHash = $File->hash;

		$Media = new static($mediaHash, $File);
		$Media->mimeType = $File->mime;

		return $Media;
	}

	final public static function createFromFilename($filename, $mime = null)
	{
		return self::createFromFile( new File($filename, null, null, $mime) );
	}

	final public static function createFromHash($mediaHash)
	{
		$filePath = DIR_MEDIA_SOURCE . $mediaHash;
		return new static($mediaHash, new File($filePath) );
	}

	public static function createFromType($type, $mediaHash, File $File)
	{
		if (strlen($type) == 0) {
			throw new \InvalidArgumentException(__METHOD__ . ' requires argument #1 to be non-zero length string');
		}

		$classPath = '\\Asenine\\Media\\Type\\' . ucfirst($type);

		if (!class_exists($classPath)) {
			throw new \InvalidArgumentException("No media handler for type $type.");
		}

		return new $classPath($mediaHash, $File);
	}


	public function __construct($mediaHash = null, File $File = null)
	{
		#if( strlen($mediaHash) !== 32 ) trigger_error(__METHOD__ . ' expects argument 1 to be string of exact length 32', E_USER_ERROR);
		$this->mediaHash = $mediaHash;
		$this->File = $File;
	}

	final public function __get($key)
	{
		return $this->$key;
	}

	final public function __isset($key)
	{
		return isset($this->$key);
	}

	final public function __toString()
	{
		return $this->mediaHash;
	}


	final public function getFile()
	{
		return $this->File;
	}

	final public function getFilePath()
	{
		return (string)$this->File; ### $File::__toString() provides $File->location and if null will be ""
	}

	public function getInfo()
	{
		throw new \RuntimeException(get_called_class() . ' has not implemented ' . __METHOD__);
	}

	final public function getMediaHash()
	{
		return $this->mediaHash;
	}

	final public function isFileValid()
	{
		return static::canHandleFile($this->filePath);
	}
}