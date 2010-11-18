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

	log_hit('id_brundage_report.php', $location, $found_cache);

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
	if( !preg_match_all("/$field<\/td><td class=\"value tdborder\">(\d+)/", $report, $matches, PREG_OFFSET_CAPTURE) )
		return FALSE;
	return $matches[1][0][0];
}

/**
 * Takes the report's HTML and parse the information out into a hashtable
 */
function get_report_props($report)
{
	$props = array();

	preg_match_all("/Last Updated:\s+(.*?)</", $report, $matches, PREG_OFFSET_CAPTURE);
	$props['date'] = $matches[1][0][0];

	$hr24 = find_val($report, '24 Hour');	
	$hr48 = find_val($report, '48 Hour');
	$hr72 = find_val($report, '72 Hour');
	$top  = find_val($report, 'Summit Depth:');
	$base = find_val($report, 'Base Depth:');

	$props['snow.fresh'] = $hr24;
	$props['snow.daily'] = "Fresh($hr24) 48hr($hr48) 72hr($hr72)";
	$props['snow.units'] = 'inches';

	$props['snow.total'] = "$base $top";

	$report = strstr($report, "Current Conditions</td>");
	$report = strstr($report, "<td>");
	$end = strpos($report, "</table>");
	$report = substr($report, 0, $end);	
	$props['location.comments'] = strip_tags($report);

	return $props;
}

function get_report($resort)
{
	$contents = file_get_contents($resort->fresh_source_url);
	
	//strip off some the leading junk we don't need
	return strstr($contents, "<p align=\"right\">Last Updated:");
}

?>
