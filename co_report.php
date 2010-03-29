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

require_once('common.inc');

header( "Content-Type: text/plain" );

	$location = $_GET['location'];

	$resorts = build_resorts_table();

	resort_assert_location($resorts, $location);

	$cache_file = 'co_'.$location.'.txt';
	$found_cache = cache_available($cache_file);
	if( !$found_cache )
	{
		write_report($location);
	}

	cache_dump($cache_file, $found_cache);


function write_report($loc)
{
	global $resorts, $cache_file;

	$readable = resort_get_readable_location($resorts, $loc);
	$report = get_location_report($readable);
	if( $report )
	{
		$props = get_report_props($report);
		$props['location'] = $loc;

		resort_set_weather($resorts, $loc, &$props);

		cache_create($cache_file, $props);
	}
	else
	{
		print("err.msg=No ski report data found\n");
	}
}

/**
 * Takes the report's XML node and parse the information out into a hashtable
 */
function get_report_props($report)
{
	$props = array();
	$data = $report->getElementsByTagName('description')->item(0)->nodeValue;

	preg_match_all("/New Snow Last 24 Hours: (\d+)/", $data, $matches, PREG_OFFSET_CAPTURE);
	$day = $matches[1][0][0];
	preg_match_all("/New Snow Last 48 hours: (\d+)/", $data, $matches, PREG_OFFSET_CAPTURE);
	$yesterday = $matches[1][0][0];
	$props['snow.daily'] = "Fresh($day) 48hr($yesterday)";
	$props['snow.fresh'] = $day;
	$props['snow.units'] = 'inches';

	preg_match_all("/Mid-Mountain Depth: (\d+)/", $data, $matches, PREG_OFFSET_CAPTURE);
	$props['snow.total'] = $matches[1][0][0];

	preg_match_all("/Lifts Open: (\d+)\/(\d+)/", $data, $matches, PREG_OFFSET_CAPTURE);
	$props['lifts.open'] = $matches[1][0][0];
	$props['lifts.total'] = $matches[2][0][0];

	preg_match_all("/Surface Conditions: (.*?)<br/", $data, $matches, PREG_OFFSET_CAPTURE);
	if( $matches[1][0][0] )
		$props['snow.conditions'] = $matches[1][0][0];

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
function get_location_report($loc)
{
	$dom = get_report_xml();

	$items = $dom->getElementsByTagName('item');
	for ($i = 0; $i < $items->length; $i++)
	{
		$title_node = $items->item($i)->getElementsByTagName('title')->item(0);
		$title = trim($title_node->firstChild->nodeValue);
		if(preg_match("/$loc/", $title) )
		{
		    return $items->item($i);
		}
	}

	return false;
}

function get_report_xml()
{
	$xml = file_get_contents("http://feeds.feedburner.com/snowreport");
	$sxe = simplexml_load_string($xml);
	return dom_import_simplexml($sxe);
}

function build_resorts_table()
{
	$resorts['AB'] = resort_props('Arapahoe Basin',     array(39.6448,   -105.871));
	$resorts['AH'] = resort_props('Aspen Highlands',    array(39.181711, -106.856121));
	$resorts['AM'] = resort_props('Aspen Mountain',     array(39.18428,  -106.821903));
	$resorts['BM'] = resort_props('Buttermilk',         array(39.205167, -106.859294));
	$resorts['CM'] = resort_props('Copper Mountain',    array(39.4944,   -106.138732));
	$resorts['CB'] = resort_props('Crested Butte',      array(38.899932, -106.964249));
	$resorts['EM'] = resort_props('Echo Mountain',      array(37.591389, -107.571726));
	$resorts['EL'] = resort_props('Eldora',             array(39.937341, -105.5853));
	$resorts['HW'] = resort_props('Howelsen',           array(40.480533, -106.840605));
	$resorts['LV'] = resort_props('Loveland',           array(39.680191, -105.898114));
	$resorts['MM'] = resort_props('Monarch Mountain',   array(38.512285, -106.332957));
	$resorts['PH'] = resort_props('Powderhorn',         array(39.068912, -108.15068));
	$resorts['PG'] = resort_props('Purgatory',          array(37.629261, -107.815288));
	$resorts['SM'] = resort_props('Silverton Mountain', array(37.791067, -107.666171));
	$resorts['SC'] = resort_props('Ski Cooper',         array(39.358897, -106.299256));
	$resorts['SN'] = resort_props('Snowmass',           array(39.162132, -106.787847));
	$resorts['SV'] = resort_props('Sol Vista Basin',    array(40.04784,  -105.898969));
	$resorts['ST'] = resort_props('Steamboat',          array(40.458905, -106.802092));
	$resorts['SL'] = resort_props('Sunlight',           array(39.398121, -107.339174));
	$resorts['TD'] = resort_props('Telluride',          array(37.9392,   -107.8163));
	$resorts['WP'] = resort_props('Winter Park',        array(39.886791, -105.764279));
	$resorts['WC'] = resort_props('Wolf Creek',         array(37.472654, -106.793116));

	return $resorts;
}

?>
