<?php


	// Change this to false, as soon as everything is up and running
	define('VERBOSE', true);


	$servers = array();

	// Add an entry like this for every server you want to monitor
	$servers[] = (object) array(
		'id' => 1,					// The unique id
		'host' => '127.0.0.1',		// IP-address or host name of server
		'port' => 5000,				// XMLRPC port
		'authpw' => 'User',			// Password for 'User' authentication level
		'servername' => 'Name',		// Name to be displayed (no color parsing or the like)
	);
	
	$servers[] = (object) array(
		'id' => 2,
		'host' => 'localhost',
		'port' => 5005,
		'authpw' => 'Asdf',
		'servername' => 'Asdf-Clan FS 70k',
	);


?>