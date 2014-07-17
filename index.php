<html>
	<head>
		<title>ServerMonitor</title>
		<style type="text/css">
			img {
				margin: 2px;
			}
		</style>
	</head>
	
	<body>
		
		<h2>Monitored Servers</h2>

<?php


	// hasdf
	
	
	require_once( "inc/config.inc.php" );
		
	
	// Print "menu"
	echo '<a href="?p=day">Day</a> - <a href="?p=week">Week</a> - <a href="?p=month">Month</a> - <a href="?p=year">Year</a> Stats <br /><br />';
	
	// Print list
	$p = htmlspecialchars( $_GET['p'] );
	if( $p == "" )
		$p = "day";
	echo '<p>Showing stats from this '.$p.'.</p>';
	$nl = false;
	foreach( $servers as $s ) {
		echo '<img src="graph.php?id='.$s->id.'&period='.$p.'" />';
		if( $nl ) {
			echo ' <br />'; $nl=false;
		} else {
			echo ''; $nl=true;
		}
	}
	
	
?>

	</body>
</html>