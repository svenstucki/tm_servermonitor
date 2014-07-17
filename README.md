# Trackmania Servermonitor

This is quite an old project of mine, that I used to host on my no longer
existing website. It hasn't been updated for years and is still from before
Trackmania 2 first came out. There's a slight chance it works with TM 2 servers
too (since the API didn't change much) or can be easily adapted, but I won't do
that.

The old README is attached below.


# README


FAQ:

	- Will it run on Windows?
	No, it never will.
	- Can I run it on my free webspace?
	No.
	- Can I run it on a shared hosting?
	Maybe, this hardly depends on your hoster. Try it at your own risk.
	- I have problems running it.
	You can always use my hosted version on http://sm.dimension-servers.ch/.
	- How can I give something back?
	Improve the script and send it back to me. Or flame Nadeo for the FreeZone changes ;).


STEP-BY-STEP GUIDE:

You need to have rrdtool installed and in your path on your server. Type 'rrdtool' in the shell and you should see something like this:

	$ rrdtool
	RRDtool 1.3.1  Copyright 1997-2008 by Tobias Oetiker <tobi@oetiker.ch>
	               Compiled Sep 26 2008 22:25:10

	Usage: rrdtool [options] command command_options

	Valid commands: create, update, updatev, graph, graphv,  dump, restore,
			last, lastupdate, first, info, fetch, tune,
			resize, xport

	RRDtool is distributed under the Terms of the GNU General
	Public License Version 2. (www.gnu.org/copyleft/gpl.html)

	For more information read the RRD manpages


Have a look at the inc/rrd.inc.php file, maybe you need to adjust the settings there for your server. You probably don't have to change anything, but reading through this section won't hurt anyway.

	private static $cmd = 'rrdtool';

If there are any 'command not found' issues, try to specify the full path to rrdtool there. Type 'which rrdtool' in your shell to get it. It's /usr/bin/rrdtool on my debian.

	private static $mod = 'stat -c %%Y %s';

This is the command used to get the file modification dates.

	private static $fn = 'data/server_%d.rrd';
	private static $fng = 'data/graph_%d_%s.png';

These are the filename templates. The first one is for the rrd-files (database), the second one for the generated images. Note that your webserver will generate the images, so you need to give it write permissions to the directory.


Now add your servers to the config.inc.php file. Create an entry like this for every server you want to monitor:

	$servers[] = (object) array(
		'id' => 1,					// The id, must be exclusive
		'host' => '127.0.0.1',		// IP-address or host name of server
		'port' => 5001,				// XMLRPC port
		'authpw' => 'User',			// Password for 'User' authentication level
		'servername' => 'Name',		// Name to be displayed (no color parsing or the like)
	);

Should be quite self-explantory. The server id is just a random number (integer), be sure that you use each number only once. You have to fill out all those fields.


Now it's time for a short test run. Open a shell, navigate to the directory with the php files and type 'php -f test.php'. You'll see the current number of players on your server if it's working properly. If it isn't you'll get a bunch of error messages. In most cases they'll tell you what's wrong and you should be able to figure out the problem yourself. If you have no clue why it happens, be sure to include your config file (without passwords) and the full error message if you ask for help.

To test the cronjob, type 'php -f fetch.php' in your shell. It will take about 20 seconds and should produce no output. For every server you added, there should now be a database file. Check that. Then go to your browser and surf to 'http://path.to/files/index.php'. If the images aren't showing up, comment out the header() function in the graph.php file and go to 'http://path.to/files/graph.php?id=1', so you can see the error message.

	// header( "Content-type: image/png" );


The second but last step is to disable verbose mode, it's very easy, just change the top of config.inc.php to:

	define('VERBOSE', false);

And the last step is adding the cronjob. I won't go into details on how to do this, Google knows much better than me. The cronjob should run the command 'php -f /path/to/script/fetch.php' once per minute. That's it.


If you feel like modifying anything, go ahead! If you make it any better, feel free to send me the changes, I'm looking forward to it. I'd especially appreciate if someone takes the time to make a better color scheme, I'm not that good at this. If you have any comments, suggestions, etc. drop me a mail too.

