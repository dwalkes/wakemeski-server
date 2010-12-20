<?php
/*
 * Copyright (c) 2008 nombre.usario@gmail.com
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions
 * are met:
 * 1. Redistributions of source code must retain the above copyright
 *	  notice, this list of conditions and the following disclaimer.
 * 2. Redistributions in binary form must reproduce the above copyright
 *	  notice, this list of conditions and the following disclaimer in the
 *	  documentation and/or other materials provided with the distribution.
 * 3. The name of the author may not be used to endorse or promote products
 *	  derived from this software without specific prior written permission.
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

require_once('eu.inc');

header( "Content-Type: text/plain" );

	$location = $_GET['location'];

	$resorts = resorts_eu_get();
	$resort = resort_get_location($resorts, $location);

	if( $resort->data )
		$resort->info = $resort->data;
		
	$cache_file = 'eu_'.$location.'.txt';
	$found_cache = cache_available($cache_file);
	if( !$found_cache )
	{
		write_report($resort, $cache_file);
	}

	cache_dump($cache_file, $found_cache);

	log_hit('eu_report.php', $location, $found_cache);

function write_report($resort, $cache_file)
{
	$report = get_report($resort->fresh_source_url);
	if( $report )
	{
		$props = get_report_props($resort->fresh_source_url, $report);

		if( $resort->lat > 0 )
		{
			$props['location.latitude'] = $resort->lat;
			$props['location.longitude'] = $resort->lon;
		}
		cache_create($resort, $cache_file, $props);
	}
}

/**
 * Takes the report's XML node and parse the information out into a hashtable
 */
function get_report_props($url, $report)
{
	$props = array();

	preg_match_all("/<th>Upper<\/th><td>(.*?)<\/td>/", $report, $matches, PREG_OFFSET_CAPTURE);
	$upper = $matches[1][0][0];
	preg_match_all("/<th>Lower<\/th><td>(.*?)<\/td>/", $report, $matches, PREG_OFFSET_CAPTURE);
	$lower = $matches[1][0][0];
	$props['snow.total'] = "Lower ($lower), Upper($upper)";

	$num_matches = preg_match_all("/<th>Fresh Snow<\/th><td>(\d+)/", $report, $matches, PREG_OFFSET_CAPTURE);

	$fresh = $matches[1][0][0];
	
	if( $num_matches == 0 ) 
	{
		// if no matches were found looking for ints, look for a single - instead.  
		// This means 0 fresh snow in this report.
		$num_matches = preg_match_all("/<th>Fresh Snow<\/th><td>-/", $report, $matches, PREG_OFFSET_CAPTURE);
		if( $num_matches > 0 ) 
		{
			$fresh = "0";
		}
	}

	$props['snow.daily'] = "Fresh(".$fresh."cm)";
	$props['snow.fresh'] = $fresh;
	$props['snow.units'] = 'cm';

	// Lifts open is reported as %
	if( preg_match_all("/<th>Lifts Open<\/th><td>(.*?)<\/td>/", $report, $matches, PREG_OFFSET_CAPTURE) )
		$props['lifts.percent.open'] = $matches[1][0][0];

	//some places report "Pistes Open". Some do "area open"
	if( preg_match_all("/<th>Pistes Open<\/th><td>(.*?)<\/td>/", $report, $matches, PREG_OFFSET_CAPTURE) )
		$props['trails.open'] = $matches[1][0][0];
	if( preg_match_all("/<th>Area Open<\/th><td>(.*?)<\/td>/", $report, $matches, PREG_OFFSET_CAPTURE) )
		$props['trails.percent.open'] = $matches[1][0][0];

	preg_match_all("/<th>Snow<\/th><td>(.*?)<\/td>/", $report, $matches, PREG_OFFSET_CAPTURE);
	$props['snow.conditions'] = $matches[1][0][0];

	preg_match_all("/reported (.*?)\.<\/p>/", $report, $matches, PREG_OFFSET_CAPTURE);
	$props['date'] = date('d M', strtotime($matches[1][0][0]));

	preg_match_all("/<p><a href=\"(.*?)\.html/", $report, $matches, PREG_OFFSET_CAPTURE);	
	$page = $matches[1][0][0].".html";
	//this gives us chamonix_snow-forecast.html, now pull the base from the
	//report's url
	$idx = strrpos($url, '/');
	$base = substr($url, 0, $idx);
	$props['weather.url'] = $base."/".$page;

	$props['weather.icon'] = get_weather_icon($props['weather.url']);

	return $props;
}

function get_report($url)
{
//	return file_get_contents($url);
return file_get_contents("http://www.j2ski.mobi/austria/molltal_gletscher_flattach_snow-report.html");
}

function get_weather_icon($url)
{
	$weather = get_report($url);
	preg_match_all("/<img alt=\"(.*?)\"/", $weather, $matches, PREG_OFFSET_CAPTURE);

	switch(strtolower($matches[1][0][0]))
	{
		case 'sunny/clear':
			return 'skc';
		case 'fair': //cloud with sun
			return 'sct';
		case 'light snow':
			return 'mix';
		case 'snow':
		case 'heavy snow':
			return 'blizzard';
		case 'cloudy':
			return 'ovc';
		case 'partly cloudy': //little sun most cloud
			return 'bkn';
		case 'light rain':
		case 'rain':
			return 'ra';
	}
	return $matches[1][0][0];
}

?>
