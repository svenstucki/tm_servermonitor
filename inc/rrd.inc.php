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
	
	
	class smrrd {
		
		// Path to rrdtool
		private static $cmd = 'rrdtool';
		// Filename template for server rrds (%d = id)
		private static $fn = 'data/server_%d.rrd';
		// Command to get last file modification date
		private static $mod = 'stat -c %%Y %s';
		// Filename template for graphs (%d = id, %s = period)
		private static $fng = 'data/graph_%d_%s.png';
		
		// Create a new round robin database for id
		public static function create( $id ) {
			chdir( dirname(__FILE__)."/../" );
			$fn = sprintf( self::$fn, $id );
			$r = exec(
				self::$cmd . " create {$fn} --start N --step 60"
				." DS:players:GAUGE:90:0:255"
				." DS:spectators:GAUGE:90:0:255"
				." DS:game_mode:GAUGE:90:-1:5"
				." DS:protected:GAUGE:90:-1:1"
				." RRA:MAX:0.5:1:1440"		// 1min max / 1day
				." RRA:MAX:0.5:15:672"		// 15min max / 7days
				." RRA:MAX:0.5:30:1440"		// 30min max / 30days
				." RRA:MAX:0.5:60:8760"		// 60min max / 365days
			);
			if( $r != "" )
				throw new Exception( "Error creating rrd for server {$id} ('{$r}')." );
		}
		
		// Insert new entries into the rrd (first paramter is server id, the rest will be used as arguments to rrdtool)
		public static function update() {
			chdir( dirname(__FILE__)."/../" );
			$args = func_get_args();
			foreach($args as &$arg)
				$arg = (int) $arg;
			$id = array_shift( $args );
			
			// Check if rrd exists, create it if not
			$fn = sprintf( self::$fn, $id );
			if( !file_exists( $fn ) )
				self::create( $id );
			
			$r = exec( self::$cmd . " update {$fn} N:" . implode( ":", $args ) );
			if( $r != "" )
				throw new Exception( "Error updating rrd of server {$id} ('{$r}')." );
		}
		
		// Create graphs or get latest version
		public static function graph( $server, $period="day" ) {
			
			if( !in_array( $period, array("day","week","month","year") ) )
				throw new InvalidArgumentException( "Invalid period '{$period}'." );
			
			$id = (int)$server->id;
			$end = time();
			$fn = sprintf( self::$fng, $id, $period );
			$rrd = sprintf( self::$fn, $id );
			chdir( dirname(__FILE__)."/../" );
			if( file_exists($fn) ) {
				$modified = (int) exec( sprintf( self::$mod, $fn ) );
				switch( $period ) {
					case "year":
						$max_age = 60;
						break;
					case "month":
						$max_age = 30;
						break;
					case "week":
						$max_age = 15;
						break;
					case "day":
						$max_age = 1;
						break;
				}
				// Don't regraph if not older than {$max_age} minutes
				if( $end - $modified < $max_age*60 )
					return $fn;
			}
			if( !file_exists($rrd) )
				throw new InvalidArgumentException( "The rrd '{$rrd}' has not yet been created." );
			
			// Get start time
			switch( $period ) {
				case "year":
					$start = $end - 365*24*60*60;
					$title = "This Year (60min max)";
					break;
				case "month":
					$start = $end - 31*24*60*60;
					$title = "This Month (30min max)";
					break;
				case "week":
					$start = $end - 7*24*60*60;
					$title = "This Week (15min max)";
					break;
				case "day":
					$start = $end - 24*60*60;
					$title = "Today";
					break;
			}
			
			// Create graph
			$cmd = self::$cmd . " graph {$fn}"
				." --start {$start}"
				." --end {$end}"
				." --title=" . escapeshellarg($server->servername." - Player Graph") . ""
				." --vertical-label Connections"
				// Data imports
				." DEF:players={$rrd}:players:MAX"
				." DEF:spectators={$rrd}:spectators:MAX"
				." DEF:game_mode={$rrd}:game_mode:MAX"
				." DEF:protected={$rrd}:protected:MAX"
				.' COMMENT:"'.$title.'"'
				// GameMode Area preparation
				.' CDEF:plmax=players,spectators,MAX,INF,MAX'
				.' CDEF:rounds=game_mode,0,EQ,plmax,UNKN,IF'
				.' CDEF:timeattack=game_mode,1,EQ,plmax,UNKN,IF'
				.' CDEF:team=game_mode,2,EQ,plmax,UNKN,IF'
				.' CDEF:laps=game_mode,3,EQ,plmax,UNKN,IF'
				.' CDEF:stunts=game_mode,4,EQ,plmax,UNKN,IF'
				.' CDEF:cup=game_mode,5,EQ,plmax,UNKN,IF'
				// Offline Area
				.' CDEF:offline=game_mode,-1,EQ,INF,UNKN,IF'
				.' AREA:offline#FFFFFF:"Offline"'
				// GameMode Areas
				.' AREA:rounds#CCCCFF:"Rounds"'
				.' AREA:timeattack#CC9900CC:"TimeAttack"'
				.' AREA:team#99FF33:"Team"'
				.' AREA:laps#999900:"Laps"'
				.' AREA:stunts#CC3399:"Stunts"'
				.' AREA:cup#CCCC33:"Cup"'
				// Player/Spectator line
				.' LINE2:spectators#0000FF:"Spectators"'
				.' LINE2:players#FF0000:"Players"'
				.'';
			exec( $cmd );
			return $fn;
			
		}
		
	}
	
	
?>