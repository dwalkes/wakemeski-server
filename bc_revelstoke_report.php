<?php
/*
 * Copyright (c) 2010 Andy Doan, Dan Walkes
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

require_once('bc.inc');

header( "Content-Type: text/plain" );

	$location = $_GET['location'];

	$resorts = resorts_bc_get();
	$resort = resort_get_location($resorts, $location);

	$resort->fresh_source_url = $resort->data;
	
	$cache_file = 'bc_'.$location.'.txt';
	$found_cache = cache_available($resort,$cache_file);
	if( !$found_cache )
	{
		write_report($resort, $cache_file);
	}

	cache_dump($cache_file, $found_cache);

	log_hit('bc_revelstoke_report.php', $location, $found_cache);

function write_report($resort, $cache_file)
{
	$content = get_report($resort);
	if( $content )
	{
		$props = get_report_props($resort, $content);
		cache_create($resort, $cache_file, $props);
	}
}

/**
 * Takes the report's HTML and parse the information out into a hashtable
 */
function get_report_props($resort, $report)
{
	$props = array();

	$props['snow.units'] = 'cm';

	$props['details.url'] = $resort->fresh_source_url;

	preg_match("/updated (.*?)</", $report, $matches);
	$props['date'] = $matches[1];

	$new = find_int("/Overnight(.*?)(\d+)\s+cm/", $report, 2);
	$hr24 = find_int("/Last 24 Hours(.*?)(\d+)\s+cm/", $report, 2);
	$hr48 = find_int("/Last 48 Hours(.*?)(\d+)\s+cm/", $report, 2);
	$week = find_int("/Last 7 Days(.*?)(\d+)\s+cm/", $report, 2);

	$props['snow.fresh'] = $hr24;
	$props['snow.daily'] = "Fresh($new) 24hr($hr24) 48hr($hr48) week($week)";

	$props['snow.total'] = find_int("/Base Depth(.*?)(\d+)\s+cm/", $report, 2);

	$props['temp.readings'] = find_int("/Mountain Top Temp: <\/strong>&nbsp;(.*?)C/", $report);
	
	if( preg_match("/Runs Open:(.*?)(\d+)\/(\d+)/", $report, $matches) )
	{
		$props['trails.open'] = $matches[2];
		$props['trails.total'] = $matches[3];
	}

	get_weather_props(&$props);	

	return $props;
}

function get_weather_props($props)
{
	$props['weather.url'] = "http://www.weatheroffice.gc.ca/city/pages/bc-65_metric_e.html";
	$report = get_weather_report($props);

	get_weather_icon($report, &$props);

	//remove the contents up to the actual weather so that we can get
	//the proper list:
	$report = strstr($report, "<div class=\"fdetails\">");

	preg_match_all("/<dt>(.*?)<\/dt>\s+<dd>(.*?)<\/dd>/", $report, $matches);

	for($i = 0; $i < 3; $i++)
	{
		$props['weather.forecast.when.'.$i] = $matches[1][$i];
		$props['weather.forecast.desc.'.$i] = $matches[2][$i];
	}
}

function get_weather_icon($report, $props)
{
	if(preg_match("/id=\"currentimg\" src=\"\/weathericons\/(.*?).gif/", $report, $matches) )
		$props['weather.icon'] = bc_get_weather_icon($matches[1]);
}

function get_weather_report($props)
{
	$contents = file_get_contents($props['weather.url']);
	
	//its easier to parse if one line
	return str_replace("\n", "\t", $contents);
}

function get_report($resort)
{
	$contents = file_get_contents($resort->fresh_source_url);

	//the report has fields we grep for that span lines, so remove
	//EOL's so regex's will work easily
	return str_replace("\n", "\t", $contents);
}

?>
