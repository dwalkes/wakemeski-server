<?php
/*
 * Copyright (c) 2008 nombre.usario@gmail.com
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions
 * are met:
 * 1. Redistributions of source code must retain the above copyright
 *    notice, this list of conditions and the following disclaimer.
 * 2. Redistributions in binary form must reproduce the above copyright
 *    notice, this list of conditions and the following disclaimer in the
 *    documentation and/or other materials provided with the distribution.
 * 3. The name of the author may not be used to endorse or promote products
 *    derived from this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE AUTHOR ``AS IS'' AND ANY EXPRESS OR
 * IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES
 * OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED.
 * IN NO EVENT SHALL THE AUTHOR BE LIABLE FOR ANY DIRECT, INDIRECT,
 * INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT
 * NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF
 * THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 */

require_once('weather.inc');

	header( "Content-Type: text/plain" );

	$location = $_GET['location'];

	//first validate the location:
	if(!get_location_name($location))
	{
		print "err.msg=invalid location: $location\n";
		exit(1);
	}

	$found_cache = have_cache($location);
	if( !$found_cache )
	{
		write_report($location);
	}

	$cache_file = "nm_$location.txt";
	if( is_readable($cache_file) )
	{
		print file_get_contents("nm_$location.txt");
		print "cache.found=$found_cache\n";
	}
	else
	{
		print "err.msg=No ski report data found\n";
	}

function have_cache($location)
{
	$file = "nm_$location.txt";
	if( is_readable($file))
	{
		//get modification time stamp. If its less than
		//120 minutes old, use that copy
		$mod = filemtime($file);
		if( time() - $mod < 7200 ) //=60*120 = 120 minutes
		{
			return 1;
		}
	}
	return 0;
}

function write_report($loc)
{
	$reports = get_reports();
	$report = $reports[$loc];
	if( $report )
	{
		$fp = fopen("nm_$loc.txt", "w");
		
		$keys = array_keys($report);
		for($j = 0; $j < count($keys); $j++)
		{
			$key = $keys[$j];
			fwrite($fp, $key.' = '.$report[$key]."\n");
		}
		fwrite($fp, "location.info=".get_details_url($loc)."\n");
		list($lat, $lon) = get_lat_lon($loc);
        list($icon, $url) = Weather::get_report($lat, $lon);
		fwrite($fp, "location.latitude=$lat\n");
		fwrite($fp, "location.longitude=$lon\n");
		fwrite($fp, "weather.url=$url\n");
		fwrite($fp, "weather.icon=$icon\n");
		
		fclose($fp);
	}
}

function get_reports()
{
	$contents = file_get_contents("http://skinewmexico.com/snow_reports/feed.rss");
	
	//each location is in an <item> tag
	$locations = preg_split("/<item>/", $contents);
	//the first item is header junk we can ignore
	array_shift($locations);

	$reports = array();
	for($i = 0; $i < count($locations); $i++)
	{
		$report = get_report($locations[$i]);
		$loc = get_location($report['location']);
		$reports[$loc] = $report;
	}	
	
	return $reports;
}

function get_report($body)
{
	$data = array();
	preg_match_all("/<h1>(.*)<\/h1/", $body, $matches, PREG_OFFSET_CAPTURE);
	$data['location'] = $matches[1][0][0];
	
	preg_match_all("/<pubDate>(.*)<\/pubDate>/", $body, $matches, PREG_OFFSET_CAPTURE);	
	$data['date'] = $matches[1][0][0];
	
	preg_match_all("/New Natural Snow Last 48 Hours: <b>(\d+)/", $body, $matches, PREG_OFFSET_CAPTURE);
	if( $matches[1][0][0] )
		$data['snow.daily'] = $matches[1][0][0];
	else
		$data['snow.daily'] = 'none';
		
	preg_match_all("/Base Snow Depth \(inches\): <b>(.*?)&quot;/", $body, $matches, PREG_OFFSET_CAPTURE);
	if( $matches[1][0][0] )
		$data['snow.total'] = $matches[1][0][0];
	else
		$data['snow.total'] = '?';
		
	preg_match_all("/Trails Open: <b>(\d+)/", $body, $matches, PREG_OFFSET_CAPTURE);
	if( $matches[1][0][0] )
		$data['trails.open'] = $matches[1][0][0];
	else
		$data['trails.open'] = 0;

	preg_match_all("/Lifts Open: <b>(\d+)/", $body, $matches, PREG_OFFSET_CAPTURE);
	if( $matches[1][0][0] )
		$data['lifts.open'] = $matches[1][0][0];
	else
		$data['lifts.open'] = 0;

	preg_match_all("/Surface Cond&#58; (.*?)<\/title>/", $body, $matches, PREG_OFFSET_CAPTURE);
	if( $matches[1][0][0] )
		$data['snow.conditions'] = $matches[1][0][0];

	return $data;
}

