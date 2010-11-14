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

require_once('co.inc');
require_once('nv.inc');

header( "Content-Type: text/plain" );

	$location = $_GET['location'];
	
	$resorts = array_merge(resorts_co_get(),resorts_nv_co_report2_get());
	
	$resort = resort_get_location($resorts, $location);

	$resort->fresh_source_url = "http://snow.com/rssfeeds/snowreports.aspx";
	
	$cache_file = 'co2_'.$location.'.txt';
	$found_cache = cache_available($cache_file);
	if( !$found_cache )
	{
		write_report($resort, $cache_file);
	}

	cache_dump($cache_file, $found_cache);

	log_hit('co_report2.php', $location, $found_cache);

function write_report($resort, $cache_file)
{
	$report = get_report($resort);
	if( $report )
		cache_create($resort, $cache_file, $report);
}

function get_report($resort)
{
	global $resorts;
	$contents = file_get_contents($resort->fresh_source_url);

	//each location is in an <item> tag
	$locations = preg_split("/<item>/", $contents);
	//the first item is header junk we can ignore
	array_shift($locations);

	for($i = 0; $i < count($locations); $i++)
	{
		$report = get_report_props($locations[$i]);
		if( $report['location'] == $resort->name )
			return $report;
	}

	return false;
}

function get_report_props($body)
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


?>
