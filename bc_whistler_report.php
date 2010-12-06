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
	
	$cache_file = 'bc_'.$location.'.txt';
	$found_cache = cache_available($cache_file);
	if( !$found_cache )
	{
		write_report($resort, $cache_file);
	}

	cache_dump($cache_file, $found_cache);

	log_hit('bc_whistler_report.php', $location, $found_cache);

function write_report($resort, $cache_file)
{
	$content = get_report($resort);
	if( $content )
	{
		$props = get_report_props($content);
		cache_create($resort, $cache_file, $props);
	}
}

/**
 * Takes the report's HTML and parse the information out into a hashtable
 */
function get_report_props($report)
{
	$props = array();

	$props['snow.units'] = 'cm';

	preg_match("/Last Updated:\s+(.*?)</", $report, $matches);
	//this string can have odd padding in between words, so stript it off
	$fields = preg_split("/\s+/", $matches[1]);
	$props['date'] = implode(' ', $fields);

	preg_match("/lline\">(\d+) cm(.*?)(\d+) cm(.*?)(\d+) cm(.*?)(\d+)/", $report, $matches);
	$new  = $matches[1];
	$hr24 = $matches[3];	
	$hr48 = $matches[5];	
	$week = $matches[7];	

	$props['snow.fresh'] = $hr24;
	$props['snow.daily'] = "Fresh($new) 24hr($hr24) 48hr($hr48) week($week)";

	$props['snow.total'] = find_int("/colspan=\"2\">(\d+)/", $report);

	preg_match("/Peak<(.*?)width=\"25%\">(.*?)&deg;/", $report, $matches);
	$tempPeak = $matches[2];
	preg_match("/Village<\/td><td>(.*?)&deg;/", $report, $matches);
	$tempVillage = $matches[1];
	$props['temp.readings'] = $tempVillage.' '.$tempPeak;

	get_weather_props(&$props);	

	return $props;
}

/**
 * Translates the canadian report value into one recognized by the android
 * client
 */
function get_icon($ca_val)
{
	switch($ca_val)
	{
		case '00': //sunny
			return 'skc';
		case '02': //sun and clouds
			return 'sct';
		case '03': //cloudy with sunny periods
		case '04': //increasing clouds
			return 'sct';
		case '06': //chance of showers
			return 'scttsra';
		case '08': //chance of flurries (day)
			return 'snow';
		case '10':
			return 'cloudy';
		case '12': //chance of rain
			return 'ra';
		case '16': //chance of flurries
		case '17':
		case '18':
			return 'snow';
		case '16':
			return 'mix';
		case '30': //clear night
			return 'nskc';
		case '37': //night cloudy
			return 'cloudy';
	}
	
	return $ca_val;
}

function get_weather_interval($idx, $label, $offset, $props, $contents)
{
	$props['forecast.when.'.$idx] = $label;

	//move to the offset for this forecast
	$contents = substr($contents, $offset);

	if($idx == 1)
	{
		preg_match("/weathericons\/(.*?).gif/", $contents, $matches);
		$props['weather.icon'] = get_icon($matches[1]);
	}

	preg_match("/title='(.*?)'>/", $contents, $matches);
	$props['forecast.desc.'.$idx] = $matches[1];
}

function get_weather_props($props)
{
	$props['weather.url'] = "http://movement.whistlerblackcomb.com/cache/whistler_fx.php";
	$contents = file_get_contents($props['weather.url']);

	//get the text to parse
	$idx1 = strpos($contents, "<table width='100%' cellpadding='0' cellspacing='0' class='icons' >");
	$idx2 = strpos($contents, "</table>", $idx1);
	$contents = substr($contents, $idx1, $idx2-$idx1);

	//split this up based on each interval (ie Sunday, Monday, Tuesday)
	// keep the offset's so we can parse the actual report
	preg_match_all("/<span class='titleS'>(.*?)<\/span>/", $contents, $matches, PREG_OFFSET_CAPTURE);

	get_weather_interval(1, $matches[1][0][0], $matches[1][0][1], &$props, $contents);
	get_weather_interval(2, $matches[1][1][0], $matches[1][1][1], &$props, $contents);
	get_weather_interval(3, $matches[1][2][0], $matches[1][2][1], &$props, $contents);
}

function get_report($resort)
{
	$contents = file_get_contents($resort->fresh_source_url);

	//strip off some the junk we don't need
	$contents = strstr($contents, "Last Updated");
	$idx = strpos($contents, "Environment Canada Forecast");
	return(substr($contents, 0, $idx));
}

?>
