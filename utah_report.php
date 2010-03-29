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

	$cache_file = 'ut_'.$location.'.txt';
	$found_cache = cache_available($cache_file);
	if( !$found_cache )
	{
		write_report($resorts, $location, $cache_file);
	}

	cache_dump($cache_file, $found_cache);

function write_report($resorts, $loc, $cache_file)
{
	$report = get_report($resorts, $loc);
	if( $report )
	{
		resort_set_weather($resorts, $loc, &$report);

		cache_create($cache_file, $report);
	}
}

function get_url($loc)
{
	if( $loc == 'ATA') $val = 'alta_ski_area';
	else if( $loc == 'BVR') $val = 'beaver_mountain';
	else if( $loc == 'BHR') $val = 'brian_head_resort';
	else if( $loc == 'BRT') $val = 'brighton_ski_resort';
	else if( $loc == 'CNY') $val = 'the_canyons';
	else if( $loc == 'DVR') $val = 'deer_valley_resort';
	else if( $loc == 'PCM') $val = 'park_city_mountain_resort';
	else if( $loc == 'POW') $val = 'powder_mountain_resort';
	else if( $loc == 'SBN') $val = 'snowbasin';
	else if( $loc == 'SBD') $val = 'snowbird_ski_and_summer_resort';
	else if( $loc == 'SOL') $val = 'solitude_mountain_resort';
	else if( $loc == 'SUN') $val = 'sundance_resort';
	else if( $loc == 'WLF') $val = 'wolf_creek_utah_ski_resort';

	return 'http://www.skiutah.com/winter/members/'.$val.'/resort';
}

function get_report_contents($url)
{
	$contents = file_get_contents($url);

	$idx1 = strpos($contents, "v-ski_resort_snow_report");
	$idx2 = strpos($contents, "#v-ski_resort_snow_report");
	if( $idx1 === false || $idx2 === false)
	{
		print "err.msg=report format changed. server update required\n";
		exit(1);
	}

	return substr($contents, $idx1, $idx2-$idx1);
}

function get_report($resorts, $loc)
{
	$url = get_url($loc);
	$contents = get_report_contents($url);

	$data = array();
	$data['location'] = resort_get_readable_location($resorts, $loc);

	$data['location.info'] = $url;

	preg_match_all("/Updated: <span>(.*)<\/span/", $contents, $matches, PREG_OFFSET_CAPTURE);
	$data['date'] = $matches[1][0][0];

	$data['snow.units'] = 'inches';
	preg_match_all("/Snow Last 24<\/th><td>(\d+)/", $contents, $matches, PREG_OFFSET_CAPTURE);
	$data['snow.daily'] = "Fresh(".$matches[1][0][0].")";
	$data['snow.fresh'] = $matches[1][0][0];

	preg_match_all("/Snow Last 48<\/th><td>(\d+)/", $contents, $matches, PREG_OFFSET_CAPTURE);
	$data['snow.daily'] .= " 48hr(".$matches[1][0][0].")";

	preg_match_all("/Base Depth<\/th><td>(\d+)/", $contents, $matches, PREG_OFFSET_CAPTURE);
	$data['snow.total'] = $matches[1][0][0];

	preg_match_all("/<span>(\d+)<\/span>\/<span>(\d+)/", $contents, $matches, PREG_OFFSET_CAPTURE);
	$data['trails.open'] = $matches[1][0][0];
	$data['trails.total'] = $matches[2][0][0];
	$data['lifts.open'] = $matches[1][1][0];
	$data['lifts.total'] = $matches[2][1][0];

	return $data;
}

function build_resorts_table()
{
	$resorts['ATA'] = resort_props('Alta',            array(40.57972, -111.6375));
	$resorts['BVR'] = resort_props('Beaver Mountain', array(41.96833, -111.54083));
	$resorts['BHR'] = resort_props('Brian Head',      array(37.69194, -112.83722));
	$resorts['BRT'] = resort_props('Brighton',        array(40.6,     -111.58278));
	$resorts['CNY'] = resort_props('The Canyons',     array(40.68525, -111.556375));
	$resorts['DVR'] = resort_props('Deer Valley',     array(40.63139, -111.47861));
	$resorts['PCM'] = resort_props('Park City',       array(40.64361, -111.50417));
	$resorts['POW'] = resort_props('Powder Mountain', array(41.37778, -111.77111));
	$resorts['SBN'] = resort_props('Snowbasin',       array(41.21194, -111.85111));
	$resorts['SBD'] = resort_props('Snowbird',        array(40.57805, -111.666755));
	$resorts['SOL'] = resort_props('Solitude',        array(40.62556, -111.59444));
	$resorts['SUN'] = resort_props('Sundance',        array(40.38583, -111.58083));
	$resorts['WLF'] = resort_props('Wolf Creek',      array(40.47667, -111.02361));

	return $resorts;
}

?>
