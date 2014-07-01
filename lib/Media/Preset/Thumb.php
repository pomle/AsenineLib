<?php
namespace Asenine\Media\Preset;

use Asenine\Media\Preset;

class Thumb extends Preset
{
	const NAME = 'thumb';

	public function __construct($mediaHash, $x, $y, $crop = false, $quality = 90)
	{
		$this->mediaHash = $mediaHash;
		$this->x = abs($x);
		$this->y = abs($y);
		$this->crop = (bool)$crop;
		$this->quality = abs($quality);
		$this->subPath = sprintf('%ux%ux%ux%u/', $this->x, $this->y, $this->crop, $this->quality);
		$this->ext = '.jpg';
	}

	public function createFile($filepath)
	{
		$Factory = new \Asenine\Media\Generator\ImageResize($this->getMedia(), $this->x, $this->y, $this->crop, $this->quality);
		return $Factory->saveToFile($filepath);
	}
}