<?
/**
 * Class containing methods operating on CSS.
 */
namespace Asenine\Util;

class CSSException extends \Exception
{}

class CSS
{
	public static function base64EncodeImages($css, $cssPath)
	{
		if (preg_match_all('%url\((.+)\)%U', $css, $matches)) {

			$cssPath = realpath($cssPath);

			if (!$cssPath) {
				throw new CSSException("Argument #2 to " . __METHOD__ . " must be an existing path.");
			}

			$cssPath .= '/';

			$pathHistory = array();

			foreach ($matches[1] as $index => $bracketContent) {

				$imageRelPath = trim($bracketContent, '\'"');

				if (in_array($imageRelPath, $pathHistory)) {
					throw new CSSException("$imagePath occurs several times in source.");
				}

				$ext = end(explode('.', $imageRelPath));

				if (!$ext) {
					throw new CSSException("Could not parse extension for $imageRelPath.");
				}

				$pathHistory[] = $imageRelPath;

				$imagePath = $cssPath . $imageRelPath;

				$srcFile = realpath($cssPath . $imageRelPath);

				if (!$srcFile || !is_file($srcFile)) {
					throw new CSSException("File $imageRelPath does not exist relative to $cssPath.");
				}

				$fileContent = file_get_contents($srcFile);
				$base64Content = base64_encode($fileContent);
				$css = str_replace($bracketContent, 'data:image/' . $ext . ';base64,' . $base64Content, $css);
			}
		}

		return $css;
	}

	public static function stripWhitespace($css)
	{
		$css = trim($css);

		$css = preg_replace('%\s*([{}:;,])\s*%', '\\1', $css);

		/* Strip comments. */
		$css = preg_replace('%/\*.*?\*/%s', '', $css);

		/* Remove newlines and tabs. */
		$css = str_replace(array("\t", "\r", "\n"), '', $css);

		return $css;
	}
}