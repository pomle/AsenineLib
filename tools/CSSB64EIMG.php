<?php
require __DIR__ . '/../lib/Util/CSS.php';

try {
	if (!isset($argv[1])) {
		throw new Exception('Usage: ' . basename(__FILE__) . ' [css file]');
	}

	$doStripWhitespace = in_array('-s', $argv);


	$cssFile = $argv[1];

	$css = file_get_contents($cssFile);


	$css = \Asenine\Util\CSS::base64EncodeImages($css, dirname($cssFile));


	if ($doStripWhitespace) {
		$css = \Asenine\Util\CSS::stripWhitespace($css);
	}

	echo $css;

}
catch (Exception $e) {
	echo $e->getMessage(), "\n";
	die(1);
}

