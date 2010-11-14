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

require_once('me.inc');

header( "Content-Type: text/plain" );

	$location = $_GET['location'];

	$resorts = resorts_me_get();
	$resort = resort_get_location($resorts, $location);

	$resort->fresh_source_url = "http://feeds.feedburner.com/Sugarloaf/snowreport";
	
	$cache_file = 'me_'.$location.'.txt';
	$found_cache = cache_available($cache_file);
	if( !$found_cache )
	{
		write_report($resort, $cache_file);
	}

	cache_dump($cache_file, $found_cache);

	log_hit('me_sugarloaf_report.php', $location, $found_cache);

function write_report($resort, $cache_file)
{
	$xml = get_location_report($resort);
	if( $xml )
	{
		$props = get_report_props($xml);
		cache_create($resort, $cache_file, $props);
	}
}

/**
 * Takes the report's XML node and parse the information out into a hashtable
 */
function get_report_props($report)
{
	$props = array();
	$data = $report->getElementsByTagName('description')->item(0)->nodeValue;

	$props['snow.daily'] = 'n/a';
	$props['snow.fresh'] = 'n/a';

	$day = find_int("/24-hr Snowfall:<\/b>\s+(\d+)/", $data);
	$week = find_int("/Past 7 Days Snow:<\/b>\s+(\d+)/", $data);

	if( $day != 'n/a' )
	{
		$props['snow.daily'] = "Fresh($day)";
		$props['snow.fresh'] = $day;
	}

	if( $week != 'n/a' && $day != 'n/a' )
		$props['snow.daily'] .= " Week($week)";
	else if( $week != 'n/a' )
		$props['snow.daily'] = "Week($week)";

	$props['snow.units'] = 'inches';

	$props['snow.total'] = find_int("/<b>Average Base Depth:<\/b>\s+(\d+)/", $data);

	preg_match_all("/Lifts Open:<\/b>\s+(\d+)/", $data, $matches, PREG_OFFSET_CAPTURE);
	$props['lifts.open'] = $matches[1][0][0];
	
	preg_match_all("/Trails Open:<\/b>\s+(\d+)/", $data, $matches, PREG_OFFSET_CAPTURE);
	$props['trails.open'] = $matches[1][0][0];

	preg_match_all("/Primary Surface:<\/b>\s+(.*?)<b/", $data, $matches, PREG_OFFSET_CAPTURE);
	if( $matches[1][0][0] )
		$props['snow.conditions'] = $matches[1][0][0];

	preg_match_all("/Comments:<\/b>\s+(.*?)</", $data, $matches, PREG_OFFSET_CAPTURE);
	if( $matches[1][0][0] )
	{
		$props['location.comments'] = strip_tags($matches[1][0][0]);
		//ensure we don't just give an empty comment
		if( $props['location.comments'] == '--' )
			unset($props['location.comments']);
	}

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
function get_location_report($resort)
{
	$dom = get_report_xml($resort);

	$items = $dom->getElementsByTagName('item');
	for ($i = 0; $i < $items->length; $i++)
	{
		$title_node = $items->item($i)->getElementsByTagName('title')->item(0);
		$title = trim($title_node->firstChild->nodeValue);
		$name = $resort->name;
		if(preg_match("/$name/", $title) )
		{
		    return $items->item($i);
		}
	}

	return false;
}

function get_report_xml($resort)
{
	$xml = file_get_contents($resort->fresh_source_url);
	$sxe = simplexml_load_string($xml);
	return dom_import_simplexml($sxe);
}

?>
