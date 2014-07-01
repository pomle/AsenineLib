<?php
/**
 * Policy class
 *
 * @author Pontus Persson <pom@spotify.com>
 */
namespace Asenine\Access;

class Policy
{
	public $name;
	public $description;


	public static function stripIllegalChars($string)
	{
		return preg_replace('%[^A-Za-z]%', '', $string);
	}


	public function __construct($name = null, $description = null)
	{
		$this->name = $name;
		$this->description = $description;
	}


	public function getDescription()
	{
		return $this->description;
	}

	public function getName()
	{
		return $this->name;
	}
}
