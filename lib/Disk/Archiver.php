<?php
/**
 * Helps with creating dir hierarchy, writing files to and resolving file names
 * in a file name splitted archives.
 *
 * @author Pontus Persson <pom@spotify.com>
 */
namespace Asenine\Disk;

class Archiver
{
	/**
	 * @var string   Working dir of this instance.
	 */
	protected $workPath;

	/**
	 * @var int   Amount of chunks used for dir split. /ae/34/3f/
	 */
	protected $depth;

	/**
	 * @var int   Split chunk length.
	 */
	protected $split;

	/**
	 * @var int   File permission used when storing files.
	 */
	protected $perms;


	/**
	 * Get an instance of a service
	 *
	 * @param string $path 		Working dir.
	 * @param string $depth 	Dir depth to use.
	 * @param string $split 	Split length to use.
	 * @param string $perms 	File permissions when writing.
	 */
	public function __construct($path, $depth = 4, $split = 2, $perms = 0644)
	{
		$path = rtrim($path, DIRECTORY_SEPARATOR);

		$this->workPath = $path . DIRECTORY_SEPARATOR;

		if (!is_int($depth)) {
			throw new \InvalidArgumentException('Argument #2 of ' . __METHOD__ . ' must be integer.');
		}

		if (!is_int($split)) {
			throw new \InvalidArgumentException('Argument #3 of ' . __METHOD__ . ' must be integer.');
		}

		$this->depth = (int)$depth;
		$this->split = $split;
		$this->perms = $perms;
	}

	/**
	 * Returns an instance of \Asenine\Disk\File for a file name.
	 *
	 * @param string $name 		File name to resolve into a complete file path.
	 * @return \Asenine\Disk\File
	 */
	public function getFile($name)
	{
		return new File($this->getFileLocation($name));
	}

	/**
	 * Returns complete path to file.
	 *
	 * @param string $name 		File name to resolve into a complete file path.
	 * @return string
	 */
	public function getFileLocation($name)
	{
		return $this->getFilePath($name) . $name;
	}

	/**
	 * Returns dir path where the file resides.
	 *
	 * @param string $name 		File name to resolve into path.
	 * @return string
	 */
	public function getFilePath($name)
	{
		return $this->workPath . $this->resolveHash($name);
	}

	/**
	 * Returns the working dir.
	 *
	 * @return string
	 */
	public function getPath()
	{
		return $this->workPath;
	}

	/**
	 * Stores a file in the archive under the correct dir, creating any dir necessary
	 * and returns a new \Asenine\Disk\File object for the new file. If file already exists in
	 * in archive, that one will be used.
	 *
	 * @param \Asenine\Disk\File $File 	File object to use.
	 * @param bool $overwrite 		Overwrites if already exists.
	 * @return \Asenine\Disk\File
	 */
	public function putFile(File $File, $overwrite = false)
	{
		$name = $File->getHash();

		$inputFileName = (string)$File;

		$archiveFilePath = $this->getFilePath($name);

		if (file_exists($archiveFilePath)) {

			if (!is_dir($archiveFilePath)) {
				throw new \RuntimeException(sprintf('"%s" already exists and is not a dir', $archiveFilePath));
			}

			if (!is_writable($archiveFilePath)) {
				throw new \RuntimeException(sprintf('"%s" is not writeable', $archiveFilePath));
			}
		}
		elseif (!@mkdir($archiveFilePath, 0755, true)) {
			throw new \RuntimeException(sprintf('Could not create dir "%s"', $archiveFilePath));
		}


		$archiveFileName = $this->getFileLocation($name);


		if (!file_exists($archiveFileName) || $overwrite === true) {
			$ArchivedFile = $File->copy($archiveFileName);
			chmod($ArchivedFile->getLocation(), $this->perms);
		}
		else {
			$ArchivedFile = $File->point($archiveFileName);
		}

		return $ArchivedFile;
	}


	/**
	 * Resolves path for a hash, basically just putting in / in the string.
	 *
	 * @param string $hash   String to split.
	 * @return string
	 */
	protected function resolveHash($hash)
	{
		$path = '';

		$i = 0;
		while ($i < $this->depth) {
			$path .= substr($hash, $i++ * $this->split, $this->split) . '/';
		}

		return $path;
	}
}
