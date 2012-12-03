<?
namespace Asenine;

class FileException extends \Exception
{}

interface iFile
{
	public function __construct($location, $size = null, $mime = null, $name = null, $hash = null);
}

class File implements iFile
{
	protected
		$location,
		$size,
		$hash,
		$extension;

	public
		$mime,
		$name;


	public static function createInDB(self $File)
	{
		$timeCreated = $timeModified = time();

		$query = DB::prepareQuery("SELECT id FROM asenine_files WHERE hash = %s", $File->getHash());

		$File->fileID = (int)DB::queryAndFetchOne($query);

		if (!$File->fileID) {

			$query = DB::prepareQuery("INSERT INTO
				asenine_files (
					time_created,
					time_modified,
					hash,
					size,
					mime,
					ext
				) VALUES (
					%u,
					%u,
					%s,
					%u,
					NULLIF(%s, ''),
					NULLIF(%s, '')
				)",
				$timeCreated,
				$timeModified,
				$File->getHash(),
				$File->getSize(),
				$File->getMime(),
				$File->getExtension());

			$File->fileID = (int)DB::queryAndGetID($query, 'asenine_files_id_seq');
			$File->timeCreated = $timeCreated;
			$File->timeModified = $timeModified;
		}
	}

	public static function fromURL($fromURL, $toFile = null)
	{
		$d = $s = null;

		try
		{
			if (empty($fromURL)) {
				throw New FileException('URL empty.');
			}

			if (!$toFile) {
				$toFile = tempnam(ASENINE_DIR_TEMP, 'AsenineDownload');
			}

			if (!$d = @fopen($toFile, 'w')) {
				throw New FileException(sprintf('Could not open destination "%s" for writing', $toFile));
			}

			if (!$s = @fopen($fromURL, 'r')) {
				throw New FileException(sprintf('Could not open source "%s" for reading', $fromURL));
			}

			$bufferSize = 512 * 16;

			$t = microtime(true);

			$downloadBytes = 0;

			while (($buffer = fgets($s, $bufferSize)) !== false) {
				$downloadBytes += fputs($d, $buffer);
			}

			$downloadTime = microtime(true) - $t;

			fclose($s);
			fclose($d);


			$name = basename($fromURL);
			if (strpos($name, '%') !== false) {
				$name = urldecode($name); ### If URL contains % we assume it's URL encoded.
			}


			$File = new static($toFile, filesize($toFile), null, $name);

			$File->name = $name;

			$File->downloadBytes = $downloadBytes;
			$File->downloadTime = $downloadTime;

			return $File;
		}
		catch(\Exception $e)
		{
			if($d) {
				fclose($d);
			}

			if($s) {
				fclose($s);
			}

			throw $e;
		}
	}

	public static function fromPHPUpload($phpfile)
	{
		switch($phpfile['error'])
		{
			case UPLOAD_ERR_INI_SIZE:
				throw new FileException('Uploaded file too large for the webserver');

			case UPLOAD_ERR_NO_TMP_DIR:
				throw new FileException('No temporary storage available');
		}

		$File = new static($phpfile['tmp_name'], $phpfile['size'], $phpfile['type'], $phpfile['name']);

		return $File;
	}

	public static function loadFromDB(Archiver $Archiver, $fileIDs)
	{
		if (!$returnArray = is_array($fileIDs)) {
			$fileIDs = (array)$fileIDs;
		}

		$files = array_fill_keys($fileIDs, false);

		$query = DB::prepareQuery("SELECT
				id AS file_id,
				time_created,
				time_modified,
				hash,
				size,
				mime,
				ext
			FROM
				asenine_files
			WHERE
				id IN %a",
			$fileIDs);

		$result = DB::fetch($query);

		while ($file = DB::assoc($result)) {
			$fileID = (int)$file['file_id'];

			$fileLocation = $Archiver->getFileLocation($file['hash']);

			$File = new static(
				$fileLocation,
				(int)$file['size'] ?: null,
				$file['mime'],
				null,
				$file['hash']
			);

			$File->extension = $file['ext'];

			$File->timeCreated = (int)$file['time_created'] ?: null;
			$File->timeModified = (int)$file['time_modified'] ?: null;
			$File->fileID = $fileID;

			$files[$fileID] = $File;
		}

		return $returnArray ? $files : reset($files);
	}

	public static function removeFromDB(self $File)
	{
		$query = DB::prepareQuery("DELETE FROM asenine_files WHERE ID = %u", $File->fileID);
		DB::queryAndCountAffected($query);
		return true;
	}

