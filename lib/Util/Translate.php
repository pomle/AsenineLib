<?
/**
 * Class containing utility functions for translations.
 *
 * @author Pontus Persson <pom@spotify.com>
 */
namespace Asenine\Util;

class Translate
{
	/**
	 * Applies a translation from $translation array onto $subject
	 * that can be either an array or a string.
	 *
	 * If $subject is a string, the method will check if the string exists as
	 * a key in $translation and return the corresponding value.
	 * If $subject is an array it will do it for every value in $subject array
	 * and return the new array.
	 *
	 * @param mixed $subject 		Value eligible for translation.
	 * @param mixed $translation 	Translation table to use.
	 * @return mixed				The translated result.
	 */
	public static function inject($subject, array $translation)
	{
		if (is_array($subject)) {

			if (count($subject)) {

				$subject = array_combine($subject, $subject);

				foreach($subject as $key => &$value) {

					if (isset($translation[$key])) {
						$value = $translation[$key];
					}
				}
			}
		}
		else {
			if (isset($translation[$subject])) {
				$subject = $translation[$subject];
			}
		}

		return $subject;
	}
}