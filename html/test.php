<?php

function autoload($className) {
	$className = str_replace('\\', '/', $className);
	require(__DIR__ . '/classes/'.$className. '.class.php');
	//printf(__DIR__ . '/classes/'.$className. '.class.php');
}

	Error_Reporting( E_ALL | E_STRICT );
	Ini_Set( 'display_errors', true );
	
	spl_autoload_register('autoload');
	$SS = new ServerStatus\ServerStatusStructure();

	printf(APPLICATION_DIR);
?>