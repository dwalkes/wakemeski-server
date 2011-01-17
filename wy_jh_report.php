<?php
/*
 * Copyright (c) 2011 Dan Walkes, Andy Doan
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

/*
 * This script finds ski conditions for Wyoming Jackson Hole resort
 */
require_once('wy.inc');

header( "Content-Type: text/plain" );

	$location = $_GET['location'];

	$resorts = resorts_wy_get();
	$resort = resort_get_location($resorts, $location);

	$resort->fresh_source_url = "http://www.jacksonhole.com/mountain-info/conditions/weather-snow-report.html";
	
	$cache_file = 'wy_'.$location.'.txt';
	$found_cache = cache_available($cache_file);
	if( !$found_cache )
	{
		write_report($resort, $cache_file);
	}

	cache_dump($cache_file, $found_cache);

	log_hit('wy_jackson_hole_report.php', $location, $found_cache);
	
function write_report($resort, $cache_file)
{
	$contents = get_location_content($resort);
	if( $contents )
	{
		$props = get_resort_props($contents);
		cache_create($resort, $cache_file, $props);
	}
}

/*
 * Find a snow value given the name of the table row and the content of
 * the html page.  Returns n\a if table row or value was not found.
 */
function find_snow_value($row_name,$content)
{
	/*
	 * Snow totals are in table rows with <h6> headings based on row names.  
	 * Snow values (for summit) follow with <h2> heading
	 */
	$total = grep_grep_int("/<td.*?><h6>".$row_name."(.*?)<\/tr>/s","/<h2>(\d+)/",$content);
	return $total;
}

function find_lifts_trails_open($desc,$content,&$matches)
{
	return	preg_match("/<h2>(\d+)<\/h2>[^>]*>of (\d+)[^>]*>[^>]*>[^>]*>[^>]*>".$desc."/s",$content,$matches);
}

/**
 * Takes the resort HTML's content and builds its properties
 */
function get_resort_props($content)
{
	$props = array();

	$props['snow.units'] = 'inches';

	if(preg_match("/Weather \/ Snow Report<\/h1><[^>]+>[^\w]*([^<]+)<small>.*Reported as of:\s*(\d+:\d+\s*[A-Z][A-Z])/s", $content, $matches)) 
	{
		$props['date'] = $matches[2]." ".$matches[1];
	}

	$props['snow.fresh'] = find_snow_value("Since lifts closed",$content);
	$props['snow.total'] = find_snow_value("Snow Depth", $content);
	$value = find_snow_value("24 Hours",$content);
	if( int_found($value) ) 
	{
		$props['snow.daily'] .= "24 Hours(" . $value . ")";
	}
	$value = find_snow_value("48 Hours",$content);
	if( int_found($value) ) 
	{
		$props['snow.daily'] .= "48 Hours(" . $value . ")";
	}
	
	if( find_lifts_trails_open("Lifts Open",$content,$matches) )
	{
		$props['lifts.open'] = $matches[1];
		$props['lifts.total'] = $matches[2];
	}

	if(find_lifts_trails_open("Trails Open",$content, $matches) )
	{
		$props['trails.open'] = $matches[1];
		$props['trails.total'] = $matches[2];
	}

	return $props;
}

/**
 * Extracts the report content HTML for the given resort from the web page
 */
function get_location_content($resort)
{
	$contents = file_get_contents($resort->fresh_source_url);
	return $contents;
}
