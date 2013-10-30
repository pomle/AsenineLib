<?
namespace Asenine\Util;

class ForceDownloadUnbuffered
{
	public function __construct($name, $contentType = 'application/octet-stream')
	{
		ini_set('zlib.output_compression', 'off');
		header('Expires: ' . date('r', time() + 60*60*24*30));
		header('Accept-Ranges: bytes');
		header('Content-Description: File Transfer');
		header('Content-Disposition: attachment; filename="' . $name . '"');
		header('Content-Type: ' . $contentType);
		flush();
		ob_flush();
	}


	public function addData($string)
	{
		echo $string;
		flush();
		ob_flush();
	}

	public function addFile($filename)
	{
		readfile($filename);
	}
}