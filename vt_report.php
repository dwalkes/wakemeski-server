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

require_once('vt.inc');

header( "Content-Type: text/plain" );

	$location = $_GET['location'];

	$resorts = resorts_vt_get();
	$resort = resort_get_location($resorts, $location);

	$resort->fresh_source_url = "http://www.skivermont.com/conditions";
	
	$cache_file = 'vt_'.$location.'.txt';
	$found_cache = cache_available($cache_file);
	if( !$found_cache )
	{
		write_report($resort, $cache_file);
	}

	cache_dump($cache_file, $found_cache);

	log_hit('vt_report.php', $location, $found_cache);

function write_report($resort, $cache_file)
{
	$contents = get_location_content($resort);
	if( $contents )
	{
		$props = get_resort_props($contents);
		cache_create($resort, $cache_file, $props);
	}
}

/**
 * determines if the given date is recent enough to be considered "fresh"
 */
function is_fresh($date)
{
	$time = strtotime($date);
	if( time() - $time < 60*60*24 ) //last 24 hours
		return true;
	return false;
}

/**
 * fresh can be a single value like:
 *  3" on 11/12
 * or a range:
 *  3-5" on 11/12
 */
function find_fresh($props, $content)
{
	if( preg_match("/class=\"value\">(\d+)&quot;(.*?)On\s+(\d+\/\d+)/", $content, $matches) )
	{
		if( is_fresh($matches[3]) )
			$props['snow.fresh'] = $matches[1];
		else
			$props['snow.fresh'] = "0";
		$props['snow.daily'] = $matches[3].'('.$matches[1].') ';
	}
	else if( preg_match("/class=\"value\">(\d+)-(\d+)&quot;(.*?)On\s+(\d+\/\d+)/", $content, $matches) )
	{
		if( is_fresh($matches[4]) )
			$props['snow.fresh'] = $matches[2];
		else
			$props['snow.fresh'] = "0";
		$props['snow.daily'] = $matches[4].'('.$matches[1].') ';
	}
	else
	{
		$props['snow.fresh'] = 'n/a';
	}
}

/**
 * Finds daily values. Its similar to find_fresh because it deals with ranges
 */
function find_daily($props, $content)
{
	if( preg_match("/Previous Snowfall:<\/span>\s+(\d+)&quot; on (.*?)</", $content, $matches) )
		$props['snow.daily'] .= $matches[2].'('.$matches[1].')';
	else if( preg_match("/Previous Snowfall:<\/span>\s+(\d+)\s+-\s+(\d+)&quot; on (.*?)</", $content, $matches) )
		$props['snow.daily'] .= $matches[3].'('.$matches[2].')';
	else if( !array_key_exists($props['snow.daily']) )
		$props['snow.daily'] = 'n/a';
}

/**
 * Takes the resort HTML's content and builds its properties
 */
function get_resort_props($content)
{
	$props = array();

	$props['snow.units'] = 'inches';

	preg_match("/Updated (\d+\/\d+\/\d+)\s+at\s+(\d+:\d+[a-z][a-z])/", $content, $matches);
	$props['date'] = $matches[2]." ".$matches[1];

	//first make sure the resort is reporting for the season
	if( strstr($content, "<p class=\"updated\">Unavailable") )
	{
		$props['location.comments'] = 'Not available';
		return $props;
	}

	find_fresh(&$props, $content);
	find_daily(&$props, $content);		

	$props['snow.total'] = find_int("/Average Base:<\/span>\s+(\d+)/", $content);

	preg_match("/Surface:<\/span>\s+(.*?)<\/td>/", $content, $matches);
	$props['snow.conditions'] = $matches[1]; 

	if( preg_match("/Open Lifts:<\/span>\s+(\d+)\/(\d+)/", $content, $matches) )
	{
		$props['lifts.open'] = $matches[1];
		$props['lifts.total'] = $matches[2];
	}

	if( preg_match("/Open Trails:<\/span>\s+(\d+)\/(\d+)/", $content, $matches) )
	{
		$props['trails.open'] = $matches[1];
		$props['trails.total'] = $matches[2];
	}

	if( preg_match("/Base Temp at Noon:<\/span>\s+(\d+)/", $content, $matches) )
		$props['temp.readings'] = $matches[1];

	if( preg_match("/Snowmaking in past 24 hrs:<\/span> Yes/", $content, $matches) )
		$props['snow.making'] = 1;

	return $props;
}

/**
 * Extracts the report content HTML for the given resort from the web page
 */
function get_location_content($resort)
{
	$contents = file_get_contents($resort->fresh_source_url);

	preg_match_all("/<h3>($resort->name)(.*?)<\/h3>/", $contents, $matches, PREG_OFFSET_CAPTURE);
	if( $matches[1][0][1] !== false )
	{
		$start = $matches[1][0][1];
		$idx = strpos($contents, "</table>", $start);
		if( $idx !== false )
			return substr($contents, $start, $idx-$start);			
	}

	return false;
}

?>
