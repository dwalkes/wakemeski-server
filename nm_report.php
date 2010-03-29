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

require_once('weather.inc');
require_once('common.inc');

header( "Content-Type: text/plain" );

	$location = $_GET['location'];

	$resorts = build_resorts_table();

	resort_assert_location($resorts, $location);

	$cache_file = 'nm_'.$location.'.txt';
	$found_cache = cache_available($cache_file);
	if( !$found_cache )
	{
		write_report($resorts, $location, $cache_file);
	}

	cache_dump($cache_file, $found_cache);

function write_report($resorts, $loc, $cache_file)
{
	$reports = get_reports($resorts);
	$report = $reports[$loc];
	if( $report )
	{
		$props['location.info'] = resort_get_info_url($resorts, $loc);

		resort_set_weather($resorts, $loc, &$report);

		cache_create($cache_file, $report);
	}
	else
	{
		print("err.msg=No ski report data found\n");
	}
}

function get_reports($resorts)
{
	$contents = file_get_contents("http://skinewmexico.com/snow_reports/feed.rss");

	//each location is in an <item> tag
	$locations = preg_split("/<item>/", $contents);
	//the first item is header junk we can ignore
	array_shift($locations);

	$reports = array();
	for($i = 0; $i < count($locations); $i++)
	{
		$report = get_report($locations[$i]);
		$loc = resort_get_location($resorts, $report['location']);
		$reports[$loc] = $report;
	}

	return $reports;
}

function get_report($body)
{
	$data = array();
	preg_match_all("/<h1>(.*)<\/h1/", $body, $matches, PREG_OFFSET_CAPTURE);
	$data['location'] = $matches[1][0][0];

	preg_match_all("/<pubDate>(.*)<\/pubDate>/", $body, $matches, PREG_OFFSET_CAPTURE);
	$data['date'] = $matches[1][0][0];

	preg_match_all("/New Natural Snow Last 48 Hours: <b>(\d+)/", $body, $matches, PREG_OFFSET_CAPTURE);
	if( $matches[1][0][0] )
		$data['snow.fresh'] = $matches[1][0][0];
	else
		$data['snow.fresh'] = 'none';
	$data['snow.daily'] = 'Fresh('.$data['snow.fresh'].')';
	$data['snow.units'] = 'inches';

	preg_match_all("/Base Snow Depth \(inches\): <b>(.*?)&quot;/", $body, $matches, PREG_OFFSET_CAPTURE);
	if( $matches[1][0][0] )
		$data['snow.total'] = $matches[1][0][0];
	else
		$data['snow.total'] = '?';

	preg_match_all("/Trails Open: <b>(\d+)/", $body, $matches, PREG_OFFSET_CAPTURE);
	if( $matches[1][0][0] )
		$data['trails.open'] = $matches[1][0][0];
	else
		$data['trails.open'] = 0;

	preg_match_all("/Lifts Open: <b>(\d+)/", $body, $matches, PREG_OFFSET_CAPTURE);
	if( $matches[1][0][0] )
		$data['lifts.open'] = $matches[1][0][0];
	else
		$data['lifts.open'] = 0;

	preg_match_all("/Surface Cond&#58; (.*?)<\/title>/", $body, $matches, PREG_OFFSET_CAPTURE);
	if( $matches[1][0][0] )
		$data['snow.conditions'] = $matches[1][0][0];

	return $data;
}

function build_resorts_table()
{
	$resorts['AF'] = resort_props('Angel Fire',        array(36.3903, -105.2875),   'http://www.angelfireresort.com/winter/mountain-snow-report.php');
	$resorts['EF'] = resort_props('Enchanted Forest',  array(36.7063, -105.4053),   'http://www.enchantedforestxc.com/');
	$resorts['PM'] = resort_props('Pajarito Mountain', array(35.89519,-106.391785), 'http://www.skipajarito.com/conditions.php');
	$resorts['RR'] = resort_props('Red River',         array(36.70859,-105.409924), 'http://redriverskiarea.com/page.php?pname=mountain/snow');
	$resorts['SP'] = resort_props('Sandia Peak',       array(35.20783,-106.41354),  'http://www.sandiapeak.com/index.php?page=snow-report');
	$resorts['SI'] = resort_props('Sipapu',            array(36.15359,-105.54824),  'http://www.sipapunm.com/index.php?option=com_snowreport&view=helloworld&Itemid=73');
	$resorts['SA'] = resort_props('Ski Apache',        array(33.39745,-105.789198), 'http://www.skiapache.com/');
	$resorts['SF'] = resort_props('Ski Santa Fe',      array(35.79679,-105.80166),  'http://skisantafe.com/index.php?page=snow-report');
	$resorts['TS'] = resort_props('Taos',              array(36.35,   -105.27),     'http://www.skitaos.org/snow_reports/index');
	$resorts['VC'] = resort_props('Valles Caldera',    array(35.9,    -106.55),     'http://www.vallescaldera.gov/comevisit/skisnow/');

	return $resorts;
}

?>
