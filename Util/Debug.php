<?
/**
 * Debug class containing various functions for simplifying debug output and logging.
 *
 * @author Pontus Persson <pom@spotify.com>
 */
namespace Asenine\Util;

class Debug
{
	/**
	 * Returns debug string if in debug mode, otherwise production string is
	 *	returned and debug string outputted as error for log grabbing.
	 *
	 * @param string $debug 		String to output if debug mode is on.
	 * @param string $production 	String to output if debug mode is off.
	 * @return string
	 */
	public static function log($debug, $production = 'System Error')
	{
		$isDebugging = (defined('DEBUG') && (true === constant('DEBUG')));

		if (true === $isDebugging) {
			return $debug;
		}
		else {
			ob_start();
			debug_print_backtrace();
			$backtrace = ob_get_clean();
			error_log("PHP Debug: " . $debug . "\n" . $backtrace);
			return $production;
		}
	}
}