function get_details_url($loc)
{
	if( $loc == 'AF')
		return 'http://www.angelfireresort.com/winter/mountain-snow-report.php';
	if( $loc == 'EF')
		return 'http://www.enchantedforestxc.com/';
	if( $loc == 'PM')
		return 'http://www.skipajarito.com/conditions.php';
	if( $loc == 'RR')
		return 'http://redriverskiarea.com/page.php?pname=mountain/snow';
	if( $loc == 'SP')
		return 'http://www.sandiapeak.com/index.php?page=snow-report';
	if( $loc == 'SI')
		return 'http://www.sipapunm.com/index.php?option=com_snowreport&view=helloworld&Itemid=73';
	if( $loc == 'SA')
		return 'http://www.skiapache.com/';
	if( $loc == 'SF')
		return 'https://www.skisantafe.com/index.php/snow_report';
	if( $loc == 'TS')
		return 'http://www.skitaos.org/snow_reports/index';
	if( $loc == 'VC')
		return 'http://www.vallescaldera.gov/comevisit/skisnow/';
}

function get_lat_lon($loc)
{
	if( $loc == 'AF')
		return array(36.3903, -105.2875);
	if( $loc == 'EF')
		return array(36.7063, -105.4053);
	if( $loc == 'PM')
		return array(35.895129, -106.391785);
	if( $loc == 'RR')
		return array(36.70859, -105.409924 );
	if( $loc == 'SP')
		return array(35.207831, -106.41354 );
	if( $loc == 'SI')
		return array(36.153595, -105.54824);
	if( $loc == 'SA')
		return array(33.397455, -105.789198);
	if( $loc == 'SF')
		return array(35.796793, -105.80166);
	if( $loc == 'TS')
		return array(36.35, -105.27);
	if( $loc == 'VC')
		return array(35.9, -106.55);
}

//these values are tied to the values defined in location_finder.php
function get_location_name($code)
{
	if( $code == 'AF' ) return 'Angel Fire';
	if( $code == 'EF' )	return "Enchanted Forest";
	if( $code == 'PM' )	return "Pajarito Mountain";
	if( $code == 'RR' )	return "Red River";
	if( $code == 'SP' )	return "Sandia Peak";
	if( $code == 'SI' )	return "Sipapu";
	if( $code == 'SA' )	return "Ski Apache";
	if( $code == 'SF' )	return "Ski Santa Fe";
	if( $code == 'TS' )	return "Taos";
	if( $code == 'VC' )	return "Valles Caldera Nordic";

	return '';
}

//these values are tied to the values defined in location_finder.php
function get_location($name)
{
	if( strstr($name, "Angel Fire") ) return "AF";
	if( strstr($name, "Enchanted Forest") ) return "EF";
	if( strstr($name, "Pajarito Mountain") ) return "PM";
	if( strstr($name, "Red River") ) return "RR";
	if( strstr($name, "Sandia Peak") ) return "SP";
	if( strstr($name, "Sipapu") ) return "SI";
	if( strstr($name, "Ski Apache") ) return "SA";
	if( strstr($name, "Ski Santa Fe") ) return "SF";
	if( strstr($name, "Taos") ) return "TS";
	if( strstr($name, "Valles Caldera") ) return "VC";

	return '';
}

?>
