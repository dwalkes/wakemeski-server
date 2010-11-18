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

require_once('id.inc');

header( "Content-Type: text/plain" );

	$location = $_GET['location'];

	$resorts = resorts_id_get();
	$resort = resort_get_location($resorts, $location);
	
	$cache_file = 'id_'.$location.'.txt';
	$found_cache = cache_available($cache_file);
	if( !$found_cache )
	{
		write_report($resort, $cache_file);
	}

	cache_dump($cache_file, $found_cache);

	log_hit('id_sunvalley_report.php', $location, $found_cache);

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
 * returns the value for the given field in the HTML div
 * This report does a nice consistent job formatting the report, so its easy
 * to parse.
 */
function find_val($report, $field)
{
	if( !preg_match_all("/$field(.*?)\"value\">(\d+)/", $report, $matches, PREG_OFFSET_CAPTURE) )
		return FALSE;
	return $matches[2][0][0];
}

/**
 * Takes the report's HTML and parse the information out into a hashtable
 */
function get_report_props($report)
{
	$props = array();

	preg_match_all("/New Snow as of\s+(.*?)</", $report, $matches, PREG_OFFSET_CAPTURE);
	$props['date'] = $matches[1][0][0];

	$night = find_val($report, 'Since 5:30 AM');
	$day = find_val($report, 'Past 24 Hours');
	$twoday = find_val($report, 'Past 48 Hours');
	$top = find_val($report, 'Top');
	$mid = find_val($report, 'Mid');
	$base = find_val($report, 'Base');

	$props['snow.fresh'] = $day;
	$props['snow.daily'] = "Fresh($night) 24hr($day) 48hr($twoday)";
	$props['snow.units'] = 'inches';

	$props['snow.total'] = "$base $mid $top";

	return $props;
}

function get_report($resort)
{
	$contents = file_get_contents($resort->fresh_source_url);
	
	//strip off some the leading junk we don't need
	$contents = strstr($contents, "<h3>Snow Conditions</h3>");
	//remove eols so we can grep easier
	return str_replace("\r\n", " ", $contents);
}

?>
