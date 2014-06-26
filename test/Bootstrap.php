<?php
/**
 * Bootstrapper for the Asenine Test Suite.
 *
 * @author Pontus Persson <pontus.alexander@gmail.com>
 */
spl_autoload_register(function ($class) {
	require __DIR__ . '/../lib/' . str_replace(array('\\', 'Asenine/'), array('/', ''), $class) . '.php';
});

define('DIR_TEST_RESOURCES', __DIR__ . '/resource/');
define('DIR_TEST_FILES', DIR_TEST_RESOURCES . 'files/');