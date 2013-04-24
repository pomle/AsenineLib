<?
namespace Asenine;

class ArchiverException extends \Exception
{}

class Archiver
{
	protected
		$workPath;


	public function __construct($path)
	{
		$path = rtrim($path, DIRECTORY_SEPARATOR);

		$this->workPath = $path . DIRECTORY_SEPARATOR;

		$this->depth = 4;
		$this->split = 2;
		$this->perms = 0644;
	}


	public function getFile($name)
	{
		return new File($this->getFileLocation($name));
	}

	public function getFileLocation($name)
	{
		return $this->getFilePath($name) . $name;
	}

	public function getFilePath($name)
	{
		return $this->workPath . $this->resolveHash($name);
	}

	public function getPath()
	{
		return $this->workPath;
	}

	public function putFile(File $File, $overwrite = false)
	{
		$name = $File->getHash();

		$inputFileName = (string)$File;

		$archiveFilePath = $this->getFilePath($name);

		if(file_exists($archiveFilePath))
		{
			if(!is_dir($archiveFilePath))
				throw New ArchiverException(sprintf('"%s" already exists and is not a dir', $archiveFilePath));

			if(!is_writeable($archiveFilePath))
				throw New ArchiverException(sprintf('"%s" is not writeable', $archiveFilePath));
		}
		elseif(!@mkdir($archiveFilePath, 0755, true))
			throw new ArchiverException(sprintf('Could not create dir "%s"', $archiveFilePath));


		$archiveFileName = $this->getFileLocation($name);


		if(!file_exists($archiveFileName) || $overwrite === true)
		{
			$ArchivedFile = $File->copy($archiveFileName);
			chmod($ArchivedFile->getLocation(), $this->perms);
		}
		else
		{
			$ArchivedFile = new File($archiveFileName);
		}

		return $ArchivedFile;
	}


	protected function resolveHash($hash)
	{
		$path = '';

		$i = 0;
		while($i < $this->depth)
			$path .= substr($hash, $i++ * $this->split, $this->split) . '/';

		return $path;
	}
}