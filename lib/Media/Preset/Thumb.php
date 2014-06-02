<?php
namespace Asenine\Media\Preset;

class Thumb extends \Asenine\Media\Preset
{
	const NAME = 'thumb';

	public function __construct(\Asenine\Media\Type\_Visual $Media, $x, $y, $crop = false, $quality = 90)
	{
		$this->Media;
		$this->mediaHash = $Media->File->hash;
		$this->x = abs($x);
		$this->y = abs($y);
		$this->crop = (bool)$crop;
		$this->quality = abs($quality);
		$this->subPath = sprintf('%ux%ux%ux%u/', $this->x, $this->y, $this->crop, $this->quality);
		$this->ext = '.jpg';
	}

	public function createFile($filepath)
	{
		$Factory = new \Asenine\Media\Generator\ImageResize($this->Media, $this->x, $this->y, $this->crop, $this->quality);
		return $Factory->saveToFile($filepath);
	}
}