<?php

require(__DIR__ . '/../vendor/autoload.php');

// Set up autoload
spl_autoload_register(function ($className) {
	if (preg_match('/^(?:TestAuxFiles)\\\\[^\\\\]+$/AD', $className))
	{
		include(__DIR__ . DIRECTORY_SEPARATOR . str_replace('\\', DIRECTORY_SEPARATOR, $className) . '.php');
		return true;
	}
	return false;
});
