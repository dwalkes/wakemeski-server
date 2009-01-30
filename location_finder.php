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
	print "New Mexico\n";
	print "Oregon\n";
	print "Utah\n";
	print "Washington\n";
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
	else if ( $region == "Utah" )
	{
		print "Alta = $URL_BASE/utah_report.php?location=ATA\n";
		print "Beaver Mountain = $URL_BASE/utah_report.php?location=BVR\n";
		print "Brian Head = $URL_BASE/utah_report.php?location=BHR\n";
		print "Brighton = $URL_BASE/utah_report.php?location=BRT\n";
		print "The Canyons = $URL_BASE/utah_report.php?location=CNY\n";
		print "Deer Valley = $URL_BASE/utah_report.php?location=DVR\n";
		print "Park City = $URL_BASE/utah_report.php?location=PCM\n";
		print "Powder Mountain = $URL_BASE/utah_report.php?location=POW\n";
		print "Snowbasin = $URL_BASE/utah_report.php?location=SBN\n";
		print "Sunbird = $URL_BASE/utah_report.php?location=SBD\n";
		print "Solitude = $URL_BASE/utah_report.php?location=SOL\n";
		print "Sundance = $URL_BASE/utah_report.php?location=SUN\n";
		print "Wolf Creek = $URL_BASE/utah_report.php?location=WLF\n";
	}
	else if ( $region == "New Mexico" )
	{
		print "Angel Fire = $URL_BASE/nm_report.php?location=AF\n";
		print "Enchanted Forest = $URL_BASE/nm_report.php?location=EF\n";
		print "Pajarito Mountain = $URL_BASE/nm_report.php?location=PM\n";
		print "Red River = $URL_BASE/nm_report.php?location=RR\n";
		print "Sandia Peak = $URL_BASE/nm_report.php?location=SP\n";
		print "Sipapu = $URL_BASE/nm_report.php?location=SI\n";
		print "Ski Apache = $URL_BASE/nm_report.php?location=SA\n";
		print "Ski Santa Fe = $URL_BASE/nm_report.php?location=SF\n";
		print "Taos = $URL_BASE/nm_report.php?location=TS\n";
		print "Valles Caldera Nordic = $URL_BASE/nm_report.php?location=VC\n";
	}
}
?>