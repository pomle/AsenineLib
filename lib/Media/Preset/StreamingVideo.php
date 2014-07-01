<?php
namespace Asenine\Media\Preset;

class StreamingVideo extends \Asenine\Media\Preset
{
	const NAME = 'streamingVideo';

	public function __construct($mediaHash, $x, $y)
	{
		$this->mediaHash = $mediaHash;
		$this->x = abs($x);
		$this->y = abs($y);
		$this->subPath = sprintf('%ux%u/', $this->x, $this->y);
		$this->ext = '.mp4';
	}


	public function createFile($filepath)
	{
		$Factory = new \Media\Generator\x264($this->getMedia(), $this->x, $this->y);
		return $Factory->saveToFile($filepath);
	}
}