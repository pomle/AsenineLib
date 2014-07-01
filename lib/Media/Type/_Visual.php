<?php
namespace Asenine\Media\Type;

interface iVisual
{
	public function getFrameCount();
	public function getFrame($index);
	public function getPreviewImage();
}

abstract class _Visual extends \Asenine\Media implements iVisual
{
	const VARIANT = 'visual';

	public $orientation = 0;
}