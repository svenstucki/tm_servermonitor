<?php


	/*

	Copyright (c) 2011, Sven Stucki
	All rights reserved.

	Redistribution and use in source and binary forms, with or without modification, are permitted provided that the following conditions are met:
		* Redistributions may not be sold, nor may they be used in a commercial product or activity.
		* Redistributions of source code and/or in binary form must reproduce the above copyright notice, this list of conditions and the following disclaimer in the documentation and/or other materials provided with the distribution.
		* Neither the name of Sven Stucki nor the names of its contributors may be used to endorse or promote products derived from this software without specific prior written permission.

	THIS SOFTWARE IS PROVIDED BY SVEN STUCKI "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
	LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN
	NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL,
	EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR
	SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF
	LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY
	WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.

	*/
	
	
	chdir( dirname(__FILE__) );
	require_once( "inc/GbxRemote.inc.php" );
	require_once( "inc/rrd.inc.php" );
	require_once( "inc/config.inc.php" );
	
	
	// Maximum number of threads
	define( "THREAD_MAX", 15 );
	// Jobs per thread
	define( "THREAD_SERVER", 5 );
	
	
	// Do the work
	for( $i=0; $i<count($servers); $i+=THREAD_SERVER ) {
		if( ($i / THREAD_SERVER) >= THREAD_MAX )
			die( "Capacity limit!" );
		$pid = pcntl_fork();
		if( $pid == -1 ) {
			die( "Error while forking!" );
		} else if( $pid ) {
			// I am the parent
			out( "Child started with pid: {$pid}." );
		} else {
			// I am the child
			$to = min( count($servers), $i+THREAD_SERVER );
			out( "Child here, caring for servers {$i} to {$to}." );
			for( $j=$i; $j<$to; $j++ ) {
				// Connect to server
				$server = $servers[$j];
				$client = new IXR_Client_Gbx;
				if( !$client->InitWithIp( $server->host, (int)$server->port ) ) {
					out( "Error connecting to server {$server->host} on port {$server->port}." );
					$game_mode = -1;
					$password = -1;
					$players = 0;
					$spectators = 0;
				} else {
					if( !$client->query( "Authenticate", "User", $server->authpw ) ) {
						out( "Can't authenticate on server {$j}." );
						continue;
					}
					// Get game mode
					$client->query( "GetGameMode" );
					$game_mode = $client->getResponse();
					// Get password
					$client->query( "GetServerPassword" );
					$password = $client->getResponse();
					if( strtolower($password) == "no password" )
						$password = "";
					if( $password == "" )
						$password = 0;
					else
						$password = 1;
					// Get playerlist
					$client->query( "GetPlayerList", 255, 0, 2 );
					$playerlist = $client->getResponse();
					$players = 0; $spectators = 0;
					foreach( $playerlist as &$pl ) {
						// Skip relayed players
						if( floor(($pl['Flags'] % 100000)/10000) > 0 )
							continue;
						// Skip servers
						if( floor(($pl['Flags'] % 1000000)/100000) > 0 )
							continue;
						// Count players/spectators
						if( $pl['SpectatorStatus'] % 10 > 0 )
							$spectators++;
						else
							$players++;
					}
				}
				// Execute rrdtool
				try {
					smrrd::update( (int)$server->id, $players, $spectators, $game_mode, $password );
				} catch( Exception $e ) {
					error( $e->getMessage() );
				}
			}
			// Avoid cleanup with SIGKILL
			posix_kill( getmypid(), 9 );
		}
	}
	sleep(20);
	
	
	function out( $msg, $eol=true ) {
		if( !VERBOSE )
			return;
		echo $msg;
		if( $eol )
			echo PHP_EOL;
	}
	
	function error( $msg ) {
		echo "ERROR: {$msg}" . PHP_EOL;
	}


?>