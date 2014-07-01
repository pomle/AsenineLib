<?php
namespace Asenine;

use Asenine\Disk\File;

interface iMedia
{
	public static function canHandleFile($filePath);
	public function __construct($mediaHash = null, File $File = null);
	public function getInfo();
}

class Media implements iMedia
{
	public $File;

	public $mediaID;
	public $mediaHash;
	public $mimeType;
	public $fileOriginalName;


	public static function canHandleFile($filePath)
	{
		return false;
	}

	public static function createFromFile(File $File)
	{
		if (!$File->reads()) {
			throw new \RuntimeException("File not readable: \"" . $File . "\"");
		}

		/* If this object has been extended and has it's type set,
		   ensure we only test that type so that Audio plugin does
		   not accept Images etc. */
		$requireType = null;
		if (defined('static::TYPE')) {
			$requireType = static::TYPE;
		}

		foreach (self::getPlugins() as $className) {
			if ($requireType && $requireType != $className::TYPE) {
				continue;
			}

			if (!$className::canHandleFile($File)) {
				continue;
			}

			$Media = new $className($File->hash, $File);
			$Media->mimeType = $File->mime;
			return $Media;
		}

		$fileName = $File->getLocation();
		if ($requireType) {
			$vanityNames = self::getTypes();
			if (isset($vanityNames[$requireType])) {
				$requireType = $vanityNames[$requireType];
			}
			throw new \InvalidArgumentException("$fileName is not of type $requireType.");
		}
		else {
			throw new \InvalidArgumentException("No media plugin candidate for $fileName.");
		}
	}

	final public static function createFromFilename($filename, $mime = null)
	{
		return self::createFromFile(new File($filename, null, null, $mime));
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

	public static function getPlugins()
	{
		static $plugins;

		if (!isset($plugins)) {
			$plugins = array();

			$pluginPath = realpath(__DIR__ . '/Media/Type');
			if (false === $pluginPath) {
				return $plugins;
			}

			$pluginPath .= '/';

			$pluginFiles = glob($pluginPath . '*.php');

			foreach ($pluginFiles as $pluginFile) {
				if (!preg_match('/\/([A-Za-z0-9]+)\.php/u', $pluginFile, $className)) {
					continue;
				}

				$className = '\\Asenine\\Media\\Type\\' . $className[1];

				if (class_exists($className)) {
					$plugins[$className::TYPE] = $className;
				}
			}
		}

		return $plugins;
	}

	public static function getTypes()
	{
		static $pluginNames;

		if (!isset($pluginNames)) {
			$pluginNames = array();
			foreach (self::getPlugins() as $className) {
				$pluginNames[$className::TYPE] = $className::DESCRIPTION;
			}
			asort($pluginNames);
		}

		return $pluginNames;
	}

	final public function isFileValid()
	{
		return static::canHandleFile($this->filePath);
	}
}