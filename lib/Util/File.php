<?php
/**
 * Class containing function for interacting with file system.
 *
 * @author Pontus Persson <pom@spotify.com>
 */
namespace Asenine\Util;

class File
{
	/**
	 * Returns a unique path to a dir, optionally prefixed with $prefix.
	 *
	 * @param string $prefix 	Prefix path with this string.
	 * @return string
	 */
	public static function getTempDir($prefix = null)
	{
		$tmpFile = getTempFile($prefix);
		if (!unlink($tmpFile) || !mkdir($tmpFile)) {
			return false;
		}
		return $tmpFile;
	}

	/**
	 * Returns a unique path to a file, optionally prefixed with $prefix.
	 *
	 * @param string $prefix 	Prefix path with this string.
	 * @return string
	 */
	public static function getTempFile($prefix = null)
	{
		return tempnam(ASENINE_DIR_TEMP, $prefix ? $prefix . '_' : null);
	}

	/**
	 * Returns an array of file names occuring recursively under $path
	 * that matches $pattern.
	 *
	 * @param string $pattern 		Pattern to look for (*.mp3)
	 * @param string $path 			Root path to search in.
	 * @return array
	 */
	public static function recGlob($pattern, $path)
	{
		$command = sprintf("%s %s -name %s", 'find', escapeshellarg($path), escapeshellarg($pattern));
		return array_filter(explode("\n", shell_exec($command)));
	}
}