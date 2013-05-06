<?
namespace Asenine\Media;

abstract class Producer
{
	protected
		$Media;


	public static function createFromMedia(\Media $Media)
	{
		$Producer = new static($Media->mediaHash);
		$Producer->Media = $Media;
		return $Producer;
	}


	public static function createFromHash($mediaHash)
	{
		$Producer = new static($mediaHash);
		return $Producer;
	}


	public function __construct($mediaHash)
	{
		$this->mediaHash = $mediaHash;
	}

	public function getMedia()
	{
		if( !isset($this->Media) )
		{
			$this->Media = \Asenine\Media::loadByHash($this->mediaHash);
		}

		return $this->Media;
	}
}