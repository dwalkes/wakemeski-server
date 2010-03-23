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
	if(!get_readable_location($location))
	{
		print "err.msg=invalid location: $location\n";
		exit(1);
	}

	$found_cache = have_cache($location);
	if( !$found_cache )
	{
		write_report($location);
	}

	print file_get_contents("co_$location.txt");
	print "cache.found=$found_cache\n";

function have_cache($location)
{
	$file = "co_$location.txt";
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
	$fp = fopen("co_$loc.txt", 'w');

	fwrite($fp, "location =  $loc\n");

	$readable = get_readable_location($loc);
	$report = get_location_report($readable);
	if( $report )
	{
		$props = get_report_props($report);
		$keys = array_keys($props);
		for($i = 0; $i < count($keys); $i++)
		{
			$key = $keys[$i];
			fwrite($fp, $key.' = '.$props[$key]."\n");
		}

		list($lat, $lon) = get_lat_lon($loc);
		list($icon, $url) = Weather::get_report($lat, $lon);
		fwrite($fp, "location.latitude=$lat\n");
		fwrite($fp, "location.longitude=$lon\n");
		fwrite($fp, "weather.url=$url\n");
		fwrite($fp, "weather.icon=$icon\n");
	}
	else
	{
		fwrite($fp, "err.msg=No ski report data found\n");
	}

	fclose($fp);
}

/**
 * Takes the report's XML node and parse the information out into a hashtable
 */
function get_report_props($report)
{
	$props = array();
	$data = $report->getElementsByTagName('description')->item(0)->nodeValue;

	preg_match_all("/New Snow Last 24 Hours: (\d+)/", $data, $matches, PREG_OFFSET_CAPTURE);
	$day = $matches[1][0][0];
	preg_match_all("/New Snow Last 48 hours: (\d+)/", $data, $matches, PREG_OFFSET_CAPTURE);
	$yesterday = $matches[1][0][0];
	$props['snow.daily'] = "Fresh($day) 48hr($yesterday)";
	$props['snow.fresh'] = $day;
	$props['snow.units'] = 'inches';
	
	preg_match_all("/Mid-Mountain Depth: (\d+)/", $data, $matches, PREG_OFFSET_CAPTURE);
	$props['snow.total'] = $matches[1][0][0];
	
	preg_match_all("/Lifts Open: (\d+)\/(\d+)/", $data, $matches, PREG_OFFSET_CAPTURE);
	$props['lifts.open'] = $matches[1][0][0];
	$props['lifts.total'] = $matches[2][0][0];
	
	preg_match_all("/Surface Conditions: (.*?)<br/", $data, $matches, PREG_OFFSET_CAPTURE);
	if( $matches[1][0][0] )
		$props['snow.conditions'] = $matches[1][0][0];

	$date = $report->getElementsByTagName('pubDate')->item(0)->nodeValue;
	$date = strtotime($date);
	$props['date'] = date("h:ia M j", $date);

	$props['details.url'] = $report->getElementsByTagName('link')->item(0)->nodeValue;

	return $props;
}

/**
 * Returns the XML node containing the report for a given location or false
 * if one is not found
 */
function get_location_report($loc)
{
	$dom = get_report_xml();

	$items = $dom->getElementsByTagName('item');
	for ($i = 0; $i < $items->length; $i++)
	{
		$title_node = $items->item($i)->getElementsByTagName('title')->item(0);
		$title = trim($title_node->firstChild->nodeValue);
		if(preg_match("/$loc/", $title) )
		{
		    return $items->item($i);
		}
	}

	return false;
}

function get_report_xml()
{
	$xml = file_get_contents("http://feeds.feedburner.com/snowreport");
	$sxe = simplexml_load_string($xml);
	return dom_import_simplexml($sxe);
}

/**
 * Turns a 2 digit code like AB into Arapohoe Basin
 */
function get_readable_location($loc)
{
	if( $loc == 'AB')
		return 'Arapahoe Basin';
    if( $loc =='AH' )
        return 'Aspen Highlands';
    if( $loc =='AM' )
        return 'Aspen Mountain';
    if( $loc =='BM' )
        return 'Buttermilk';
    if( $loc =='CM' )
        return 'Copper Mountain';
    if( $loc =='CB' )
        return 'Crested Butte';
    if( $loc =='EM' )
        return 'Echo Mountain';
    if( $loc =='EL' )
        return 'Eldora';
    if( $loc =='HW' )
        return 'Howelsen';
    if( $loc =='LV' )
        return 'Loveland';
    if( $loc =='MM' )
        return 'Monarch Mountain';
    if( $loc =='PH' )
        return 'Powderhorn';
    if( $loc =='PG' )
        return 'Purgatory';
    if( $loc == 'SM')
        return 'Silverton Mountain';
    if( $loc == 'SC')
        return 'Ski Cooper';
    if( $loc == 'SN')
        return 'Snowmass';
    if( $loc == 'SV')
        return 'Sol Vista Basin';
    if( $loc == 'ST')
        return 'Steamboat';
    if( $loc == 'SL')
        return 'Sunlight';
    if( $loc == 'TD')
        return 'Telluride';
    if( $loc == 'WP')
        return 'Winter Park';
    if( $loc == 'WC')
        return 'Wolf Creek';

	//hope this doesn't happen, but be graceful at the least
	return $loc;
}

function get_lat_lon($loc)
{
    if( $loc == 'AB')
		return array(39.6448, -105.871);
    if( $loc =='AH' )
        return array(39.181711, -106.856121);
    if( $loc =='AM' )
        return array(39.18428, -106.821903);
    if( $loc =='BM' )
        return array(39.205167, -106.859294);
    if( $loc =='CM' )
        return array(39.4944, -106.138732);
    if( $loc =='CB' )
        return array(38.899932, -106.964249);
    if( $loc =='EM' )
        return array(37.591389, -107.571726);
    if( $loc =='EL' )
        return array(39.937341, -105.5853);
    if( $loc =='HW' )
        return array(40.480533, -106.840605);
    if( $loc =='LV' )
        return array(39.680191, -105.898114);
    if( $loc =='MM' )
        return array(38.512285, -106.332957);
    if( $loc =='PH' )
        return array(39.068912, -108.15068);
    if( $loc =='PG' )
        return array(37.629261, -107.815288);
    if( $loc == 'SM')
        return array(37.791067, -107.666171);
    if( $loc == 'SC')
        return array(39.358897, -106.299256);
    if( $loc == 'SN')
        return array(39.162132, -106.787847);
    if( $loc == 'SV')
        return array(40.04784, -105.898969);
    if( $loc == 'ST')
        return array(40.458905, -106.802092);
    if( $loc == 'SL')
        return array(39.398121, -107.339174);
    if( $loc == 'TD')
        return array(37.9392, -107.8163);
    if( $loc == 'WP')
        return array(39.886791, -105.764279);
    if( $loc == 'WC')
        return array(37.472654, -106.793116);
}
?>