	public static function saveToDB(self $File)
	{
		$timeCreated = $timeModified = time();

		### Try find the file on hash first, and set it's ID
		if (!isset($File->fileID)) {
			self::createInDB($File);
		}

		$query = DB::prepareQuery("UPDATE
				asenine_files
			SET
				time_modified = %d,
				mime = NULLIF(%s, '')
			WHERE
				id = %d",
			$timeModified,
			$File->getMime(),
			$File->fileID);

		DB::queryAndCountAffected($query);

		$File->timeModified = $timeModified;
	}


	public function __construct($location, $size = null, $mime = null, $name = null, $hash = null)
	{
		if (!is_string($location)) {
			trigger_error(__METHOD__ . ' expects arg #1 to be string, ' . gettype($location) . ' given', E_USER_WARNING);
		}

		$location = (string)$location;

		/*if( !file_exists($location) )
			throw New FileException(sprintf("Path does not exist: %s", $location));

		if( !is_file($location) )
			throw New FileException(sprintf("Path is not a file: %s", $location));*/

		$this->location = $location;

		### File size can only be integer and must not be negative
		if (!is_null($size) && (!is_int($size) && ($size < 0 ))) {
			throw New FileException(sprintf("Size must be integer and 0 or more"));
		}

		$this->hash = $hash;
		$this->size = $size ?: filesize($this->location);
		$this->mime = $mime;
		$this->name = $name ?: basename($this->location);
	}

	public function __get($key)
	{
		### Auto calculate hash and size if not available already
		switch($key)
		{
			case 'hash':
				return $this->getHash();
			break;

			case 'mime':
				return $this->getMime();
			break;

			case 'size':
				return $this->getSize();
			break;
		}

		return isset($this->$key) ? $this->key : null;
	}

	public function __isset($key)
	{
		return isset($this->$key);
	}

	public function __toString()
	{
		return $this->location;
	}


	public function copy($to)
	{
		if (!copy($this->location, $to)) {
			throw new FileException(sprintf('File copy from "%s" to "%s" failed', $this->location, $to));
		}

		$File_New = clone $this;
		$File_New->location = $to;

		return $File_New;
	}

	public function delete()
	{
		if (!unlink($this->location)) {
			throw new FileException(sprintf('File delete from "%s" failed', $this->location));
		}

		return true;
	}

	public function exists()
	{
		return file_exists($this->location);
	}

	public function link($at)
	{
		if (!symlink($this->location, $at)) {
			throw new FileException(sprintf('File symlinking from "%s" to "%s" failed', $this->location, $at));
		}

		$File_Link = clone $this;
		$File_Link->location = $at;

		return $File_Link;
	}

	public function move($to)
	{
		if (!rename($this->location, $to)) {
			throw new FileException(sprintf('File move from "%s" to "%s" failed', $this->location, $to));
		}

		$this->location = $to;

		return true;
	}

	public function getContents()
	{
		return file_get_contents($this->location);
	}

	public function getExtension()
	{
		if (!isset($this->extension)) {

			if (($n = explode('.', $this->name)) && ($ext = end($n))) {
				$this->extension = $ext;
			}
			elseif (($m = explode('/', $this->getMime())) && ($ext = end($m))) {
				$this->extension = $ext;
			}
			else {
				$this->extension = null;
			}
		}

		return $this->extension;
	}

	public function getHash()
	{
		if (is_null($this->hash)) {
			$this->hash = hash_file('sha256', $this->location, false);
		}

		return $this->hash;
	}

	public function getLocation()
	{
		return $this->location;
	}

	public function getMime()
	{
		if (is_null($this->mime)) {
			$finfo = finfo_open(FILEINFO_MIME_TYPE);
			$this->mime = finfo_file($finfo, $this->location);
			finfo_close($finfo);
		}

		return $this->mime;
	}

	public function getName()
	{
		return $this->name ?: basename($this->getLocation());
	}

	public function getSize()
	{
		if (is_null($this->size)) {
			$this->size = filesize($this->location);
		}

		return $this->size;
	}

	public function reads()
	{
		return is_readable($this->location);
	}

	public function sendToClient($name = null, $contentType = null)
	{
		if (!$this->exists()) {
			throw new FileException('File does not exist on disk.');
		}

		if (!$this->reads()) {
			throw new FileException('File is not readable.');
		}

		if (strlen($name) > 0) {
			$fileName = $name;
		}
		else {
			$fileName = $this->getName();
		}

		if (strlen($contentType) == 0) {
			$contentType = $this->getMime();
		}

		ini_set('zlib.output_compression', 'off');
		header('Accept-Ranges: bytes');
		header('Content-Type: ' . $contentType);
		header('Content-Disposition: attachment; filename="' . $fileName . '"');
		header('Content-Length: ' . $this->getSize());

		$res = @readfile($this->getLocation());

		if (false === $res) {
			throw new FileException('File send failed.');
		}
	}

	public function writes()
	{
		return is_writeable($this->location);
	}
}