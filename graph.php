<?php


	// Trackmania ServerMonitor
	// by Sven Stucki - www.svenstucki.ch - tm AT svenstucki ch
	
	
	require_once( "inc/config.inc.php" );
	require_once( "inc/rrd.inc.php" );


	$period = $_GET['period'];
	if (empty($period))
		$period = "day";
	$id = (int)$_GET['id'];
	

	foreach ($servers as $server) {
		if ($server->id == $id)
			break;
	}
	if ($server->id != $id)
		die ('There\'s no server with this id.');
	
	
	$fn = smrrd::graph( $server, $period );

	header( "Content-type: image/png" );
	echo file_get_contents( $fn );
	
	
?>