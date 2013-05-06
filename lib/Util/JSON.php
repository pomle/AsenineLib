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

	/**
	 * Takes a JSON string and formats it so that it's readable by humans.
	 *
	 * @param string $s 	The JSON as string.
	 * @param string $d 	String to use as indenter, defaults to tab.
	 * @return string
	 * @throws JSONException
	 */
	public static function prettify($s, $d = "\t") {

		$b = ''; // Buffer.
		$o = strlen($s); // Omega (end).
		$i = 0; // Iterator.
		$l = 0; // indentation Level.

		/* Be aware that "continue;" increments $i by 1. */
		do {
			$c = $s[$i];

			/* All whitespace out of quote is stripped. */
			if (" " == $c || "\t" == $c || "\n" == $c) {
				continue;
			}

			if ('{' == $c || '[' == $c) {
				$b .= "\n" . str_repeat($d, $l++) . $c . "\n" . str_repeat($d, $l);
				continue;
			}

			/* If we find a quote, buffer chars until next quote. */
			if ("\"" == $c) {

				do {
					$b .= $c;

					/* If we just buffered a backslash, buffer following char blindly and increment p. */
					if ("\\" == $c) {
						$b .= $s[++$i];
					}

					$c = $s[++$i];

					if ($i >= $o) {
						throw new JSONException('Malformed JSON.');
					}

				} while("\"" != $c || $i < $o);
			}

			if ('}' == $c || ']' == $c) {
				$b .= "\n" . str_repeat($d, --$l) . $c;
				continue;
			}

			$b .= $c;

			/* If we just outputted a comma, go to new line and indent. */
			if (',' == $c) {
				$b .= "\n" . str_repeat($d, $l);
			}
			/* In case it was a colon, insert a space. */
			elseif (":" == $c) {
				$b .= ' ';
			}

		} while(++$i < $o);

		return $b;
	}
}