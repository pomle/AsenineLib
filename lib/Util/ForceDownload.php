<?php
namespace Asenine\Util;

class ForceDownload
{
	protected $data = '';

	public function __construct()
	{
	}


	public function addData($string)
	{
		$this->data .= $string;
		return $this;
	}

	public function addFile($filename)
	{
		$this->addData(file_get_contents($filename));
		return $this;
	}

	public function send($name, $contentType = 'application/octet-stream')
	{
		ini_set('zlib.output_compression', 'off');

		header('Expires: ' . date('r', time() + 60*60*24*30));
		header('Accept-Ranges: bytes');
		header('Content-Type: ' . $contentType);
		header('Content-Disposition: attachment; filename="' . $name . '"');
		header('Content-Length: ' . strlen($this->data));

		echo $this->data;
	}
}