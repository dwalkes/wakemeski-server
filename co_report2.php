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
require_once('common.inc');

header( "Content-Type: text/plain" );

	$location = $_GET['location'];

	//first validate the location:
	if(!get_readable_location($location))
	{
		print "err.msg=invalid location: $location\n";
		exit(1);
	}

	$cache_file = 'co2_'.$location.'.txt';
	$found_cache = cache_available($cache_file);
	if( !$found_cache )
	{
		write_report($location);
	}

	cache_dump($cache_file, $found_cache);


function write_report($loc)
{
	global $cache_file;

	$reports = get_reports();
	$props = $reports[$loc];
	if( $props )
	{
		$props['location.info'] = get_details_url($loc);

		list($lat, $lon) = get_lat_lon($loc);
		Weather::set_props($lat, $lon, &$props);

		cache_create($cache_file, $props);
	}
	else
	{
		print("err.msg=No ski report data found\n");
	}
}

function get_reports()
{
	$contents = file_get_contents("http://snow.com/rssfeeds/snowreports.aspx");

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
	preg_match_all("/<title>(.*) Resort Snow Report - (.*) - (.*)<\/title/", $body, $matches, PREG_OFFSET_CAPTURE);
	$data['location'] = $matches[1][0][0];
	$data['date'] = $matches[3][0][0];

	preg_match_all("/New Snow in last 24 hours:\s+(\d+)/", $body, $matches, PREG_OFFSET_CAPTURE);
	$data['snow.daily'] = "Fresh(".$matches[1][0][0].")";
	$data['snow.fresh'] = $matches[1][0][0];
	$data['snow.units'] = 'inches';

	preg_match_all("/New Snow in last 48 hours:\s+(\d+)/", $body, $matches, PREG_OFFSET_CAPTURE);
	$data['snow.daily'] .= " 48hr(".$matches[1][0][0].")";

	preg_match_all("/Mid-Mountain:\s+(\d+)/", $body, $matches, PREG_OFFSET_CAPTURE);
	if( $matches[1][0][0] )
		$data['snow.total'] = $matches[1][0][0];
	else
		$data['snow.total'] = '?';

	preg_match_all("/Runs Open (\d+) of (\d+)/", $body, $matches, PREG_OFFSET_CAPTURE);
	$data['trails.open'] = $matches[1][0][0];
	$data['trails.total'] = $matches[2][0][0];

	preg_match_all("/Lifts Open (\d+) of (\d+)/", $body, $matches, PREG_OFFSET_CAPTURE);
	$data['lifts.open'] = $matches[1][0][0];
	$data['lifts.total'] = $matches[2][0][0];

	preg_match_all("/Snow Conditions: (.*?)&lt;/", $body, $matches, PREG_OFFSET_CAPTURE);
	if( $matches[1][0][0] )
		$data['snow.conditions'] = $matches[1][0][0];

	return $data;
}

//performs the reverse of get_readable_location
function get_location($loc)
{
	if( $loc == 'Vail')
		return 'VA';
	if( $loc == 'Beaver Creek')
		return 'BC';
	if( $loc == 'Keystone')
		return 'KS';
	if( $loc == 'Breckenridge')
		return 'BK';

	//hope this doesn't happen, but be graceful at the least
	return $loc;
}

/**
 * Turns a 3 digit code like ATA into Alta
 */
function get_readable_location($loc)
{
	if( $loc == 'VA')
		return 'Vail';
	if( $loc == 'BC')
		return 'Beaver Creek';
	if( $loc == 'KS')
		return 'Keystone';
	if( $loc == 'BK')
		return 'Breckenridge';
}

function get_details_url($loc)
{
	if( $loc == 'VA')
		return 'http://www.vail.com/';
	if( $loc == 'BC')
		return 'http://www.beavercreek.com';
	if( $loc == 'KS')
		return 'http://www.keystoneresort.com';
	if( $loc == 'BK')
		return 'http://www.breckenridge.com';
}

function get_lat_lon($loc)
{
	if( $loc == 'VA')
		return array(39.639423, -106.371);
	if( $loc == 'BC')
		return array(39.60253, -106.51711);
	if( $loc == 'KS')
		return array(39.60402, -105.954336);
	if( $loc == 'BK')
		return array(39.474249, -106.048871 );
}

?>
