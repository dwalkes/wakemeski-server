<?php

	header( "Content-Type: text/plain" );
	
	$URL_BASE = 'http://bettykrocks.com/skireport';

	$region = $_GET['region'];
	if( !$region )
		show_regions();
	else
		show_locations($region);
		
function show_regions()
{
	print "Washington\n";
	print "Oregon\n";
}

function show_locations($region)
{
	global $URL_BASE;

	if( $region == "Washington")
	{
		print "Alpental = $URL_BASE/nwac_report.php?location=OSOALP\n";
		print "Crystal Mountain = $URL_BASE/nwac_report.php?location=OSOCMT\n";
		print "Hurricane Ridge  = $URL_BASE/nwac_report.php?location=OSOHUR\n";
		print "Mission Ridge = $URL_BASE/nwac_report.php?location=OSOMSR\n";
		print "Mt Baker = $URL_BASE/nwac_report.php?location=OSOMTB\n";
		print "Stevens Pass = $URL_BASE/nwac_report.php?location=OSOSK9\n";
		print "Snoqualmie Pass = $URL_BASE/nwac_report.php?location=OSOSNO\n";
		print "White Pass = $URL_BASE/nwac_report.php?location=OSOWPS\n";		
	}
	else if( $region == "Oregon")
	{
		print "Ski Bowl Ski Area, Government Camp = $URL_BASE/nwac_report.php?location=OSOGVT\n";
		print "Mt Hood Meadows  = $URL_BASE/nwac_report.php?location=OSOMHM\n";
		print "Timberline = $URL_BASE/nwac_report.php?location=OSOTIM\n"; 
	}
}
?>