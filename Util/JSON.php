<?
/**
 * Class containing methods operating with JSON.
 */
namespace Asenine\Util;

class JSONException extends \Exception
{}

class JSON
{
	/**
	 * Decodes JSON and throws an Exception with readable message
	 * if operation fails.
	 *
	 * @param string $jsonString 		The JSON as string.
	 * @return \stdClass 				json_decode result.
	 * @throws JSONException
	 */
	public static function decode($jsonString)
	{
		$jsonObject = json_decode($jsonString);

		$jsonError = json_last_error();

		if (JSON_ERROR_NONE !== $jsonError) {

			switch ($jsonError) {

				case JSON_ERROR_DEPTH:
					$jsonErrorMsg = 'The maximum stack depth has been exceeded';
				break;

				case JSON_ERROR_STATE_MISMATCH:
					$jsonErrorMsg = 'Invalid or malformed JSON';
				break;

				case JSON_ERROR_CTRL_CHAR:
					$jsonErrorMsg = 'Control character error, possibly incorrectly encoded';
				break;

				case JSON_ERROR_SYNTAX:
					$jsonErrorMsg = 'Syntax error';
				break;

				case JSON_ERROR_UTF8:
					$jsonErrorMsg = 'Malformed UTF-8 characters, possibly incorrectly encoded';
				break;

				default:
					$jsonErrorMsg = 'Unknown error';
				break;
			}

			throw new JSONException(sprintf('JSON Decode Failed with error code %d, %s', $jsonError, $jsonErrorMsg));
		}

		return $jsonObject;
	}
}